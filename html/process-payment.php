<?php

/**
 * Process Direct Payment
 */

// Загружаем WordPress
define('WP_USE_THEMES', false);
require_once('./wp-load.php');

try {
    // Проверяем, что это POST запрос
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        error_log("Invalid request method");
        wp_die(esc_html__('Invalid request method', 'tnlq'));
    }

    // Проверяем nonce для безопасности
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'direct_payment_nonce')) {
        error_log("Security check failed");
        wp_die(esc_html__('Security check failed', 'tnlq'));
    }

    // Получаем и валидируем данные
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : 'None';
    $coupon_code = isset($_POST['promo']) ? sanitize_text_field(wp_unslash($_POST['promo'])) : '';

    if (!$product_id || !is_email($email)) {
        error_log("Invalid product or email");
        // wp_die(esc_html__('Invalid product or email', 'tnlq'));
        showClientError($email, $product_id, $coupon_code, __('Invalid product or email', 'tnlq'));
    }

    if (!empty($coupon_code) && !check_coupon_rate_limit($email)) {
        error_log("Too many coupon attempts.");
        showClientError($email, $product_id, $coupon_code, __('Too many coupon attempts. Please try again in 20 minutes.', 'tnlq'));
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        throw new Exception(__('Product not found', 'tnlq'));
    }

    // Создаем заказ
    $order = wc_create_order();
    $order->add_product($product, 1);

    // Применяем купон, если он передан и валиден
    if (!empty($coupon_code)) {
        // Проверяем существование купона
        $coupon = new WC_Coupon($coupon_code);
        $WC_Discounts = new WC_Discounts();
        // Проверяем, действителен ли купон
        if ($coupon->get_id() && $WC_Discounts->is_coupon_valid($coupon)) {
            // Проверяем минимальную сумму заказа для купона
            $minimum_amount = $coupon->get_minimum_amount();
            if ($minimum_amount > 0) {
                $order_total = $order->get_total();
                if ($order_total < $minimum_amount) {
                    increment_failed_coupon_attempts($email);
                    error_log(sprintf(__('Minimum order amount for this coupon is %s', 'tnlq'), wc_price($minimum_amount)));
                    showClientError($email, $product_id, $coupon_code, sprintf(__('Minimum order amount for this coupon is %s', 'tnlq'), wc_price($minimum_amount)), $order);

                    // throw new Exception(sprintf(__('Minimum order amount for this coupon is %s', 'tnlq'), wc_price($minimum_amount)));
                }
            }

            // Проверяем, применим ли купон к товару
            $product_ids = $coupon->get_product_ids();
            $excluded_product_ids = $coupon->get_excluded_product_ids();

            if (!empty($product_ids) && !in_array($product_id, $product_ids)) {
                // throw new Exception();
                error_log("This coupon $coupon_code is not valid for the selected product");
                increment_failed_coupon_attempts($email);
                showClientError($email, $product_id, $coupon_code, __('This coupon is not valid for the selected product', 'tnlq'), $order);
            }

            if (!empty($excluded_product_ids) && in_array($product_id, $excluded_product_ids)) {
                // throw new Exception(__('This coupon is not valid for the selected product', 'tnlq'));
                error_log("This coupon $coupon_code is not valid for the selected product");
                increment_failed_coupon_attempts($email);
                showClientError($email, $product_id, $coupon_code, __('This coupon is not valid for the selected product', 'tnlq'), $order);
            }

            // Применяем купон к заказу
            $result = $order->apply_coupon($coupon_code);

            if (is_wp_error($result)) {
                increment_failed_coupon_attempts($email);
                throw new Exception($result->get_error_message());
            }

            // Сохраняем информацию о примененном купоне в мета-данные
            reset_coupon_attempts($email);
            update_post_meta($order->get_id(), '_applied_coupon', $coupon_code);
        } else {
            increment_failed_coupon_attempts($email);
            error_log("Invalid coupon $coupon_code");
            showClientError($email, $product_id, $coupon_code, __("Invalid coupon code", 'tnlq'), $order);
            // Получаем конкретную причину невалидности купона
            // $error_message = __('Invalid coupon code', 'tnlq');
            // throw new Exception($error_message);
        }
    }

    // Устанавливаем данные заказа
    $order->set_address(array(
        'email' => $email,
        'first_name' => $email,
        'last_name' => '',
    ), 'billing');

    // Рассчитываем итоги
    $order->calculate_totals();

    // Проверяем, равна ли итоговая сумма 0
    if ($order->get_total() == 0) {
        // Если сумма равна 0, отмечаем заказ как оплаченный
        $order->set_status('completed');
        $order->set_payment_method('free_gateway'); // Можно установить виртуальный метод оплаты
        $order->save();
        set_order_affiliate_from_cookie($order->get_id());

        // Создаем URL для успешной оплаты
        $success_url = add_query_arg(array(
            'payment_status' => 'success',
            'order_id' => $order->get_id(),
            'key' => $order->get_order_key(),
            'free_order' => 'true'
        ), site_url('/'));

        // Перенаправляем на страницу успеха
        wp_redirect($success_url);
        exit;
    }

    // Если сумма не равна 0, продолжаем с обычным процессом оплаты
    $order->set_status('pending');
    $order->save();

    // Устанавливаем платежный метод NOWPayments
    if (!function_exists('wc')) {
        throw new Exception(__('WooCommerce is not loaded', 'tnlq'));
    }

    $payment_gateways = WC()->payment_gateways ? WC()->payment_gateways->payment_gateways() : array();

    if (empty($payment_gateways['nowpayments'])) {
        throw new Exception(__('NOWPayments gateway is not available', 'tnlq'));
    }

    $nowpayments_gateway = $payment_gateways['nowpayments'];

    $order->set_payment_method($nowpayments_gateway);
    $order->save(); // Сохраняем изменения с платежным методом
    set_order_affiliate_from_cookie($order->get_id());

    // Создаем URL для успешной оплаты и отмены
    $success_url = add_query_arg(array(
        'payment_status' => 'success',
        'order_id' => $order->get_id(),
        'key' => $order->get_order_key()
    ), site_url('/'));

    $cancel_url = add_query_arg(array(
        'cancel_order' => 'true',
        'order_id' => $order->get_id(),
        'key' => $order->get_order_key()
    ), site_url('/'));

    add_filter('woocommerce_get_return_url', function () use ($success_url) {
        return $success_url;
    });

    add_filter('woocommerce_get_cancel_order_url_raw', function () use ($cancel_url) {
        return $cancel_url;
    });

    // Теперь получаем URL оплаты через NOWPayments
    $result = $nowpayments_gateway->process_payment($order->get_id());
    if (is_array($result) && isset($result['result']) && $result['result'] === 'success' && !empty($result['redirect'])) {
        $payment_url = esc_url_raw($result['redirect']);

        // Сохраняем URL оплаты в мета-данные заказа (опционально)
        update_post_meta($order->get_id(), '_payment_url', $payment_url);

        // Перенаправляем на страницу оплаты NOWPayments
        wp_redirect($payment_url);
        exit;
    } else {
        $error_message = isset($result['message']) ? $result['message'] : 'Unknown error';
        throw new Exception(sprintf(__('Failed to create payment: %s', 'tnlq'), $error_message));
    }
} catch (Exception $e) {
    error_log('Direct payment error: ' . $e->getMessage());
    showClientError($email, $product_id, $coupon_code, "Please contact support or try again later.");
}


function showClientError($email, $product_id, $coupon_code, $message, $need_delete_order = null)
{
    // if ($need_delete_order && is_a($need_delete_order, 'WC_Order')) {
    //     $need_delete_order->delete(true); // true = удалить навсегда
    //     error_log("Order #{$need_delete_order->get_id()} deleted due to error: {$message}");
    // }

    $error_url = add_query_arg(array(
        'payment_result' => 'error',
        'email' => $email,
        'product_id' => $product_id,
        'promo' => $coupon_code,
        'message' => urlencode($message),
    ), site_url('/'));

    wp_redirect($error_url);
    exit;
}

function increment_failed_coupon_attempts($email)
{
    $ip_address = get_real_user_ip() ?? 'unknown';
    $identifier = md5($email . $ip_address);
    $attempts_key = 'coupon_attempts_' . $identifier;

    $attempts = get_transient($attempts_key);
    $attempts = $attempts ? intval($attempts) + 1 : 1;

    // Сохраняем на 20 минут
    set_transient($attempts_key, $attempts, 20 * MINUTE_IN_SECONDS);
}

function reset_coupon_attempts($email)
{
    $ip_address =  get_real_user_ip() ?? 'unknown';
    $identifier = md5($email . $ip_address);
    $attempts_key = 'coupon_attempts_' . $identifier;
    $blocked_key = 'coupon_blocked_' . $identifier;

    delete_transient($attempts_key);
    delete_transient($blocked_key);
}

function check_coupon_rate_limit($email)
{
    $ip_address =  get_real_user_ip() ?? 'unknown';
    $identifier = md5($email . $ip_address); // Комбинируем email и IP для лучшей идентификации

    $attempts_key = 'coupon_attempts_' . $identifier;
    $blocked_key = 'coupon_blocked_' . $identifier;

    // Проверяем, не заблокирован ли пользователь
    if (get_transient($blocked_key)) {
        return false;
    }

    // Получаем текущие попытки
    $attempts = get_transient($attempts_key);
    $attempts = $attempts ? intval($attempts) : 0;

    // Если попыток больше 5, блокируем на 20 минут
    if ($attempts >= 5) {
        set_transient($blocked_key, true, 20 * MINUTE_IN_SECONDS);
        delete_transient($attempts_key);
        return false;
    }

    return true;
}


/**
 * Сохраняет ID партнёра из cookie AffiliateWP в мета-поля заказа и создает реферал
 *
 * @param int $order_id ID созданного заказа.
 */
function set_order_affiliate_from_cookie($order_id)
{
    // Проверяем, активен ли AffiliateWP
    if (! function_exists('affiliate_wp')) {
        return;
    }

    // Проверяем наличие cookie с ID партнёра
    if (empty($_COOKIE['affwp_ref'])) {
        return;
    }

    $affiliate_id = intval($_COOKIE['affwp_ref']);
    if ($affiliate_id <= 0) {
        return;
    }

    // Убеждаемся, что партнёр действительно существует и активен
    $affiliate = affwp_get_affiliate($affiliate_id);
    if (!$affiliate || $affiliate->status !== 'active') {
        return;
    }

    // Проверяем, является ли это саморефералом (партнер пытается сделать заказ под своим же аккаунтом)
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $customer_email = $order->get_billing_email();
    $affiliate_user_id = affwp_get_affiliate_user_id($affiliate_id);
    $affiliate_user = get_userdata($affiliate_user_id);

    if ($affiliate_user && $customer_email === $affiliate_user->user_email) {
        // Это самореферал, проверяем настройки
        $self_referral_setting = affiliate_wp()->settings->get('fraud_prevention_self_referrals', 'reject');

        if ('reject' === $self_referral_setting) {
            // Отклоняем самореферал
            return;
        } elseif ('flag' === $self_referral_setting) {
            // Помечаем реферал для ручной проверки
            update_post_meta($order_id, '_affwp_needs_review', true);
        }
        // 'allow' - разрешаем самореферал
    }

    // Сохраняем ID партнёра в мета-поле заказа
    update_post_meta($order_id, '_affwp_affiliate_id', $affiliate_id);

    // Если есть cookie с ID визита – сохраняем и его (для более точной статистики)
    $visit_id = false;
    if (! empty($_COOKIE['affwp_ref_visit_id'])) {
        $visit_id = intval($_COOKIE['affwp_ref_visit_id']);
        update_post_meta($order_id, '_affwp_visit_id', $visit_id);
    }

    // Проверяем, не был ли уже создан реферал для этого заказа
    $existing_referral = affwp_get_referral_by('reference', $order_id, 'woocommerce');
    if (!is_wp_error($existing_referral)) {
        // Реферал уже существует, не создаем новый
        error_log("Referral already exists for order {$order_id}, skipping creation");
        return;
    }

    error_log("No existing referral found for order {$order_id}, proceeding with referral creation");

    // Получаем детали заказа для создания реферала
    $order_total = $order->get_total();
    $order_date = $order->get_date_created()->date('Y-m-d H:i:s');

    // Получаем список товаров в заказе (используем формат, как в интеграции WooCommerce)
    $products = array();
    foreach ($order->get_items() as $key => $product) {
        $products[] = array(
            'name' => $product['name'],
            'id' => $product['product_id'],
            'price' => $product['line_total'],
            'quantity' => $product['qty']
        );
    }

    // Формируем описание реферала
    $description = implode(', ', array_column($products, 'name'));

    // Рассчитываем сумму реферала на основе настроек партнера и товаров
    $referral_amount = 0;
    $affiliate_rate = affwp_get_affiliate_rate($affiliate_id, false, '', $order_id);
    $rate_type = affwp_get_affiliate_rate_type($affiliate_id);

    if ('percentage' === $rate_type) {
        $referral_amount = round($order_total * $affiliate_rate, 2);
    } else { // flat rate
        // Для фиксированной ставки может быть настроена по продуктам или по заказу
        if (affwp_is_per_order_rate($affiliate_id)) {
            $referral_amount = $affiliate_rate;
        } else {
            // Расчет по каждому продукту
            foreach ($order->get_items() as $key => $product) {
                $product_id = $product['product_id'];
                $item_total = $product['line_total'];

                // Проверяем, есть ли специальная ставка для этого продукта
                $product_rate = get_post_meta($product_id, '_affwp_woocommerce_product_rate', true);
                $product_rate_type = get_post_meta($product_id, '_affwp_woocommerce_product_rate_type', true);

                if ($product_rate) {
                    if ('percentage' === $product_rate_type) {
                        $referral_amount += round($item_total * $product_rate, 2);
                    } else {
                        $referral_amount += $product_rate * $product['qty'];
                    }
                } else {
                    // Используем общую ставку партнера
                    if ('percentage' === $rate_type) {
                        $referral_amount += round($item_total * $affiliate_rate, 2);
                    } else {
                        // Для фиксированной ставки на продукт - обычно берется за единицу
                        $referral_amount += $affiliate_rate * $product['qty'];
                    }
                }
            }
        }
    }

    // Добавим отладочную информацию для проверки расчетов
    error_log("Order total: {$order_total}, Affiliate rate: {$affiliate_rate}, Rate type: {$rate_type}, Calculated referral amount: {$referral_amount}");

    // Проверяем, не отключены ли рефералы для каких-то продуктов
    foreach ($order->get_items() as $key => $product) {
        $product_id = $product['product_id'];
        $referrals_disabled = get_post_meta($product_id, '_affwp_woocommerce_referrals_disabled', true);

        if ($referrals_disabled) {
            // Рефералы отключены для этого продукта
            $referral_amount = 0;
            break;
        }
    }

    // Проверяем, нужно ли игнорировать нулевые рефералы
    if ($referral_amount == 0 && affiliate_wp()->settings->get('ignore_zero_referrals')) {
        error_log("Referral amount is 0 and ignore_zero_referrals is enabled, skipping referral creation");
        return;
    }

    // Создаем реферал
    $referral_data = array(
        'affiliate_id' => $affiliate_id,
        'amount'       => $referral_amount,
        'description'  => $description,
        'reference'    => $order_id,
        'context'      => 'woocommerce', // !!! Указываем правильный контекст для интеграции с WooCommerce !!! ВАЖНО !!!
        'campaign'     => !empty($_COOKIE['affwp_campaign']) ? sanitize_text_field($_COOKIE['affwp_campaign']) : '',
        'status'       => 'pending', // Статус будет изменен на 'unpaid' после подтверждения оплаты
        'products'     => $products,
        'date'         => $order_date,
        'type'         => 'sale'
    );

    // Добавляем ID визита, если он есть
    if ($visit_id) {
        $referral_data['visit_id'] = $visit_id;
    }

    // Создаем реферал в системе
    $referral_id = affwp_add_referral($referral_data);

    if ($referral_id) {
        // Добавляем заметку к заказу о созданном реферале
        $order->add_order_note(
            sprintf(
                /* translators: %1$s is the referral URL, %2$s is the formatted money amount, and %3$s is the affiliate's name. */
                __('Referral %1$s created. Amount %2$s recorded for %3$s', 'affiliate-wp'),
                affwp_admin_link(
                    'referrals',
                    esc_html("#{$referral_id}"),
                    array(
                        'action'      => 'edit_referral',
                        'referral_id' => $referral_id,
                    )
                ),
                affwp_currency_filter(affwp_format_amount($referral_amount)),
                affiliate_wp()->affiliates->get_affiliate_name($affiliate_id)
            )
        );
        error_log("Successfully created referral #{$referral_id} with amount {$referral_amount} for order {$order_id}");
    } else {
        error_log("Failed to create referral for order {$order_id} with amount {$referral_amount}");
    }
}

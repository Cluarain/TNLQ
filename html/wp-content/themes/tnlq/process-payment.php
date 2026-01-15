<?php

/**
 * Process Direct Payment
 */

// Загружаем WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Проверяем, что это POST запрос
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    wp_die(esc_html__('Invalid request method', 'tnlq'));
}

// Проверяем nonce для безопасности
if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'direct_payment_nonce')) {
    wp_die(esc_html__('Security check failed', 'tnlq'));
}

// Получаем и валидируем данные
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : 'None';
$coupon_code = isset($_POST['promo']) ? sanitize_text_field(wp_unslash($_POST['promo'])) : '';

if (!$product_id || !is_email($email)) {
    // wp_die(esc_html__('Invalid product or email', 'tnlq'));
    showClientError($email, $product_id, $coupon_code, esc_html__('Invalid product or email', 'tnlq'));
}

try {
    if (!empty($coupon_code) && !check_coupon_rate_limit($email)) {
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
                    showClientError($email, $product_id, $coupon_code, sprintf(__('Minimum order amount for this coupon is %s', 'tnlq'), wc_price($minimum_amount)), $order);
                    // throw new Exception(sprintf(__('Minimum order amount for this coupon is %s', 'tnlq'), wc_price($minimum_amount)));
                }
            }

            // Проверяем, применим ли купон к товару
            $product_ids = $coupon->get_product_ids();
            $excluded_product_ids = $coupon->get_excluded_product_ids();

            if (!empty($product_ids) && !in_array($product_id, $product_ids)) {
                // throw new Exception();
                increment_failed_coupon_attempts($email);
                showClientError($email, $product_id, $coupon_code, __('This coupon is not valid for the selected product', 'tnlq'), $order);
            }

            if (!empty($excluded_product_ids) && in_array($product_id, $excluded_product_ids)) {
                // throw new Exception(__('This coupon is not valid for the selected product', 'tnlq'));
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
            showClientError($email, $product_id, $coupon_code, "Invalid coupon code", $order);
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

    // Сохраняем email в мета-данные заказа
    update_post_meta($order->get_id(), '_billing_email', $email);

    // Теперь получаем URL оплаты через NOWPayments
    $result = $nowpayments_gateway->process_payment($order->get_id());
    if (is_array($result) && isset($result['result']) && $result['result'] === 'success' && !empty($result['redirect'])) {
        $payment_url = esc_url_raw($result['redirect']);

        // Сохраняем URL оплаты в мета-данные заказа (опционально)
        update_post_meta($order->get_id(), '_payment_url', $payment_url);
        update_post_meta($order->get_id(), 'Payment URL', $payment_url);

        // Перенаправляем на страницу оплаты NOWPayments
        wp_redirect($payment_url);
        exit;
    } else {
        $error_message = isset($result['message']) ? $result['message'] : 'Unknown error';
        throw new Exception(sprintf(__('Failed to create payment: %s', 'tnlq'), $error_message));
    }
} catch (Exception $e) {
    error_log('Direct payment error: ' . $e->getMessage());
    showClientError($email, $product_id, $coupon_code, "Error creating order. Please contact support or try again later.");
}


function showClientError($email, $product_id, $coupon_code, $message, $need_delete_order = null)
{
    // if ($need_delete_order) {
    //     wp_delete_post($need_delete_order->get_id(), true);
    // }

    $error_url = add_query_arg(array(
        'payment_result' => 'error',
        'email' => $email,
        'product_id' => $product_id,
        'promo' => $coupon_code,
        'message' => $message,
    ), site_url('/'));
    wp_redirect($error_url);
    exit;
}

function increment_failed_coupon_attempts($email)
{
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $identifier = md5($email . $ip_address);
    $attempts_key = 'coupon_attempts_' . $identifier;

    $attempts = get_transient($attempts_key);
    $attempts = $attempts ? intval($attempts) + 1 : 1;

    // Сохраняем на 20 минут
    set_transient($attempts_key, $attempts, 20 * MINUTE_IN_SECONDS);
}

function reset_coupon_attempts($email)
{
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $identifier = md5($email . $ip_address);
    $attempts_key = 'coupon_attempts_' . $identifier;
    $blocked_key = 'coupon_blocked_' . $identifier;

    delete_transient($attempts_key);
    delete_transient($blocked_key);
}

function check_coupon_rate_limit($email)
{
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
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

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
    wp_die(esc_html__('Invalid product or email', 'tnlq'));
}

try {
    // Получаем информацию о товаре
    $product = wc_get_product($product_id);
    if (!$product) {
        throw new Exception(__('Product not found', 'tnlq'));
    }

    // Проверяем купон, если он передан
    if (!empty($coupon_code)) {
        // Проверяем существование купона
        $coupon = new WC_Coupon($coupon_code);

        // Создаем временный заказ для проверки купона
        $temp_order = wc_create_order();
        $temp_order->add_product($product, 1);
        $temp_order->calculate_totals();

        $WC_Discounts = new WC_Discounts($temp_order);

        // Проверяем, действителен ли купон
        if (!$coupon->get_id() || is_wp_error($WC_Discounts->is_coupon_valid($coupon))) {
            // Удаляем временный заказ
            wp_delete_post($temp_order->get_id(), true);
            throw new Exception(__('Invalid coupon code', 'tnlq'));
        }

        // Проверяем минимальную сумму заказа для купона
        $minimum_amount = $coupon->get_minimum_amount();
        if ($minimum_amount > 0) {
            $order_total = $temp_order->get_total();
            if ($order_total < $minimum_amount) {
                wp_delete_post($temp_order->get_id(), true);
                throw new Exception(sprintf(__('Minimum order amount for this coupon is %s', 'tnlq'), wc_price($minimum_amount)));
            }
        }

        // Проверяем, применим ли купон к товару
        $product_ids = $coupon->get_product_ids();
        $excluded_product_ids = $coupon->get_excluded_product_ids();

        if (!empty($product_ids) && !in_array($product_id, $product_ids)) {
            wp_delete_post($temp_order->get_id(), true);
            throw new Exception(__('This coupon is not valid for the selected product', 'tnlq'));
        }

        if (!empty($excluded_product_ids) && in_array($product_id, $excluded_product_ids)) {
            wp_delete_post($temp_order->get_id(), true);
            throw new Exception(__('This coupon is not valid for the selected product', 'tnlq'));
        }

        // Удаляем временный заказ после проверки
        wp_delete_post($temp_order->get_id(), true);
    }

    // Создаем фактический заказ только после всех проверок
    $order = wc_create_order();

    // Добавляем товар в заказ
    $order->add_product($product, 1);

    // Применяем купон, если он передан и прошел проверку
    if (!empty($coupon_code)) {
        // Применяем купон к заказу
        $result = $order->apply_coupon($coupon_code);

        if (is_wp_error($result)) {
            // Удаляем заказ если купон не применился
            wp_delete_post($order->get_id(), true);
            throw new Exception($result->get_error_message());
        }

        // Сохраняем информацию о примененном купоне в мета-данные
        update_post_meta($order->get_id(), '_applied_coupon', $coupon_code);
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
        // Удаляем заказ если WooCommerce не загружен
        wp_delete_post($order->get_id(), true);
        throw new Exception(__('WooCommerce is not loaded', 'tnlq'));
    }

    $payment_gateways = WC()->payment_gateways ? WC()->payment_gateways->payment_gateways() : array();

    if (empty($payment_gateways['nowpayments'])) {
        // Удаляем заказ если платежный шлюз недоступен
        wp_delete_post($order->get_id(), true);
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
        // Удаляем заказ если не удалось создать платеж
        wp_delete_post($order->get_id(), true);
        $error_message = isset($result['message']) ? $result['message'] : 'Unknown error';
        throw new Exception(sprintf(__('Failed to create payment: %s', 'tnlq'), $error_message));
    }
} catch (Exception $e) {
    error_log('Direct payment error: ' . $e->getMessage());

    // Генерируем безопасный ID ошибки для отладки
    $error_id = uniqid('err_', true);
    error_log('Error ID: ' . $error_id . ' - Details: ' . $e->getMessage());

    // Сохраняем детали ошибки в сессию или временное хранилище (только для админов)
    if (current_user_can('administrator')) {
        set_transient('error_' . $error_id, $e->getMessage(), 300); // Храним 5 минут
    }

    // Перенаправляем на страницу ошибки с минимальной информацией
    $error_url = add_query_arg(array(
        'payment_result' => 'error',
        'error_code' => $error_id, // Только ID ошибки, не детали
    ), site_url('/'));

    wp_redirect($error_url);
    exit;
}

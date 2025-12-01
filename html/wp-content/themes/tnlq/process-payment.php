<?php

/**
 * Process Direct Payment
 */

// Загружаем WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Проверяем, что это POST запрос
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    wp_die('Invalid request method');
}

// Проверяем nonce для безопасности
if (!wp_verify_nonce($_POST['_wpnonce'], 'direct_payment_nonce')) {
    wp_die('Security check failed');
}

// Получаем и валидируем данные
$product_id = intval($_POST['product_id']);
$email = sanitize_email($_POST['email']);

if (!$product_id || !is_email($email)) {
    wp_die('Invalid product or email');
}

try {
    // Создаем заказ
    $order = wc_create_order();

    // Добавляем товар в заказ
    $product = wc_get_product($product_id);
    if (!$product) {
        throw new Exception('Product not found');
    }

    $order->add_product($product, 1);

    // Устанавливаем данные заказа
    $order->set_address(array(
        'email' => $email,
        'first_name' => $email,
        'last_name' => '',
    ), 'billing');

    // Устанавливаем платежный метод NOWPayments
    $payment_gateways = WC()->payment_gateways->payment_gateways();
    $nowpayments_gateway = $payment_gateways['nowpayments'];
    $order->set_payment_method($nowpayments_gateway);

    // Рассчитываем итоги
    $order->calculate_totals();
    $order->set_status('pending');
    $order->save();

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
    if ($result['result'] === 'success') {
        $payment_url = $result['redirect'];

        // Сохраняем URL оплаты в мета-данные заказа (опционально)
        update_post_meta($order->get_id(), '_payment_url', $payment_url);
        update_post_meta($order->get_id(), 'Payment URL', $payment_url);

        // Перенаправляем на страницу оплаты NOWPayments
        wp_redirect($payment_url);
        exit;
    } else {
        throw new Exception('Failed to create payment: ' . (isset($result['message']) ? $result['message'] : 'Unknown error'));
    }
} catch (Exception $e) {
    wp_die('Error creating order: ' . $e->getMessage());
}

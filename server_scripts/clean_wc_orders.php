#!/usr/bin/env php
<?php
/**
 * Скрипт для очистки WooCommerce заказов без email и со статусом Pending payment
 * Запускается каждое утро понедельника и удаляет заказы, старше одной недели
 */

// Убедимся, что скрипт запускается из CLI
error_reporting(0);
ini_set('display_errors', 0);

// Проверяем, что скрипт запущен из консоли
if (php_sapi_name() !== 'cli') {
    die('Access denied');
}

// Подключаем WordPress
require_once('/var/www/html/wp-load.php');

// Проверяем, установлен ли WooCommerce
if (!class_exists('WooCommerce') || !function_exists('wc_get_orders')) {
    die("WooCommerce not installed\n");
}

/**
 * Функция для очистки заказов без email и со статусом Pending payment
 */
function clean_wc_pending_orders_without_email()
{
    global $wpdb;

    // Вычисляем дату неделю назад
    $one_week_ago = date('Y-m-d H:i:s', strtotime('-7 days'));

    // Получаем заказы со статусом pending, без email и старше недели
    $args = array(
        'status' => 'pending',
        'date_created' => '<' . $one_week_ago,
        'limit' => -1, // Получаем все подходящие заказы
        'return' => 'ids'
    );

    $order_ids = wc_get_orders($args);

    if (empty($order_ids)) {
        log_echo("No orders for deletion.");
        return;
    }

    log_echo("Find " . count($order_ids) . " pending orders older 1 week.");

    $deleted_count = 0;

    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);

        if (!$order) {
            continue;
        }

        // Проверяем, есть ли email у заказа
        $billing_email = $order->get_billing_email();
        $price_total = $order->get_total();

        // Если нет email, удаляем заказ
        if (empty($billing_email) && $price_total == 0) {
            $order->delete(false); // false = trash, true = permanent delete
            $deleted_count++;
            log_echo("Order #" . $order_id . " from " . $order->get_date_created() . "was deleted");
        }
    }

    log_echo("Deleted $deleted_count of orders without email and with pending status, older than a week.");
}

function clean_wc_expired_coupons()
{
    global $wpdb;

    $args = array(
        'posts_per_page' => -1,
        'post_type'      => 'shop_coupon',
        'post_status'    => 'publish',
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'date_expires', // Use 'date_expires' for newer WC versions
                'value'   => current_time('timestamp'),
                'compare' => '<='
            ),
            array(
                'key'     => 'date_expires',
                'value'   => '',
                'compare' => '!='
            )
        )
    );

    $coupons = get_posts($args);
    $deleted_count = 0;
    if (! empty($coupons)) {
        foreach ($coupons as $coupon) {
            log_echo($coupon->post_name . " move to trash");
            wp_trash_post($coupon->ID);
            $deleted_count++;
        }
    }
    log_echo("Deleted $deleted_count of expired coupons");
}

function log_echo($text)
{
    echo date('Y-m-d H:i:s') . " -- $text\n";
}

// Выполняем очистку заказов
clean_wc_pending_orders_without_email();

// Выполняем очистку купонов
clean_wc_expired_coupons();

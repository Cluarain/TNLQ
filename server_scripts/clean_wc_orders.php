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
function clean_wc_pending_orders_without_email() {
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
        echo "No orders for deletion.\n";
        return;
    }
    
    echo "Find " . count($order_ids) . " pending orders older 1 week.\n";
    
    $deleted_count = 0;
    
    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            continue;
        }
        
        // Проверяем, есть ли email у заказа
        $billing_email = $order->get_billing_email();
        
        // Если нет email, удаляем заказ
        if (empty($billing_email)) {
            // Полная очистка заказа
            $result = wp_delete_post($order_id, true); // true означает удаление навсегда
            
            if ($result) {
                $deleted_count++;
                error_log("Order #" . $order_id . " from " . $order->get_date_created() . "was deleted");
            } else {
                error_log("Couldn't delete order #" . $order_id);
            }
        }
    }
    
    echo "Deleted $deleted_count of orders without email and with pending status, older than a week.\n";
}

// Выполняем очистку
clean_wc_pending_orders_without_email();
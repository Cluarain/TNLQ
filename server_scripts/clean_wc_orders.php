#!/usr/bin/env php
<?php
/**
 * Скрипт для очистки WooCommerce заказов без email и со статусом Pending payment
 * Запускается каждое утро понедельника и удаляет заказы, старше одной недели
 */

// Убедимся, что скрипт запускается из CLI
if (php_sapi_name() !== 'cli') {
    die('Этот скрипт должен запускаться из командной строки');
}

// Подключаем WordPress
$wp_load_path = '/var/www/html/wp-load.php';

if (file_exists($wp_load_path)) {
    require_once $wp_load_path;
} else {
    die('WordPress не найден по пути: ' . $wp_load_path . "\n");
}

// Проверяем, установлен ли WooCommerce
if (!class_exists('WooCommerce') || !function_exists('wc_get_orders')) {
    die('WooCommerce не установлен или не активирован' . "\n");
}

/**
 * Функция для очистки заказов без email и со статусом Pending payment
 */
function clean_wc_pending_orders_without_email() {
    global $wpdb;
    
    // Вычисляем дату неделю назад
    $one_week_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
    
    echo "Поиск заказов старше: " . $one_week_ago . "\n";
    
    // Получаем заказы со статусом pending, без email и старше недели
    $args = array(
        'status' => 'pending',
        'date_created' => '<' . $one_week_ago,
        'limit' => -1, // Получаем все подходящие заказы
        'return' => 'ids'
    );
    
    $order_ids = wc_get_orders($args);
    
    if (empty($order_ids)) {
        echo "Нет заказов для удаления.\n";
        return;
    }
    
    echo "Найдено " . count($order_ids) . " заказов со статусом pending старше недели.\n";
    
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
                error_log("Удален заказ #" . $order_id . " без email от " . $order->get_date_created());
            } else {
                error_log("Не удалось удалить заказ #" . $order_id);
            }
        }
    }
    
    echo "Удалено $deleted_count заказов без email и со статусом pending, старше недели.\n";
}

// Выполняем очистку
clean_wc_pending_orders_without_email();

echo "Задача завершена: " . date('Y-m-d H:i:s') . "\n";
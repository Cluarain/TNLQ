<?php

error_reporting(0);
ini_set('display_errors', 0);

// Проверяем, что скрипт запущен из консоли
if (php_sapi_name() !== 'cli') {
    die('Access denied');
}

// Подключаем WordPress
require_once('/var/www/html/wp-load.php');


$orders = [514, 513, 511, 510, 508, 507];

foreach ($orders as $order_id) {
    delete_post_meta($order_id, '_vpn_reminder_sent');
}

<?php

// Загружаем WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Проверяем, что функция доступна
if (!function_exists('create_vpn_client')) {
    die('Function create_vpn_client does not exist');
}

// тестовый файл для выдачи конфига с помощью функции
// $test_config = create_vpn_client(0, 9999, 0, 10);

if (isset($test_config['success']) && $test_config['success']) {
    header('Content-Type: text/plain; charset=utf-8');
    echo $test_config['client']['connection_string'];
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($test_config, JSON_PRETTY_PRINT);
}
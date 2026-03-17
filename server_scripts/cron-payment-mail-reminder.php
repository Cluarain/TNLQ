<?php

/**
 * Скрипт для отправки напоминаний об окончании подписки.
 */

// --- НАСТРОЙКИ БЕЗОПАСНОСТИ И ЛОГИРОВАНИЯ ---
// Запрещаем вывод ошибок в консоль/лог (чтобы cron не слал спам root'y)
error_reporting(0);
ini_set('display_errors', 0);

// Проверяем, что скрипт запущен из консоли
if (php_sapi_name() !== 'cli') {
    die('Access denied');
}

// Подключаем WordPress
require_once('/var/www/html/wp-load.php');

/**
 * Log VPN actions for debugging
 */
function my_error_log($order_id, $action, $details = '')
{
    $log_dir = '/var/www/html/wp-content/vpn-logs';
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }

    $log_file = $log_dir . '/' . date('Y-m-d') . '.log';

    $log_entry = sprintf(
        "[%s] Order #%s: %s %s\n",
        current_time('mysql'),
        $order_id,
        $action,
        $details
    );

    // Пишем в файл с блокировкой, чтобы не повредить лог при одновременном запуске
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Убедимся, что WooCommerce активен
if (! class_exists('WooCommerce')) {
    my_error_log(0, 'init', 'WooCommerce не активен. Скрипт остановлен.');
    exit;
}

function generate_reminder_promocode($order_id)
{
    // 1. Проверяем, не создавали ли мы уже промокод для этого заказа
    $existing_code = get_post_meta($order_id, '_vpn_reminder_promocode', true);
    if (!empty($existing_code)) {
        // Проверяем, существует ли купон в WooCommerce (могли удалить вручную)
        $coupon_id = wc_get_coupon_id_by_code($existing_code);
        if ($coupon_id) {
            return $existing_code; // Возвращаем существующий валидный код
        }
    }

    $prefix = 'TUNNEL10-';
    $max_attempts = 10;
    $attempt = 0;

    do {
        $suffix = strtoupper(wp_generate_password(5, false));
        $coupon_code = $prefix . $suffix;
        $attempt++;

        if ($attempt > $max_attempts) {
            my_error_log($order_id, 'promocode_error', 'Не удалось создать уникальный код после ' . $max_attempts . ' попыток');
            return null;
        }
    } while (wc_get_coupon_id_by_code($coupon_code));

    $coupon = new WC_Coupon();
    $coupon->set_code($coupon_code);
    $coupon->set_amount(10);
    $coupon->set_discount_type('percent');
    $coupon->set_date_expires(strtotime('+10 days'));
    $coupon->set_individual_use(true);
    $coupon->set_usage_limit(1);

    try {
        $coupon->save();
    } catch (Exception $e) {
        my_error_log($order_id, 'promocode_exception', $e->getMessage());
        return null;
    }

    if (! $coupon->get_id()) {
        my_error_log($order_id, 'promocode_error', 'Не удалось сохранить купон');
        return null;
    }

    $expiry_date = $coupon->get_date_expires() ? $coupon->get_date_expires()->date('Y-m-d') : '';
    update_post_meta($order_id, '_vpn_reminder_promocode', $coupon_code);
    update_post_meta($order_id, '_vpn_reminder_promocode_expire', $expiry_date);

    return $coupon_code;
}

/**
 * Основная функция проверки и отправки уведомлений
 */
function vpn_send_expiration_reminders()
{
    $now = current_time('mysql');
    $now_timestamp = strtotime($now);
    $some_days_later_timestamp = strtotime('+5 days', $now_timestamp);

    // --- ОПТИМИЗАЦИЯ ЗАПРОСА ---
    // Не выбираем ВСЕ заказы (-1). Ограничиваем выборку последними 2 годами 
    $date_one_year_ago = date('Y-m-d', strtotime('-2 year', $now_timestamp));

    $orders = wc_get_orders([
        'status'    => 'completed',
        'limit'     => -1, // Можно оставить -1, так как date_query сильно ограничит выборку
        'date_query' => array(
            array(
                'after'     => $date_one_year_ago,
                'inclusive' => true,
            ),
        ),
    ]);

    my_error_log(0, 'init_reminder', 'Найдено заказов для проверки: ' . count($orders));

    foreach ($orders as $order) {
        $order_id = $order->get_id();

        // Получаем дату окончания
        $vpn_config = get_post_meta($order_id, '_vpn_config', true);

        // Пропускаем, если нет конфига или даты
        if (empty($vpn_config) || empty($vpn_config['client']['expires_at_unix'])) {
            continue;
        }

        $expires_timestamp = $vpn_config['client']['expires_at_unix'];

        // Проверяем: конфиг ещё активен (expires_at > now) И expires_at <= now+5 дней
        if ($expires_timestamp > $now_timestamp && $expires_timestamp <= $some_days_later_timestamp) {

            // Проверяем, не отправляли ли уже напоминание
            $reminder_sent = get_post_meta($order_id, '_vpn_reminder_sent', true);
            if (! empty($reminder_sent)) {
                continue;
            }

            // --- ИСПРАВЛЕНИЕ ЛОГИКИ ---
            // Проверка: заказ должен быть старше 12 часов
            $created_date = $order->get_date_created();
            if (! $created_date) {
                continue;
            }

            $created_timestamp = $created_date->getTimestamp();
            $hours_since_creation = ($now_timestamp - $created_timestamp) / 3600;

            // Меняем условие на правильное (больше 12 часов)
            if ($hours_since_creation < 12) {
                // my_error_log($order_id, 'skip_recent_order', sprintf('Заказ создан %.1f часов назад, пропускаем', $hours_since_creation));
                continue;
            }

            $customer_email = $order->get_billing_email();
            if (empty($customer_email)) {
                continue;
            }

            // Генерируем (или получаем существующий) промокод
            $gen_promo_code = generate_reminder_promocode($order_id);
            if (empty($gen_promo_code)) {
                my_error_log($order_id, 'gen_promo_code', "NULL промокод");
                continue;
            }

            $expire_date = date('j M Y', $expires_timestamp);
            $subject = sprintf('Your VPN is about to expire! %s - %s', $expire_date, get_bloginfo('name'));

            $template_path = get_template_directory() . '/mail_templates/cron-mail.txt';
            if (!file_exists($template_path)) {
                my_error_log($order_id, 'template_missing', 'Файл шаблона не найден');
                continue;
            }

            $mail_txt_template = file_get_contents($template_path);
            $alt_message = str_replace(
                array('{{var:subject}}', '{{var:expire_date}}', '{{var:gen_promo_code}}', '{{var:main_page_url}}'),
                array($subject, $expire_date, $gen_promo_code, 'https://tuneliqa.com/#pricing'),
                $mail_txt_template
            );

            // Отправляем письмо
            $mail_sent = wp_mail($customer_email, $subject, $alt_message, ['Content-Type: text/plain; charset=UTF-8']);

            if ($mail_sent) {
                update_post_meta($order_id, '_vpn_reminder_sent', current_time('mysql'));
                my_error_log($order_id, 'reminder_mail_sent', "Напоминание отправлено на email {$customer_email}");
            } else {
                my_error_log($order_id, 'reminder_mail_fail', "Ошибка отправки письма (wp_mail returned false)");
            }
        }
    }
}

// Запускаем функцию
vpn_send_expiration_reminders();

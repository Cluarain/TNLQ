<?php

// Защита от прямого доступа через браузер
if (php_sapi_name() !== 'cli') {
    // Устанавливаем заголовки для предотвращения индексации
    header('HTTP/1.0 403 Forbidden');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html><head><meta name="robots" content="noindex,nofollow"></head><body><center><h1>403 Forbidden</h1></center></body></html>';
    exit;
}

// Дополнительная проверка на прямой вызов
defined('ABSPATH') || exit;

/**
 * Скрипт для отправки напоминаний об окончании подписки за 3 дня.
 * Запускать по cron: 0 12 * * * php /var/www/html/cron-payment-mail-reminder.php
 * 
 * Защита от прямого доступа: скрипт проверяет, что запускается в CLI режиме,
 * иначе возвращает HTTP 403 ошибку и заголовки noindex/nofollow.
 */

// Подключаем WordPress, чтобы использовать его функции
require_once(dirname(__FILE__) . '/wp-load.php');



/**
 * Log VPN actions for debugging
 */
function my_error_log($order_id, $action, $details = '')
{
    $log_file = WP_CONTENT_DIR . '/vpn-logs/' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);

    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }

    $log_entry = sprintf(
        "[%s] Order #%s: %s %s\n",
        current_time('mysql'),
        $order_id,
        $action,
        $details
    );

    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}


// Убедимся, что WooCommerce активен
if (! class_exists('WooCommerce')) {
    my_error_log(0, 'init', 'WooCommerce не активен. Скрипт остановлен.');
    exit;
}

function generate_reminder_promocode($order_id)
{
    $prefix = 'TUNNEL10-';
    $max_attempts = 10; // максимальное число попыток, чтобы избежать бесконечного цикла
    $attempt = 0;

    do {
        // Генерируем случайный суффикс (5 символов: буквы и цифры)
        $suffix = strtoupper(wp_generate_password(5, false));
        $coupon_code = $prefix . $suffix;
        $attempt++;

        // Если превышено число попыток – логируем ошибку и выходим
        if ($attempt > $max_attempts) {
            my_error_log($order_id, 'promocode_error', 'Не удалось создать уникальный код после ' . $max_attempts . ' попыток');
            return null;
        }

        // Проверяем, существует ли уже купон с таким кодом
    } while (wc_get_coupon_id_by_code($coupon_code));

    // Теперь код точно уникален, создаём купон
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
    // Текущая дата и время в WordPress (с учётом часового пояса)
    $now = current_time('mysql');
    $now_timestamp = strtotime($now);

    // Дата и время через 5 дней от сейчас
    $some_days_later_timestamp = strtotime('+5 days', $now_timestamp);

    // Выбираем все оплаченные заказы
    $orders = wc_get_orders([
        'status'    => 'completed',
        'limit'     => -1,
    ]);

    foreach ($orders as $order) {
        $order_id = $order->get_id();

        // Получаем дату окончания
        $vpn_config = get_post_meta($order_id, '_vpn_config', true);
        $expires_timestamp = $vpn_config['client']['expires_at_unix'];

        // Проверяем: конфиг ещё активен (expires_at > now) И expires_at <= now+5 дней
        if ($expires_timestamp > $now_timestamp && $expires_timestamp <= $some_days_later_timestamp) {
            // Проверяем, не отправляли ли уже напоминание для этого заказа
            $reminder_sent = get_post_meta($order_id, '_vpn_reminder_sent', true);
            if (! empty($reminder_sent)) {
                continue; // уже отправляли
            }

            // Дополнительная проверка: заказ должен быть старше 12 часов
            $created_date = $order->get_date_created();
            if (! $created_date) {
                my_error_log($order_id, 'skip_no_creation_date', 'Дата создания заказа отсутствует');
                continue;
            }
            $created_timestamp = $created_date->getTimestamp();
            $hours_since_creation = ($now_timestamp - $created_timestamp) / 3600;
            if ($hours_since_creation < 0) {
                my_error_log($order_id, 'skip_recent_order', sprintf('Заказ создан %.1f часов назад, пропускаем', $hours_since_creation));
                continue;
            }

            // Получаем email клиента
            $customer_email = $order->get_billing_email();
            if (empty($customer_email)) {
                continue;
            }

            $gen_promo_code = generate_reminder_promocode($order_id);
            if (empty($gen_promo_code)) {
                my_error_log($order_id, 'gen_promo_code', "NULL промокод");
                continue;
            }

            $expire_date = date('j M Y', $expires_timestamp);
            $subject = sprintf('Your VPN is about to expire! %s - %s', $expire_date, get_bloginfo('name'));

            $mail_txt_template = file_get_contents(dirname(__FILE__) . '/cron-mail.txt');
            $alt_message = str_replace(
                array('{{var:subject}}', '{{var:expire_date}}', '{{var:gen_promo_code}}', '{{var:main_page_url}}'),
                array($subject, $expire_date, $gen_promo_code, site_url('/#pricing')),
                $mail_txt_template
            );

            // Отправляем письмо
            $mail_sent = wp_mail($customer_email, $subject, $alt_message);

            if ($mail_sent) {
                // Сохраняем отметку об отправке (дата отправки)
                update_post_meta($order_id, '_vpn_reminder_sent', current_time('mysql'));
                // Можно также записать в лог для отладки
                my_error_log($order_id, 'reminder_mail_sent', "Напоминание отправлено на email {$customer_email}");
            } else {
                my_error_log($order_id, 'reminder_mail_sent', "Ошибка отправки напоминающего письма");
            }
        }
    }
}

// Запускаем функцию
vpn_send_expiration_reminders();

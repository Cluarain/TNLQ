<?php

/**
 * Скрипт для отправки напоминаний об окончании подписки за 3 дня.
 * Запускать по cron: 0 12 * * * php /var/www/html/cron-payment-mail-reminder.php
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
    // Генерируем 5 случайных букв и цифр
    $random_suffix = wp_generate_password(5, false);
    $coupon_code = 'TUNNEL10-' . strtoupper($random_suffix);

    $coupon = new WC_Coupon();
    $coupon->set_code($coupon_code);
    $coupon->set_amount(10); // Сумма скидки
    $coupon->set_discount_type('percent'); // Тип скидки (процент)

    // Установка срока действия на 10 дней от текущего момента
    $expiry_date = date('Y-m-m', strtotime('+10 days'));
    $coupon->set_date_expires($expiry_date);
    $coupon->set_individual_use(true);
    $coupon->set_usage_limit(1);
    $coupon->save();

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
    $three_days_later_timestamp = strtotime('+5 days', $now_timestamp);

    my_error_log(0, 'three_days_later_timestamp', $three_days_later_timestamp);

    // Выбираем все оплаченные заказы, у которых есть _vpn_expires_at
    $args = [
        'post_type'      => 'wc-orders',
        'post_status'    => 'wc-completed', // только выполненные (оплаченные) заказы
        'posts_per_page' => -1,              // все сразу (если заказов очень много, лучше пагинация)
        'meta_query'     => [
            [
                'key'     => '_vpn_expires_at',
                'compare' => 'EXISTS',       // только заказы с этой метой
            ],
        ],
    ];

    $orders = wc_get_orders($args); // используем wc_get_orders для надёжности

    foreach ($orders as $order) {
        $order_id = $order->get_id();

        // Получаем дату окончания
        $expires_at = get_post_meta($order_id, '_vpn_expires_at', true);
        if (empty($expires_at)) {
            continue; // на всякий случай, хотя meta_query уже отфильтровал
        }

        $expires_timestamp = strtotime($expires_at);

        my_error_log(0, 'expires_timestamp', $expires_timestamp);
        // Проверяем: конфиг ещё активен (expires_at > now) И expires_at <= now+5 дней
        if ($expires_timestamp > $now_timestamp && $expires_timestamp <= $three_days_later_timestamp) {
            // Проверяем, не отправляли ли уже напоминание для этого заказа
            $reminder_sent = get_post_meta($order_id, '_vpn_reminder_sent', true);
            if (! empty($reminder_sent)) {
                continue; // уже отправляли
            }
            // Получаем email клиента
            $customer_email = $order->get_billing_email();
            if (empty($customer_email)) {
                continue;
            }

            $gen_promo_code = generate_reminder_promocode($order_id);

            $expire_date = date('j M Y', $expires_timestamp);
            $subject = sprintf('Your VPN is about to expire! %s - %s', $expire_date, get_bloginfo('name'));

            $mail_txt_template = file_get_contents(dirname(__FILE__) . '/cron-mail.txt');
            $alt_message = str_replace(
                array('{{var:subject}}', '{{var:expire_date}}', '{{var:gen_promo_code}}', '{{var:main_page_url}}'),
                array($subject, $expire_date, $gen_promo_code, site_url('#pricing')),
                $mail_txt_template
            );

            // Отправляем письмо
            $mail_sent = wp_mail($customer_email, $subject, $alt_message);

            if ($mail_sent) {
                // Сохраняем отметку об отправке (дата отправки)
                update_post_meta($order_id, '_vpn_reminder_sent', current_time('mysql'));
                // Можно также записать в лог для отладки
                my_error_log($order_id, 'mail_sent', "Напоминание отправлено на email {$customer_email}");
            } else {
                my_error_log($order_id, 'mail_sent', "Ошибка отправки письма");
            }
        }
    }
}

// Запускаем функцию
vpn_send_expiration_reminders();

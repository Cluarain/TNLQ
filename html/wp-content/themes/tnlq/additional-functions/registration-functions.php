<?php

add_action('user_register', 'send_login_credentials_email', 10, 1);

function send_login_credentials_email($user_id)
{
    // Генерируем новый пароль
    $password = wp_generate_password(12, true, true);
    // устанавливаем этот пароль новому пользователю
    wp_set_password($password, $user_id);

    // собираем инфу
    $user_info = get_userdata($user_id);
    $login = $user_info->user_login;
    $email = $user_info->user_email;

    $site_name = get_bloginfo('name');
    $subject = sprintf('Affiliate Application Accepted! %s', $site_name);

    $mail_txt_template = file_get_contents(dirname(__FILE__) . '/mail_registration.txt');

    $array_replace_from =  array('{{var:user_login}}', '{{var:user_password}}');
    $array_replace_to =    array($login, $password);
   
    $alt_message = str_replace(
        $array_replace_from,
        $array_replace_to,
        $mail_txt_template
    );

    wp_mail($email , $subject, $alt_message, ['Content-Type: text/plain; charset=UTF-8']);
}
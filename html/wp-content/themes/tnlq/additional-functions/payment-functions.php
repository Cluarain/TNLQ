<?php

/**
 * Обработка редиректов с NOWPayments на главную с параметрами
 */
add_action('template_redirect', 'handle_nowpayments_redirects');
function handle_nowpayments_redirects()
{
    global $wp;

    // 1. ОБРАБОТКА СТАРЫХ URL (order-received/274/?page_id=137&key=...&NP_id=...)
    // Проверяем, находимся ли мы на странице order-received
    $is_order_received_page = false;
    $order_id = 0;

    if (isset($wp->query_vars['name']) && $wp->query_vars['name'] === 'order-received' && isset($wp->query_vars['page'])) {
        $is_order_received_page = true;
        $order_id = intval($wp->query_vars['page']);
    }

    if ($is_order_received_page && isset($_GET['NP_id'])) {
        $order_key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
        $np_id = sanitize_text_field(wp_unslash($_GET['NP_id']));

        $order = wc_get_order($order_id);

        if ($order && $order->get_order_key() === $order_key) {
            update_post_meta($order->get_id(), '_np_payment_id', $np_id);

            if ($order->has_status(array('processing', 'completed'))) {
                wp_safe_redirect(add_query_arg(array(
                    'payment_result' => 'success',
                    'order_id' => $order->get_id(),
                    'customer_email' => urlencode($order->get_billing_email()),
                    'np_id' => $np_id
                ), site_url('/')));
                exit;
            } else {
                wp_safe_redirect(add_query_arg(array(
                    'payment_result' => 'pending',
                    'order_id' => $order->get_id(),
                    'np_id' => $np_id
                ), site_url('/')));
                exit;
            }
        }
    }

    // 2. ОБРАБОТКА НОВЫХ URL (?payment_status=success&order_id=...&key=...)
    if (isset($_GET['payment_status']) && $_GET['payment_status'] === 'success') {
        $order_id = (isset($_GET['order_id']) ? intval($_GET['order_id']) : 0);
        $order_key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';

        $order = wc_get_order($order_id);

        if ($order && $order->get_order_key() === $order_key) {
            if ($order->has_status(array('processing', 'completed'))) {
                wp_safe_redirect(add_query_arg(array(
                    'payment_result' => 'success',
                    'order_id' => $order->get_id(),
                    'customer_email' => urlencode($order->get_billing_email())
                ), site_url('/')));
                exit;
            } else {
                wp_safe_redirect(add_query_arg(array(
                    'payment_result' => 'pending',
                    'order_id' => $order->get_id()
                ), site_url('/')));
                exit;
            }
        }
    }

    // 3. ОБРАБОТКА ОТМЕНЫ - НОВЫЙ ФОРМАТ
    if (isset($_GET['cancel_order']) && $_GET['cancel_order'] === 'true') {
        $order_key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';

        // Пытаемся получить order_id
        if (isset($_GET['order_id'])) {
            $order_id = intval($_GET['order_id']);
        }

        if ($order_id && $order_key) {
            $order = wc_get_order($order_id);

            if ($order && $order->get_order_key() === $order_key) {
                $order->update_status('cancelled', __('Payment cancelled by customer.', 'woocommerce'));
                wp_safe_redirect(add_query_arg(array(
                    'payment_result' => 'cancelled',
                    'order_id' => $order->get_id()
                ), site_url('/')));
                exit;
            }
        }
    }

    // 4. ОБРАБОТКА СТАТУСА ЗАКАЗА - СТАРЫЙ И НОВЫЙ ФОРМАТ
    $is_order_pay_page = false;
    $order_id = 0;

    // Проверяем по WooCommerce endpoint (новый формат)
    if (isset($wp->query_vars['order-pay'])) {
        $is_order_pay_page = true;
        $order_id = intval($wp->query_vars['order-pay']);
    }
    // Проверяем по странице (старый формат: /order-pay/274/)
    elseif (isset($wp->query_vars['name']) && $wp->query_vars['name'] === 'order-pay' && isset($wp->query_vars['page'])) {
        $is_order_pay_page = true;
        $order_id = intval($wp->query_vars['page']);
    }

    if ($is_order_pay_page) {
        $order_key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
        $order = wc_get_order($order_id);

        if ($order && $order->get_order_key() === $order_key) {
            $status = $order->get_status();
            wp_safe_redirect(add_query_arg(array(
                'payment_result' => $status,
                'order_id' => $order->get_id(),
                'customer_email' => urlencode($order->get_billing_email()),
            ), site_url('/')));
            exit;
        }
    }
}

/**
 * Обработка изменения статуса заказа для отправки VPN конфигурации
 */
add_action('woocommerce_order_status_changed', 'handle_order_status_change_for_vpn', 10, 4);
function handle_order_status_change_for_vpn($order_id, $old_status, $new_status, $order)
{
    if (! $order instanceof WC_Order) {
        $order = wc_get_order($order_id);
    }

    // Отправляем VPN конфиг только при переходе в processing/completed
    if (in_array($new_status, array('processing', 'completed'))) {
        // Проверяем, не был ли конфиг уже отправлен
        $config_sent = get_post_meta($order_id, '_vpn_config_sent', true);
        $was_pending = get_post_meta($order_id, '_vpn_pending', true);

        // Отправляем только если еще не отправлен или был в pending
        if (!$config_sent || $was_pending === 'yes') {
            $result = process_vpn_for_order($order_id);

            if (!empty($result['success'])) {
                log_vpn_action($order_id, 'status_change_success', 'VPN config created & email flow triggered on status change.');
                delete_post_meta($order_id, '_vpn_last_error');
            } else {
                $message = isset($result['message']) ? $result['message'] : 'Unknown error on status change';
                log_vpn_action($order_id, 'status_change_failed', $message);
                update_post_meta(
                    $order_id,
                    '_vpn_last_error',
                    array(
                        'source'    => 'order_status_changed',
                        'message'   => $message,
                        'error'     => isset($result['error']) ? $result['error'] : '',
                        'timestamp' => current_time('mysql'),
                    )
                );
            }
        }
    }
}

/**
 * Create VPN client via Firestarter API
 */
function create_vpn_client($order_id, $expires_days, $total_gb = 0, $limit_ip = 3)
{
    $api_url = 'https://portal.firestarter.click/api/clients/';

    if (!defined('D_TOKEN')) {
        log_vpn_action(0, 'api_token_missing', 'D_TOKEN constant is not defined');
        return [
            'success' => false,
            'error' => 'api_token_not_configured',
            'message' => 'VPN service is not properly configured. Please contact administrator.'
        ];
    }

    $api_token = D_TOKEN;

    if (empty($api_token) || $api_token === '123') {
        log_vpn_action(0, 'api_token_invalid', 'Using invalid or default API token');
        return [
            'success' => false,
            'error' => 'invalid_api_token',
            'message' => 'VPN service configuration error. Please contact administrator.'
        ];
    }

    $request_args = [
        'method'  => 'POST',
        'headers' => [
            'X-Manager-Token' => $api_token,
            'Content-Type'    => 'application/json',
        ],
        'body'    => json_encode([
            'expires_days' => $expires_days,
            'total_gb'     => $total_gb,
            'limit_ip'     => $limit_ip,
        ]),
    ];

    $response = wp_remote_post($api_url, $request_args);

    if (is_wp_error($response)) {
        $error_message = 'VPN API Connection Error: ' . $response->get_error_message();
        log_vpn_action($order_id, 'api_connection_failed', $response->get_error_message());
        return [
            'success' => false,
            'error' => 'connection_failed',
            'message' => 'Unable to connect to VPN service. Please try again later.',
            'details' => $response->get_error_message()
        ];
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if ($response_code !== 200 && $response_code !== 201) {
        $error_message = 'VPN API Error. HTTP Code: ' . $response_code . ' Response: ' . $response_body;
        log_vpn_action($order_id, 'api_http_error', $error_message);

        $error_data = json_decode($response_body, true);
        $user_message = 'VPN service returned an error. ';

        if (isset($error_data['message'])) {
            $user_message .= 'Details: ' . $error_data['message'];
        } elseif ($response_code === 401) {
            $user_message .= 'Authentication failed. Please check API token.';
        } elseif ($response_code === 403) {
            $user_message .= 'Access forbidden. Please check permissions.';
        } elseif ($response_code === 429) {
            $user_message .= 'Too many requests. Please try again later.';
        } else {
            $user_message .= 'Please contact administrator. Error code: ' . $response_code;
        }

        return array(
            'success' => false,
            'error' => 'api_error',
            'http_code' => $response_code,
            'message' => $user_message,
            'details' => $response_body
        );
    }

    $data = json_decode($response_body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $error_message = 'VPN API JSON Parse Error: ' . json_last_error_msg();
        log_vpn_action(0, 'api_json_error', json_last_error_msg());
        return array(
            'success' => false,
            'error' => 'json_parse_error',
            'message' => 'Invalid response from VPN service.',
            'details' => json_last_error_msg()
        );
    }

    if (!isset($data['client']['connection_string'])) {
        $error_message = 'VPN API Response Missing Connection String: ' . print_r($data, true);
        log_vpn_action(0, 'api_invalid_response', 'Missing connection_string in response');
        return array(
            'success' => false,
            'error' => 'invalid_response',
            'message' => 'VPN service returned incomplete data.',
            'details' => 'Missing connection_string in response'
        );
    }
    return $data;
}

/**
 * Process VPN configuration for order
 */
function process_vpn_for_order($order_id)
{
    $order = wc_get_order($order_id);

    if (!$order) {
        log_vpn_action($order_id, 'order_not_found', 'Order not found while processing VPN config');
        return array(
            'success' => false,
            'error' => 'order_not_found',
            'message' => 'Order not found.'
        );
    }

    $already_sent = get_post_meta($order_id, '_vpn_config_sent', true);
    if ($already_sent) {
        return array(
            'success' => true,
            'message' => 'VPN configuration was already sent.',
            'already_sent' => true
        );
    }

    $customer_email = $order->get_billing_email();
    if (empty($customer_email)) {
        $error_message = 'VPN Config Error: No customer email for order ID: ' . $order_id;
        log_vpn_action($order_id, 'no_email', $error_message);
        return array(
            'success' => false,
            'error' => 'no_email',
            'message' => 'Customer email address is missing.'
        );
    }



    $expires_days = 2;
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product) {
            $product_expires = $product->get_attribute('period');
            if ($product_expires) {
                $expires_days = $product_expires;
            }
        }
    }

    $vpn_config = create_vpn_client($order_id, $expires_days);

    if (!$vpn_config || !isset($vpn_config['success']) || !$vpn_config['success']) {
        $error_msg = isset($vpn_config['message']) ? $vpn_config['message'] : 'Unknown VPN API error';

        $order->add_order_note('VPN Configuration Error: ' . $error_msg);
        log_vpn_action($order_id, 'vpn_create_failed', $error_msg);
        update_post_meta(
            $order_id,
            '_vpn_last_error',
            array(
                'source'    => 'create_vpn_client',
                'message'   => $error_msg,
                'error'     => isset($vpn_config['error']) ? $vpn_config['error'] : 'api_failed',
                'timestamp' => current_time('mysql'),
                'raw'       => $vpn_config,
            )
        );

        return array(
            'success' => false,
            'error' => isset($vpn_config['error']) ? $vpn_config['error'] : 'api_failed',
            'message' => 'Failed to create VPN configuration: ' . $error_msg,
            'details' => $vpn_config
        );
    }

    update_post_meta($order_id, '_vpn_config', $vpn_config);
    update_post_meta($order_id, '_vpn_connection_string', $vpn_config['client']['connection_string']);
    update_post_meta($order_id, '_vpn_expires_at', $vpn_config['client']['expires_at']);
    // update_post_meta($order_id, '_vpn_total_gb', $vpn_config['client']['total_gb']);

    $email_result = send_vpn_config_email($customer_email, $vpn_config, $order);

    if ($email_result['success']) {
        delete_post_meta($order_id, '_vpn_pending');
        update_post_meta($order_id, '_vpn_config_sent', current_time('mysql'));
        delete_post_meta($order_id, '_vpn_last_error');

        $order->add_order_note('VPN configuration successfully sent to customer: ' . $customer_email);
        log_vpn_action($order_id, 'email_sent', 'VPN configuration email sent to ' . $customer_email);

        return array(
            'success' => true,
            'message' => 'VPN configuration created and sent successfully.',
            'email_sent' => true,
            'customer_email' => $customer_email
        );
    } else {
        $order->add_order_note('VPN configuration created but email failed to send: ' . $email_result['message']);
        log_vpn_action($order_id, 'email_failed', $email_result['message']);
        update_post_meta(
            $order_id,
            '_vpn_last_error',
            array(
                'source'    => 'send_vpn_config_email',
                'message'   => $email_result['message'],
                'error'     => 'email_failed',
                'timestamp' => current_time('mysql'),
                'raw'       => $email_result,
            )
        );

        return array(
            'success' => false,
            'error' => 'email_failed',
            'message' => 'VPN configuration created but failed to send email: ' . $email_result['message'],
            'customer_email' => $customer_email,
            'vpn_config' => $vpn_config
        );
    }


    return array(
        'success' => false,
        'error' => 'invalid_status',
        'message' => 'Invalid payment status for VPN configuration.'
    );
}

/**
 * Send VPN configuration email
 */
function send_vpn_config_email($to_email, $vpn_config, $order)
{
    $site_name = get_bloginfo('name');
    $order_id = $order->get_id();

    // $expires_at = isset($vpn_config['expires_at']) ?
    //     date_i18n(get_option('date_format'), strtotime($vpn_config['expires_at'])) :
    //     'Lifetime';

    // $total_gb = isset($vpn_config['total_gb']) && $vpn_config['total_gb'] > 0 ?
    //     $vpn_config['total_gb'] . ' GB' :
    //     'Unlimited';

    $subject = sprintf('VPN is ready! #%s - %s', $order_id, $site_name);

    // load HTML template relative this file
    $mail_html_template = file_get_contents(dirname(__FILE__) . '/mail.html');
    $mail_txt_template = file_get_contents(dirname(__FILE__) . '/mail.txt');

    // replace {{var}} to data
    $html_message = str_replace(
        array('{{var:subject}}', '{{var:connection_string}}'),
        array($subject, $vpn_config['client']['connection_string']),
        $mail_html_template
    );

    $alt_message = str_replace(
        array('{{var:subject}}', '{{var:connection_string}}'),
        array($subject, $vpn_config['client']['connection_string']),
        $mail_txt_template
    );


    // $sent_html = wp_mail($to_email, $subject, $html_message, ['Content-Type: text/html; charset=UTF-8', 'From: ' . $site_name . ' <service@tunnel1.website>']);
    // if (!$sent_html) {
    //     $last_error = error_get_last();
    //     $error_details = $last_error ? $last_error['message'] : 'Unknown error';

    //     log_vpn_action($order_id, 'email_failed', $error_details);

    //     return [
    //         'success' => false,
    //         'message' => 'Failed to send email. Error: ' . $error_details,
    //     ];
    // }

    $sent_alt = wp_mail($to_email, $subject, $alt_message, ['Content-Type: text/plain; charset=UTF-8']);
    if (!$sent_alt) {
        $last_error = error_get_last();
        $error_details = $last_error ? $last_error['message'] : 'Unknown error';

        log_vpn_action($order_id, 'email_failed', $error_details);

        return [
            'success' => false,
            'message' => 'Failed to send email. Error: ' . $error_details,
        ];
    }
    log_vpn_action($order_id, 'email_sent', 'HTML & plain-text VPN emails sent to ' . $to_email);

    return [
        'success' => true,
        'message' => 'Email sent successfully to ' . $to_email
    ];
}

/**
 * Resend VPN configuration with detailed response
 */
function resend_vpn_config($order_id)
{
    $order = wc_get_order($order_id);

    if (!$order) {
        log_vpn_action($order_id, 'resend_order_not_found', 'Attempted to resend VPN config but order not found.');
        return array(
            'success' => false,
            'error' => 'order_not_found',
            'message' => 'Order not found. Please check the order ID.'
        );
    }

    $vpn_config = get_post_meta($order_id, '_vpn_config', true);

    if ($vpn_config && isset($vpn_config['connection_string'])) {
        $customer_email = $order->get_billing_email();

        if (empty($customer_email)) {
            update_post_meta(
                $order_id,
                '_vpn_last_error',
                array(
                    'source'    => 'resend_vpn_config',
                    'message'   => 'Customer email address is not available for this order.',
                    'error'     => 'no_email',
                    'timestamp' => current_time('mysql'),
                )
            );
            log_vpn_action($order_id, 'resend_no_email', 'No customer email to resend VPN config.');
            return array(
                'success' => false,
                'error' => 'no_email',
                'message' => 'Customer email address is not available for this order.'
            );
        }

        $email_result = send_vpn_config_email($customer_email, $vpn_config, $order);

        if ($email_result['success']) {
            update_post_meta($order_id, '_vpn_config_resent', current_time('mysql'));
            delete_post_meta($order_id, '_vpn_last_error');
            $order->add_order_note('VPN configuration resent to customer: ' . $customer_email);
            log_vpn_action($order_id, 'resend_success', 'VPN configuration resent to ' . $customer_email);

            return array(
                'success' => true,
                'message' => 'VPN configuration resent successfully to ' . $customer_email,
                'email' => $customer_email
            );
        } else {
            update_post_meta(
                $order_id,
                '_vpn_last_error',
                array(
                    'source'    => 'resend_vpn_config',
                    'message'   => $email_result['message'],
                    'error'     => 'email_failed',
                    'timestamp' => current_time('mysql'),
                    'raw'       => $email_result,
                )
            );
            log_vpn_action($order_id, 'resend_email_failed', $email_result['message']);
            return array(
                'success' => false,
                'error' => 'email_failed',
                'message' => 'Failed to resend email: ' . $email_result['message'],
                'details' => $email_result
            );
        }
    } else {
        $result = process_vpn_for_order($order_id, 'success');

        if ($result['success']) {
            delete_post_meta($order_id, '_vpn_last_error');
            log_vpn_action($order_id, 'resend_created_new', 'New VPN configuration created & sent during resend.');
            return array(
                'success' => true,
                'message' => 'New VPN configuration created and sent successfully.',
                'action' => 'created_new'
            );
        } else {
            update_post_meta(
                $order_id,
                '_vpn_last_error',
                array(
                    'source'    => 'resend_vpn_config',
                    'message'   => isset($result['message']) ? $result['message'] : 'Unknown error',
                    'error'     => isset($result['error']) ? $result['error'] : 'unknown',
                    'timestamp' => current_time('mysql'),
                    'raw'       => $result,
                )
            );
            log_vpn_action($order_id, 'resend_create_failed', isset($result['message']) ? $result['message'] : 'Unknown error');
            return array(
                'success' => false,
                'error' => $result['error'] ?? 'unknown',
                'message' => 'Failed to create VPN configuration: ' . ($result['message'] ?? 'Unknown error'),
                'details' => $result
            );
        }
    }
}

/**
 * Add resend VPN button in admin
 */
add_action('woocommerce_admin_order_data_after_billing_address', 'add_resend_vpn_button');
function add_resend_vpn_button($order)
{
    $order_id = $order->get_id();
    $config_sent = get_post_meta($order_id, '_vpn_config_sent', true);
    $config_resent = get_post_meta($order_id, '_vpn_config_resent', true);
    $vpn_config = get_post_meta($order_id, '_vpn_config', true);
    $last_error = get_post_meta($order_id, '_vpn_last_error', true);

    echo '<div class="order_data_column" style="width:100%;">';
    echo '<h3>VPN Configuration</h3>';
    echo '<div class="address">';

    if ($config_sent) {
        echo '<p><strong>Status:</strong> <span style="color:green;">✓ Sent: ' . $config_sent . '</span></p>';
        if ($config_resent) {
            echo '<p><strong>Last Resent:</strong> ' . $config_resent . '</p>';
        }

        if ($vpn_config && isset($vpn_config['connection_string'])) {
            echo '<p><strong>Expires:</strong> ' . (isset($vpn_config['expires_at']) ?
                date_i18n(get_option('date_format'), strtotime($vpn_config['expires_at'])) : 'N/A') . '</p>';
            echo '<p><strong>Data Limit:</strong> ' . (isset($vpn_config['total_gb']) ?
                $vpn_config['total_gb'] . ' GB' : 'Unlimited') . '</p>';
            echo '<p><strong>Config String (short):</strong><br><code style="white-space: pre-wrap;">' .
                esc_html(wp_trim_words($vpn_config['connection_string'], 12, '…')) . '</code></p>';
        }

        echo '<p><a href="' . admin_url('admin-ajax.php?action=resend_vpn_config&order_id=' . $order_id . '&nonce=' . wp_create_nonce('resend_vpn_' . $order_id)) .
            '" class="button button-small resend-vpn-btn" data-order-id="' . $order_id . '">Resend VPN Config</a></p>';
    } else {
        echo '<p><strong>Status:</strong> <span style="color:red;">Not sent</span></p>';
        echo '<p><a href="' . admin_url('admin-ajax.php?action=resend_vpn_config&order_id=' . $order_id . '&nonce=' . wp_create_nonce('resend_vpn_' . $order_id)) .
            '" class="button button-primary button-small resend-vpn-btn" data-order-id="' . $order_id . '">Send VPN Config</a></p>';
    }

    if (!empty($last_error) && is_array($last_error)) {
        echo '<div style="margin-top:10px;padding:10px;border:1px solid #f0ad4e;background:#fcf8e3;border-radius:4px;">';
        echo '<p><strong>Last VPN Error:</strong></p>';
        if (!empty($last_error['timestamp'])) {
            echo '<p><strong>Time:</strong> ' . esc_html($last_error['timestamp']) . '</p>';
        }
        if (!empty($last_error['source'])) {
            echo '<p><strong>Source:</strong> ' . esc_html($last_error['source']) . '</p>';
        }
        if (!empty($last_error['error'])) {
            echo '<p><strong>Code:</strong> ' . esc_html($last_error['error']) . '</p>';
        }
        if (!empty($last_error['message'])) {
            echo '<p><strong>Message:</strong><br>' . esc_html($last_error['message']) . '</p>';
        }
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';

    // Add JavaScript for AJAX response handling
    echo '<script>
    jQuery(document).ready(function($) {
        $(".resend-vpn-btn").on("click", function(e) {
            e.preventDefault();
            var $btn = $(this);
            var orderId = $btn.data("order-id");
            var originalText = $btn.text();
            
            $btn.text("Processing...").prop("disabled", true);
            
            $.ajax({
                url: $btn.attr("href"),
                type: "GET",
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        alert("Success: " + response.data.message);
                        location.reload();
                    } else {
                        alert("Error: " + response.data.message + (response.data.details ? "\\nDetails: " + JSON.stringify(response.data.details) : ""));
                    }
                },
                error: function(xhr, status, error) {
                    alert("AJAX Error: " + error + "\\nStatus: " + status + "\\nResponse: " + xhr.responseText);
                },
                complete: function() {
                    $btn.text(originalText).prop("disabled", false);
                }
            });
        });
    });
    </script>';
}

/**
 * Add information about VPN config to order (admin)
 */
add_action('woocommerce_admin_order_data_after_shipping_address', 'display_vpn_config_in_admin_order');
function display_vpn_config_in_admin_order($order)
{
    $order_id = $order->get_id();
    $vpn_connection_string = get_post_meta($order_id, '_vpn_connection_string', true);
    $vpn_expires_at = get_post_meta($order_id, '_vpn_expires_at', true);
    // $config_sent = get_post_meta($order_id, '_vpn_config_sent', true);

    // Показываем конфиг только если он был сгенерирован и отправлен
    if ($vpn_connection_string && $vpn_expires_at) {
        echo '
        <p class="form-field form-field-wide">
            <h4>VPN Configuration String:</h4>
            <code style="word-wrap: break-word; padding: 0;">' . esc_html($vpn_connection_string) . '</code>
        </p>';

        echo '
        <p class="form-field form-field-wide">
            <h4>Expires at:</h4>
            <code style="word-wrap: break-word; padding: 0;">' . esc_html($vpn_expires_at) . '</code>;
        </p>';
    }
}

/**
 * AJAX handler for resending VPN config with improved error reporting
 */
add_action('wp_ajax_resend_vpn_config', 'ajax_resend_vpn_config');
function ajax_resend_vpn_config()
{
    // Verify nonce and sanitize input
    $order_id_raw = isset($_GET['order_id']) ? wp_unslash($_GET['order_id']) : '';
    $nonce = isset($_GET['nonce']) ? wp_unslash($_GET['nonce']) : '';

    if (empty($order_id_raw) || !is_numeric($order_id_raw)) {
        wp_send_json_error(array(
            'message' => 'Order ID is required and must be numeric.',
            'error_code' => 'invalid_order_id',
            'suggestion' => 'Please provide a valid order ID.'
        ));
    }

    $order_id = (int) $order_id_raw;

    if (empty($nonce) || !wp_verify_nonce($nonce, 'resend_vpn_' . $order_id)) {
        wp_send_json_error(array(
            'message' => 'Security check failed. Invalid or missing nonce.',
            'error_code' => 'invalid_nonce',
            'suggestion' => 'Please refresh the page and try again.'
        ));
    }

    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(array(
            'message' => 'Permission denied. You do not have sufficient permissions to perform this action.',
            'error_code' => 'insufficient_permissions',
            'required_capability' => 'manage_woocommerce',
            'suggestion' => 'Contact your administrator for access.'
        ));
    }

    $result = resend_vpn_config($order_id);

    if ($result['success']) {
        wp_send_json_success(array(
            'message' => $result['message'],
            'action' => isset($result['action']) ? $result['action'] : 'resent',
            'email' => isset($result['email']) ? $result['email'] : null,
            'timestamp' => current_time('mysql')
        ));
    } else {
        wp_send_json_error(array(
            'message' => $result['message'],
            'error_code' => $result['error'] ?? 'unknown_error',
            'details' => isset($result['details']) ? $result['details'] : null,
            'order_id' => $order_id,
            'suggestion' => 'Please check the order details and try again. If the problem persists, contact technical support.',
            'debug_info' => array(
                'order_exists' => !empty(wc_get_order($order_id)),
                'has_vpn_config' => !empty(get_post_meta($order_id, '_vpn_config', true)),
                'config_sent' => get_post_meta($order_id, '_vpn_config_sent', true)
            )
        ));
    }
}

/**
 * Log VPN actions for debugging
 */
function log_vpn_action($order_id, $action, $details = '')
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

<?php

/**
 * Plugin Name: WooCommerce - Платежный модуль Enot
 * Plugin URI: https://enot.io/
 * Description: Добавляет в WooCommerce возможность приёма платежей через платежную систему Enot
 * Author: @ladokk
 * Version: 1.1
 */

define('THIS_PLUGIN_DIR', dirname(__FILE__));

add_action('plugins_loaded', 'init_woo_enot', 0);
add_action('plugins_loaded', 'start_session', 1);
add_filter('plugin_row_meta', 'enot_add_plugin_row_meta', 10, 2);

function enot_add_plugin_row_meta($meta, $file)
{
    if ($file == plugin_basename(__FILE__))
        $meta[] = '<a href="https://docs.enot.io/" target="_blank">API Docs</a>';
    return $meta;
}

function init_woo_enot()
{
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_Gateway_Enot extends WC_Payment_Gateway
    {

        function __construct()
        {
            $this->id = 'enot';
            $this->method_title = 'Enot';
            $this->method_description = 'Добавляет в WooCommerce возможность приёма платежей через платежную систему Enot';

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            if (!empty(WC()->cart))
                $this->description = $this->getDescription();

            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
            } else {
                add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
            }

            add_action('woocommerce_api_wc_gateway_enot', array(&$this, 'callback'));
        }

        function getDescription()
        {
            if (!$this->get_option("switch_list")) return '';

            require_once __DIR__ . '/wooenotMerchantApi.php';
            $enot = new EnotMerchantAPI(
                $this->get_option('secret_word'),
            );

            $request = $enot->buildQuery('shops/' . $this->get_option('merchant_id') . '/payment-tariffs', array());
            if (!$request) {
                return 'Ошибка получения методов оплаты.';
            }

            $data = json_decode($request, true);

            $amount = WC()->cart->get_cart_contents_total();
            $currency = get_woocommerce_currency();
            $method_list = '';

            foreach ($data['data']['tariffs'] as $key => $item)
                if ($item['status'] == "enabled" && $item['min_sum'] <= $amount && ($item['service'] != 'card' || $item['currency'] == $currency)) {
                    $commi = $item['percent'] * $item['user_percent'] / 100;
                    $checked = (!empty($_SESSION['tariff']) && $item['service'] == $_SESSION['tariff']) ? 'checked' : '';
                    $method_list .= '<div class="form-group">
						<div class="form-check">
							<input class="form-check-input pay"' . $checked . ' type="radio" name="tariff" value="' . $item['service'] . '" id="' . $item['service'] . '">
							<label class="form-check-label" for="' . $item['service'] . '">
								<b>' . $item['service_label'] . '</b> (Валюта: ' . $item['currency'] . ', Комиссия: ' . $commi . ' %)
							</label>
						</div>
					</div>';
                }

            $output = '<style>
			.pay-header {
				font-weight: 700;
				font-size: 16px;
			}
			.pay-desc {
				font-size: 12px;
			}
			.pay-form {
				border: 1px solid #000;
				padding: 5px;
			}
			.form-check-input.pay {
				margin-left: 3px !important;
			}
			.form-check
			{
				display: flex;
			}
			</style>';

            $output .= '<div class="pay-form">' . $method_list . '</div>';

            $output .=
                '<script language="JavaScript">
			var ajax_url = \'/wp-admin/admin-ajax.php\';
			jQuery(document).ready(function($) {
			$(\'body\').on(\'click\',\'input[name=tariff]\', function(){
				console.log(\'yescliuck\');
				$.ajax({
				url: ajax_url+`?action=perform_ajax_query`,
				type: \'POST\',
				data: $(\'input[name=tariff]:checked\'),
				success: function(response) {
					console.log(response);
				},
				error: function(jqXHR, textStatus, errorThrown) {
					alert("Error: " + errorThrown);
				}
				});
			});})
			</script>';

            return $output;
        }

        function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'woocommerce'),
                    'type' => 'checkbox',
                    'default' => 'yes',
                ),
                'title' => array(
                    'title' => __('Title', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                    'default' => 'Enot',
                    'desc_tip' => true,
                ),
                'merchant_id' => array(
                    'title' => 'ID магазина',
                    'type' => 'text',
                ),
                'secret_word' => array(
                    'title' => 'Секретный пароль',
                    'type' => 'text',
                ),
                'secret_word2' => array(
                    'title' => 'Дополнительный ключ',
                    'type' => 'text',
                ),
                'switch_list' => array(
                    'title' => 'Загружать список способов оплаты при оформлении заказа?',
                    'type' => 'select',
                    'options' => array(
                        '1' => 'Да',
                        '0' => 'Нет',
                    ),
                    'default' => '1',
                ),
            );
        }

        function process_payment($order_id)
        {
            global $woocommerce;
            $order = new WC_Order($order_id);

            $amount = $order->get_total();
            $amount = str_replace(',', '.', $amount);
            $amount = number_format($amount, 2, '.', '');

            $merchant_id = $this->get_option("merchant_id");

            $description = get_bloginfo('name') . ' – Order #' . $order_id;

            $currency = get_woocommerce_currency();

            $data = array(
                'order_id' => time() . '_' . $order_id,
                'amount' => $amount,
                'currency' => $currency,
                'shop_id' => $merchant_id,
                'comment' => $description,
            );
            if ($this->get_option("switch_list") && !empty($_SESSION['tariff'])) {
                $data['include_service'] = array($_SESSION['tariff']);
            }
            $success_url = isset($_SESSION['enot_success_url']) ? $_SESSION['enot_success_url'] : '';
            $fail_url = isset($_SESSION['enot_fail_url']) ? $_SESSION['enot_fail_url'] : '';
            if (!empty($success_url)) $data['success_url'] = $success_url;
            if (!empty($fail_url)) $data['fail_url'] = $fail_url;

            $url = $this->getPaymentUrl($data);

            $woocommerce->cart->empty_cart();

            return array('result' => 'success', 'redirect' => $url);
        }

        function getPaymentUrl($data)
        {
            require_once __DIR__ . '/wooenotMerchantApi.php';

            $enot = new EnotMerchantAPI(
                $this->get_option("secret_word"),
            );
            $request = $enot->buildQuery('invoice/create', $data, 'post');

            if (!$request)
                wp_die('Ошибка создания инвойса. Информация записана в логи ошибок', 'Payment Error', array(
                    'response' => 500,
                    'exit' => true,
                ));

            $request = json_decode($request, true);

            return $request['data']['url'];
        }

        function callback()
        {

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                file_put_contents(__DIR__ . '/wooenot_error.log', date('Y-m-d H:i:s') . ' - callback # enot: Invalid method.' . "\r\n", FILE_APPEND);
                http_response_code(405); // Method Not Allowed
                exit;
            }

            $signature_header = $_SERVER['HTTP_X_API_SHA256_SIGNATURE'] ?? '';

            if (!$signature_header) {
                file_put_contents(__DIR__ . '/wooenot_error.log', date('Y-m-d H:i:s') . ' - callback # enot: not signature.' . "\r\n", FILE_APPEND);
                exit;
            }

            $sign = $request_body = file_get_contents('php://input');
            file_put_contents(__DIR__ . '/wooenot_payment.log', date('Y-m-d H:i:s') . " - callback # enot: $request_body" . "\r\n", FILE_APPEND);

            $sign = json_decode($sign, true);
            ksort($sign);
            $sign = json_encode($sign);
            $secret_key = $this->get_option("secret_word2");
            $calculated_signature = hash_hmac('sha256', $sign, $secret_key);

            // Compare the calculated signature with the signature in the header
            if (!hash_equals($signature_header, $calculated_signature)) {
                file_put_contents(__DIR__ . '/wooenot_error.log', date('Y-m-d H:i:s') . ' - callback # enot: Invalid signature.' . "\r\n", FILE_APPEND);
                http_response_code(401); // Unauthorized
                exit;
            }
            $request_body = json_decode($request_body, true);

            $order_id = $request_body['order_id'] ?? 0;

            if (!$order_id) {
                file_put_contents(__DIR__ . '/wooenot_error.log', date('Y-m-d H:i:s') . ' - callback # enot: empty order_id.' . "\r\n", FILE_APPEND);
                http_response_code(406);
                exit;
            }

            $order_id = explode('_', $request_body['order_id']);
            $order_id = (int)$order_id[1] ?? 0;

            if (!$order_id) {
                file_put_contents(__DIR__ . '/wooenot_error.log', date('Y-m-d H:i:s') . ' - callback # enot: incorrect order_id.' . "\r\n", FILE_APPEND);
                http_response_code(406);
                exit;
            }

            $status = $request_body['status'] ?? 'unknown';

            if ($status != 'success') {
                file_put_contents(__DIR__ . '/wooenot_error.log', date('Y-m-d H:i:s') . " - callback # enot: unsuccessful status for order $order_id - $status.\r\n", FILE_APPEND);
                exit;
            }

            $order = new WC_Order($order_id);
            $order->payment_complete();
        }
    }
}

function perform_ajax_query()
{
    if (!empty($_POST['tariff'])) $_SESSION['tariff'] = $_POST['tariff'];
}

add_action('wp_ajax_perform_ajax_query', 'perform_ajax_query');
add_action('wp_ajax_nopriv_perform_ajax_query', 'perform_ajax_query');

function add_woo_enot($methods)
{
    $methods[] = 'WC_Gateway_Enot';
    return $methods;
}

function start_session()
{
    if (headers_sent() || PHP_SAPI === 'cli') return;
    if (session_id() === '') {
        @session_start();
    }
}

function end_session()
{
    session_destroy();
}

add_action('wp_logout', 'end_session');
add_action('wp_login', 'end_session');
add_action('end_session_action', 'end_session');

add_filter('woocommerce_payment_gateways', 'add_woo_enot');

// Добавляем ссылку на настройки в список плагинов
add_filter('plugin_action_links', function ($links, $file) {

    if ($file != plugin_basename(__FILE__)) {
        return $links;
    }

    $settings_link = sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=wc-settings&tab=checkout&section=enot'), 'Настройки');

    array_unshift($links, $settings_link);
    return $links;
}, 10, 2);

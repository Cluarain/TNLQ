<?php

// подключаем все файлы функций из папки
foreach (glob(get_template_directory() . '/additional-functions/*-functions.php') as $filename) {
    require_once $filename;
}



add_filter('cfw_get_billing_checkout_fields', 'remove_checkout_fields', 100);

function remove_checkout_fields($fields)
{
    echo "remove_checkout_fields";
    print_r($fields);

    unset($fields['billing_company']);
    unset($fields['billing_city']);
    unset($fields['billing_postcode']);
    unset($fields['billing_country']);
    unset($fields['billing_state']);
    unset($fields['billing_address_1']);
    unset($fields['billing_address_2']);

    print_r($fields);
    return $fields;
}

// Set billing address fields to not required
add_filter('woocommerce_checkout_fields', 'unrequire_checkout_fields');


// отключаем поиск в теме
function disable_search_filter($query, $error = true)
{
    if (is_search() && !is_admin()) {
        $query->is_search = false;
        $query->query_vars['s'] = false;
        $query->query['s'] = false;
        if ($error == true)
            $query->is_404 = true;
    }
}
add_action('parse_query', 'disable_search_filter');


// устанавливаем глобальные переменные после запуска темы
add_action('after_setup_theme', function () {
    $GLOBALS['client_ip'] = $_SERVER['REMOTE_ADDR'];
    $GLOBALS['client_status'] = 'Not in the Tunnel';

    $GLOBALS['why-tuneliqa_link'] = '#why-tuneliqa';
    $GLOBALS['pricing_link'] = '#pricing';
    $GLOBALS['privacy-matters_link'] = '#privacy-matters';
    $GLOBALS['faq_link'] = '#faq';
    $GLOBALS['contact_link'] = '#footer';


    $GLOBALS['telegram_link'] = 'https://t.me/tuneliqa';
    $GLOBALS['twitter_link'] = 'xxx';


    // добавляем поддержку title-tag
    add_theme_support('title-tag');
});

// печатаем глобальную переменную если такая существует
function print_global_var($var)
{
    if (isset($GLOBALS[$var])) {
        echo $GLOBALS[$var];
    }
    echo null; // Возвращаем null, если переменной нет
}

// поддержка таксономий для страниц
function add_categories_and_tags_to_pages()
{
    register_taxonomy_for_object_type('category', 'page');
    register_taxonomy_for_object_type('post_tag', 'page');
}
add_action('init', 'add_categories_and_tags_to_pages');


function add_meta_tag()
{
    //     <meta name="google" content="notranslate">
    echo '
    <!-- start My custom meta -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preload" href="/assets/fonts/IBM_Plex_Mono/IBMPlexMono-Regular.woff2" as="font" type="font/woff2" fetchpriority="high" crossorigin>
    <link rel="preload" href="/assets/fonts/IBM_Plex_Mono/IBMPlexMono-Bold.woff2" as="font" type="font/woff2" fetchpriority="high" crossorigin>
    <!-- / end My custom meta -->
    ';
}
add_action('wp_head', 'add_meta_tag');

// свои стили для админки
add_action('admin_head', function () {
    echo '
    <style>
        .yooanalytics-woocommerce-wc-product-page,
        .mgmlp-header,
        .ewd-ufaq-dashboard-new-upgrade-banner {
            display: none !important;
        }
    </style>';
}, 999);

function add_scripts_and_styles()
{
    $isMinify = '';
    // if (! WP_DEBUG) {
    //     $isMinify = '.min';
    // }

    // $main_css = get_relative_theme_file_uri('/assets/css/main' . $isMinify . '.css');

    $main_js = get_relative_theme_file_uri('/assets/js/main' . $isMinify . '.js');

    // wp_enqueue_style('main', getShortFileLink($main_css), [], getShortFileHash($main_css));
    wp_register_style('main', false);
    wp_add_inline_style('main', file_get_contents(__DIR__ . '/assets/css/main' . $isMinify . '.css'));
    wp_enqueue_style('main');

    // ['strategy' => 'async', 'in_footer' => true ]
    wp_enqueue_script('main', getShortFileLink($main_js), [], getShortFileHash($main_js), true);

    $typewriter_js = get_relative_theme_file_uri('/assets/js/typewriter.min.js');
    wp_enqueue_script('typewriter', getShortFileLink($typewriter_js), [], getShortFileHash($typewriter_js), ['strategy' => 'async', 'in_footer' => true]);
}
add_action('wp_enqueue_scripts', 'add_scripts_and_styles');


// свои стили для админки
// add_action('admin_enqueue_scripts', function () {
//     $main_css = get_relative_theme_file_uri('/assets/css/main.css');
//     wp_enqueue_style('main',  $main_css, [], getShortFileHash($main_css));
// });



// заменяем вывод вопрос-ответов у плагина на свой
add_filter(
    'ewd_ufaq_faq_output',
    function ($output, $faq) {
        ob_start();

        echo generatedFAQ_HTML($faq->faq->question, $faq->faq->answer);

        return ob_get_clean();
    },
    10,
    2
);

// удаляем только конкретные группы классов
function selective_body_class($classes)
{
    $clean_classes = [];

    foreach ($classes as $class) {
        // Пропускаем только нужные классы
        if (strpos($class, 'page-id-') === 0) continue;
        if (strpos($class, 'locale-') === 0) continue;
        if (strpos($class, 'wp-theme-') === 0) continue;
        if (strpos($class, 'page-template') === 0) continue;
        // if ($class === 'home') continue;

        $clean_classes[] = $class;
    }

    return $clean_classes;
}
add_filter('body_class', 'selective_body_class', 100);


add_action('wp_footer', function () {
    // Иконки для переключения темы
    echo '
    <svg display="none">
        <symbol id="svg-symbol-light" viewBox="0 0 24 24">
            <g stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <line x1="12" y1="17" x2="12" y2="20" transform="rotate(0,12,12)"></line>
                <line x1="12" y1="17" x2="12" y2="20" transform="rotate(45,12,12)"></line>
                <line x1="12" y1="17" x2="12" y2="20" transform="rotate(90,12,12)"></line>
                <line x1="12" y1="17" x2="12" y2="20" transform="rotate(135,12,12)"></line>
                <line x1="12" y1="17" x2="12" y2="20" transform="rotate(180,12,12)"></line>
                <line x1="12" y1="17" x2="12" y2="20" transform="rotate(225,12,12)"></line>
                <line x1="12" y1="17" x2="12" y2="20" transform="rotate(270,12,12)"></line>
                <line x1="12" y1="17" x2="12" y2="20" transform="rotate(315,12,12)"></line>
            </g>
            <circle fill="currentColor" cx="12" cy="12" r="5"></circle>
        </symbol>
        <symbol id="svg-symbol-dark" viewBox="0 0 24 24">
            <path fill="currentColor" d="M15.1,14.9c-3-0.5-5.5-3-6-6C8.8,7.1,9.1,5.4,9.9,4c0.4-0.8-0.4-1.7-1.2-1.4C4.6,4,1.8,7.9,2,12.5c0.2,5.1,4.4,9.3,9.5,9.5c4.5,0.2,8.5-2.6,9.9-6.6c0.3-0.8-0.6-1.7-1.4-1.2C18.6,14.9,16.9,15.2,15.1,14.9z"></path>
        </symbol>
    </svg>';

    $isMinify = '.min';
    // if (! WP_DEBUG) {
    //     $isMinify = '.min';
    // }
    $animations_css = get_relative_theme_file_uri('/assets/css/animations' . $isMinify . '.css');
    $animations_url = getShortFileLink($animations_css) . '?v=' . getShortFileHash($animations_css);;
    echo '
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var link = document.createElement("link");
            link.rel = "stylesheet";
            link.href = "' .  $animations_url . '";
            link.type = "text/css";
            document.head.appendChild(link);
        });
    </script>
    <noscript><link rel="stylesheet" href="' . $animations_url . '"></noscript>
    ';
});


function change_attachment_image_src($image, $attachment_id, $size, $icon)
{
    if (!$image) {
        return $image;
    }

    $upload_dir = wp_upload_dir();
    $base_upload_url = $upload_dir['baseurl'] . '/images/';

    // Проверяем, что изображение находится в нужной папке
    if (strpos($image[0], $base_upload_url) !== false) {
        // Заменяем абсолютный URL на относительный путь
        $image[0] = str_replace($upload_dir['baseurl'] . '/images', '/images', $image[0]);
    }

    return $image;
}
add_filter('wp_get_attachment_image_src', 'change_attachment_image_src', 10, 4);


function change_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id)
{
    $upload_dir = wp_upload_dir();
    $base_upload_url = $upload_dir['baseurl'] . '/images/';

    foreach ($sources as $width => $source) {
        if (strpos($source['url'], $base_upload_url) !== false) {
            $sources[$width]['url'] = str_replace($upload_dir['baseurl'] . '/images', '/images', $source['url']);
        }
    }

    return $sources;
}
add_filter('wp_calculate_image_srcset', 'change_image_srcset', 10, 5);


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
        $order_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
        $np_id = sanitize_text_field($_GET['NP_id']);

        $order = wc_get_order($order_id);

        if ($order && $order->get_order_key() === $order_key) {
            update_post_meta($order->get_id(), '_np_payment_id', $np_id);

            if ($order->has_status(array('processing', 'completed'))) {
                wp_redirect(add_query_arg(array(
                    'payment_result' => 'success',
                    'order_id' => $order->get_id(),
                    'customer_email' => urlencode($order->get_billing_email()),
                    'np_id' => $np_id
                ), site_url('/')));
                exit;
            } else {
                wp_redirect(add_query_arg(array(
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
        $order_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';

        $order = wc_get_order($order_id);

        if ($order && $order->get_order_key() === $order_key) {
            if ($order->has_status(array('processing', 'completed'))) {
                wp_redirect(add_query_arg(array(
                    'payment_result' => 'success',
                    'order_id' => $order->get_id(),
                    'customer_email' => urlencode($order->get_billing_email())
                ), site_url('/')));
                exit;
            } else {
                wp_redirect(add_query_arg(array(
                    'payment_result' => 'pending',
                    'order_id' => $order->get_id()
                ), site_url('/')));
                exit;
            }
        }
    }

    // 3. ОБРАБОТКА ОТМЕНЫ - СТАРЫЙ И НОВЫЙ ФОРМАТ
    if (isset($_GET['cancel_order']) && $_GET['cancel_order'] === 'true') {
        $order_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';

        // Пытаемся получить order_id
        if (isset($_GET['order_id'])) {
            $order_id = intval($_GET['order_id']);
        }

        if ($order_id && $order_key) {
            $order = wc_get_order($order_id);

            if ($order && $order->get_order_key() === $order_key) {
                $order->update_status('cancelled', __('Payment cancelled by customer.', 'woocommerce'));
                wp_redirect(add_query_arg(array(
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
        $order_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
        $order = wc_get_order($order_id);

        if ($order && $order->get_order_key() === $order_key) {
            $status = $order->get_status();
            wp_redirect(add_query_arg(array(
                'payment_result' => $status,
                'order_id' => $order->get_id(),
                'customer_email' => urlencode($order->get_billing_email()),
                'show_status' => 'true'
            ), site_url('/')));
            exit;
        }
    }
}

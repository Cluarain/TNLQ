<?php

// отключение автозамены в WP
remove_filter('the_title', 'wptexturize');
remove_filter('the_content', 'wptexturize');
remove_filter('comment_text', 'wptexturize');
remove_filter('the_excerpt', 'wptexturize');


// отключить обёртку у всех кастомных lazy блоков
// add_filter('lzb/block_render/allow_wrapper', '__return_false');

function disable_lzb_deprecated_notice($trigger, $hook = '')
{
    if ($hook === 'lzb/block_render/allow_wrapper') {
        return false;
    }
    return $trigger;
}
add_filter('deprecated_hook_trigger_error', 'disable_lzb_deprecated_notice', 10, 2);


// отключаем уведомление об обновлении версии WP
add_action('admin_menu', function () {
    remove_action('admin_notices', 'update_nag', 3);
});

// Удаляем домен из URL-адреса (делаем пути относительными)
function make_url_relative($src)
{
    return wp_make_link_relative($src);
}
add_filter('style_loader_src', 'make_url_relative', 10, 2);
add_filter('script_loader_src', 'make_url_relative', 10, 2);
add_filter('get_site_icon_url', 'make_url_relative');

// Отключение лишних стилей и скриптов
function wp_scripts_styles_dequeue()
{
    wp_deregister_style('classic-theme-styles');
    wp_deregister_style('wp-block-library-css');
    wp_deregister_style('wp-block-library-theme');
    wp_deregister_style('wp-blocks-style');
    wp_deregister_style('wp-block-heading');
    wp_deregister_style('wp-block-separator');
    wp_deregister_style('wp-block-paragraph');
    wp_deregister_style('wp-block-list');
    wp_deregister_style('wp-block-library');

    wp_deregister_style('wc-blocks-style');
    wp_deregister_style('affwp-forms');
    wp_deregister_style('woocommerce-layout');
    wp_deregister_style('woocommerce-smallscreen');
    wp_deregister_style('woocommerce-general');
    wp_deregister_style('woocommerce-inline');

    // НЕ ОТКЛЮЧАТЬ
    // wp_deregister_script('wc-jquery-cookie');
    // wp_deregister_script('wc-js-cookie');


    // jquery НЕОБХОДИМ ДЛЯ ПЛАГИНА affiliate-wp
    // wp_deregister_script('jquery');

    wp_deregister_style('global-styles');

    wp_deregister_script('woocommerce');
    wp_deregister_script('interactivity');
    // wp_deregister_script('wp-hooks');
    wp_deregister_script('jquery-effects-core');

    // Вместо жёсткого deregister для зависимостей, которые всё равно могут быть запрошены,
    // регистрируем пустые обработчики
    wp_deregister_script('jquery-migrate');
    wp_register_script('jquery-migrate', false, array(), '', true);

    wp_deregister_script('jquery-ui-menu');
    wp_register_script('jquery-ui-menu', false, array(), '', true);

    wp_deregister_script('jquery-ui-autocomplete');
    wp_register_script('jquery-ui-autocomplete', false, array(), '', true);

    wp_deregister_script('wc-jquery-blockui');
    wp_register_script('wc-jquery-blockui', false, array(), '', true);

    wp_deregister_script('wp-dom-ready');
    wp_register_script('wp-dom-ready', false, array(), '', true);

    wp_deregister_script('wp-i18n');
    wp_register_script('wp-i18n', false, array(), '', true);

    wp_deregister_script('wp-a11y');
    wp_register_script('wp-a11y', false, array('wp-dom-ready', 'wp-i18n'), '', true);

    if (current_user_can('update_core')) {
        return;
    }

    wp_deregister_style('dashicons');


    if (strpos(get_post_field('post_name'), 'affiliate-') == false) {
        wp_deregister_script('jquery-ui-core');
    }

    $wp_scripts = wp_scripts();

    // ========================================
    // 2. WooCommerce jquery.cookie (хендл: wc-jquery-cookie-js)
    // ========================================
    $cookie_handle = 'wc-jquery-cookie';
    if (isset($wp_scripts->registered[$cookie_handle])) {
        $cookie_reg = $wp_scripts->registered[$cookie_handle];

        wp_deregister_script($cookie_handle);
        wp_register_script(
            $cookie_handle,
            $cookie_reg->src ?: plugins_url('woocommerce/assets/js/jquery-cookie/jquery.cookie.min.js'),
            $cookie_reg->deps ?: array('jquery'),
            $cookie_reg->ver ?: '000',
            true // ← футер
        );
        wp_enqueue_script($cookie_handle);
    }

    // ДОЛЖНО БЫТЬ РЯДОМ С jquery-core
    // ========================================
    // 3. AffiliateWP tracking (хендл: affwp-tracking-js)
    // ========================================
    // $tracking_handle = 'affwp-tracking';
    // if (isset($wp_scripts->registered[$tracking_handle])) {
    //     $tracking_reg = $wp_scripts->registered[$tracking_handle];

    //     wp_deregister_script($tracking_handle);
    //     wp_register_script(
    //         $tracking_handle,
    //         $tracking_reg->src ?: plugins_url('affiliate-wp/assets/js/tracking.min.js'),
    //         $tracking_reg->deps ?: array('jquery'),
    //         $tracking_reg->ver ?: '2.31.2',
    //         true // ← футер
    //     );
    //     wp_enqueue_script($tracking_handle);
    // }
}
add_action('wp_enqueue_scripts', 'wp_scripts_styles_dequeue', 100);


// Отключение JavaScript ufaq
function disable_plugins_scripts()
{
    wp_deregister_script('ewd-ufaq-js');
}
add_action('wp_print_scripts', 'disable_plugins_scripts', 20);

// Отключение CSS ufaq
function disable_plugins_styles()
{
    wp_deregister_style('ewd-ufaq-css');
    wp_deregister_style('ewd-ufaq-rrssb');
    wp_deregister_style('ewd-ufaq-jquery-ui');
}
add_action('wp_print_styles', 'disable_plugins_styles', 20);

add_action('wp_footer', function () {
    wp_dequeue_style('core-block-supports');
});



// проверяет роль пользователя и скрывает панель для всех, кто не является администратором
add_action('after_setup_theme', function () {
    if (!current_user_can('manage_options')) {
        show_admin_bar(false);
    }
});


// add_action('template_redirect', function () {

//     if (is_feed() || get_query_var('sitemap'))
//         return;

//     $filters = array(
//         'post_link',
//         'post_type_link',
//         // 'page_link',
//         'attachment_link',
//         'get_shortlink',
//         'post_type_archive_link',
//         'get_pagenum_link',
//         'get_comments_pagenum_link',
//         'term_link',
//         'search_link',
//         'day_link',
//         'month_link',
//         'year_link'
//     );

//     foreach ($filters as $filter) {
//         add_filter($filter, 'wp_make_link_relative');
//     }
// });

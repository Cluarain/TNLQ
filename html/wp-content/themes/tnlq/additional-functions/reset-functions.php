<?php

// отключение автозамены в WP
remove_filter('the_title', 'wptexturize');
remove_filter('the_content', 'wptexturize');
remove_filter('comment_text', 'wptexturize');
remove_filter('the_excerpt', 'wptexturize');


// отключить обёртку у всех кастомных lazy блоков
if (! WP_DEBUG) {
    add_filter('lzb/block_render/allow_wrapper', '__return_false');
}


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


// Отключение jquery и лишних стилей для не админов
function wp_scripts_styles_dequeue()
{
    wp_deregister_style('classic-theme-styles');
    wp_deregister_script('interactivity');
    wp_deregister_style('wp-block-library-css');
    wp_deregister_style('wp-block-library-theme');
    wp_deregister_style('wp-blocks-style');
    wp_dequeue_style('wp-block-library');

    wp_dequeue_style('global-styles');
    if (current_user_can('update_core')) {
        return;
    }

    wp_deregister_style('dashicons');
    wp_deregister_script('jquery');
}
add_action('wp_enqueue_scripts', 'wp_scripts_styles_dequeue');

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

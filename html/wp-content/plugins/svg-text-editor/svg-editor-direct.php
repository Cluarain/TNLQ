<?php
/**
 * Plugin Name: SVG Inline Editor
 * Description: Добавляет возможность редактировать SVG файлы прямо в админке WordPress
 * Version: 1.1
 * Author: Cluarain
 */

// Безопасность
if (!defined('ABSPATH')) {
    exit;
}

class SVGInlineEditor {
    
    public function __construct() {
        add_action('admin_init', array($this, 'init'));
    }
    
    public function init() {
        // Проверяем, что мы в админке и работаем с SVG
        if (!is_admin()) {
            return;
        }
        
        // Добавляем скрипты и стили
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Добавляем кнопку редактирования
        add_filter('attachment_fields_to_edit', array($this, 'add_edit_button'), 10, 2);
        
        // Обрабатываем AJAX запросы
        add_action('wp_ajax_save_svg_content', array($this, 'save_svg_content'));
        add_action('wp_ajax_get_svg_content', array($this, 'get_svg_content'));
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'upload.php' && $hook !== 'post.php') {
            return;
        }
        
        wp_enqueue_script(
            'svg-editor-js',
            plugin_dir_url(__FILE__) . 'svg-editor.js',
            array('jquery'),
            '1.1',
            true
        );
        
        wp_enqueue_style(
            'svg-editor-css',
            plugin_dir_url(__FILE__) . 'svg-editor.css',
            array(),
            '1.1'
        );
        
        // Передаем данные в JavaScript
        wp_localize_script('svg-editor-js', 'svg_editor', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('svg_editor_nonce')
        ));
    }
    
    public function add_edit_button($form_fields, $post) {
        // Проверяем, что это SVG файл
        if ($post->post_mime_type !== 'image/svg+xml') {
            return $form_fields;
        }
        
        $edit_url = '#';
        $form_fields['svg_edit_button'] = array(
            'label' => '',
            'input' => 'html',
            'html' => '<button type="button" class="button svg-edit-button" data-attachment-id="' . $post->ID . '">Edit SVG</button>'
        );
        
        return $form_fields;
    }
    
    public function get_svg_content() {
        // Проверяем nonce для безопасности
        check_ajax_referer('svg_editor_nonce', 'nonce');
        
        $attachment_id = intval($_POST['attachment_id']);
        
        if (!$attachment_id) {
            wp_die('Invalid attachment ID');
        }
        
        // Проверяем права пользователя
        if (!current_user_can('edit_post', $attachment_id)) {
            wp_die('Insufficient permissions');
        }
        
        $file_path = get_attached_file($attachment_id);
        
        if (!file_exists($file_path)) {
            wp_die('File not found');
        }
        
        $content = file_get_contents($file_path);
        
        wp_send_json_success(array(
            'content' => $content
        ));
    }
    
    public function save_svg_content() {
        // Проверяем nonce для безопасности
        check_ajax_referer('svg_editor_nonce', 'nonce');
        
        $attachment_id = intval($_POST['attachment_id']);
        $new_content = stripslashes($_POST['content']);
        
        if (!$attachment_id) {
            wp_die('Invalid attachment ID');
        }
        
        // Проверяем права пользователя
        if (!current_user_can('edit_post', $attachment_id)) {
            wp_die('Insufficient permissions');
        }
        
        $file_path = get_attached_file($attachment_id);
        
        if (!file_exists($file_path)) {
            wp_die('File not found');
        }
        
        // Сохраняем изменения в файл
        $result = file_put_contents($file_path, $new_content);
        
        if ($result === false) {
            wp_send_json_error('Failed to save file');
        } else {
            // Очищаем кеш изображения
            wp_cache_delete($attachment_id, 'post');
            wp_send_json_success('File saved successfully');
        }
    }
}

new SVGInlineEditor();
?>
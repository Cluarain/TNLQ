<?php

function add_breadcrumbs_auto()
{
  if (function_exists('yoast_breadcrumb') && !is_front_page()) {
    yoast_breadcrumb('<div class="breadcrumbs container">', '</div>');
  }
}



/**
 * Получает реальный IP-адрес пользователя (IPv4 или IPv6) за Cloudflare.
 *
 * @return string|false IP-адрес или false, если не удалось определить.
 */
function get_real_user_ip()
{
  // 1. Приоритет у специального заголовка Cloudflare
  // Именно он содержит реальный IP посетителя, прошедшего через их сеть
  // if (! empty($_SERVER['HTTP_CF_PSEUDO_IPV4'])) {
  //   $ip = $_SERVER['HTTP_CF_PSEUDO_IPV4'];
  // } 
  if (! empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
  }
  // 2. Если сайт не за Cloudflare, но за другим прокси (проверяем X-Forwarded-For)
  elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    // В этом заголовке может быть список IP. Берем самый первый (он самый удаленный).
    $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim($ip_list[0]);
  }
  // 3. Стандартный способ (без прокси)
  elseif (! empty($_SERVER['REMOTE_ADDR'])) {
    $ip = $_SERVER['REMOTE_ADDR'];
  } else {
    return false;
  }

  // Дополнительная очистка (для безопасности)
  $ip = sanitize_text_field(wp_unslash($ip));

  // Валидация: проверяем, что это действительно валидный IP (v4 или v6)
  // filter_var с FILTER_VALIDATE_IP поддерживает оба формата
  if (filter_var($ip, FILTER_VALIDATE_IP)) {
    return $ip;
  }

  // Если валидация не прошла, возвращаем REMOTE_ADDR как крайнюю меру
  return ! empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
}


function check_if_in_tunnel($client_ip)
{
  $node_ips_file = '/opt/server_scripts/node_ips.txt';
  $in_tunnel = false;
  // Финальная проверка файла
  if (file_exists($node_ips_file)) {
    $node_ips = file($node_ips_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (in_array($client_ip, $node_ips)) {
      $in_tunnel = true;
    }
  }
  return $in_tunnel;
}

// function get_relative_theme_file_uri($path = '')
// {
//   // Получаем полный URI до файла темы
//   $full_path = get_theme_file_uri($path);

//   // Убираем базовую часть пути до темы
//   $theme_directory_uri = get_theme_file_uri(); // Это URI корня темы
//   $short_path = str_replace($theme_directory_uri, '', $full_path);

//   // Делаем путь относительным (убираем протокол/домен, если нужно)
//   $short_path = wp_make_link_relative($short_path);

//   return $short_path;
// }

// function getShortFileHash(string $filePath): string
// {
//   $filePath = ABSPATH . wp_make_link_relative(get_theme_file_uri()) . $filePath;
//   if (!file_exists($filePath)) {
//     return 0;
//   }
//   $hash = hash_file('sha1', $filePath);
//   return substr($hash, 0, 6);
// }

function getShortFileLink($path = '')
{
  // на входе путь вида /wp-content/themes/tnlq/assets/css/animations.css
  // получается /assets/css/animations.css
  return preg_replace('|^/wp-content/themes/[^/]+/|', '/', $path);
}

function getShortFileHash(string $filePath): string
{
  $filePath = ABSPATH . $filePath;
  if (!file_exists($filePath)) {
    return 0;
  }
  $hash = hash_file('sha1', $filePath);
  return substr($hash, 0, 6);
}


function generatedFAQ_HTML($question, $answer)
{
  return <<<HTML
    <div class="accordion">
      <details class="accordion-details">
        <summary class="accordion-summary">
          <span class="accordion-title" role="term">{$question}</span>
          <span class="accordion-arrow"></span>
        </summary>
      </details>
      <div class="accordion-content" role="definition">
        <div class="accordion-overflow">
          <div class="accordion-body">
            <div class="special-symbols">
              {$answer}
            </div>
          </div>
        </div>
      </div>
    </div>
    HTML;
}


function get_attachment_image_ID_by_name($attachment_name)
{
  if (!$attachment_name) {
    return null;
  }
  $attachment = get_posts([
    'post_type'      => 'attachment',
    'name'           => $attachment_name,
    'post_status'    => 'inherit',
    'numberposts'    => 1,
    'fields'         => 'id',
  ]);
  if ($attachment) {
    return $attachment[0]->ID;
  } else {
    return null;
  }
}

function get_attachment_image_by_name($attachment_name, $size = 'full', $icon = false, $attributes = [])
{
  if (!$attachment_name) {
    return null;
  }

  // Кэшируем результат на день (уменьшаем нагрузку)
  $cache_key = 'attachment_image_by_name__' . $attachment_name;
  $cached = get_transient($cache_key);
  if ($cached !== false) {
    return $cached;
  }

  // Получаем ID вложения по названию
  $attachment = get_posts([
    'post_type'      => 'attachment',
    'name'           => $attachment_name,
    'post_status'    => 'inherit',
    'numberposts'    => 1,
    'fields'         => 'id', // Оптимизация: получаем только ID
  ]);

  if ($attachment) {
    $attachment_id = $attachment[0]->ID;

    // Устанавливаем значения по умолчанию
    $default_attrs = [
      'fetchpriority' => '',
      'lazyload' => true,
      'svg-inline' => false,
    ];

    // Объединяем переданные атрибуты с значениями по умолчанию
    $attributes = array_merge($default_attrs, $attributes);

    // Извлекаем наши специальные параметры
    $fetchpriority = $attributes['fetchpriority'];
    $lazyload = $attributes['lazyload'];


    // Добавляем fetchpriority если задан
    if (!empty($fetchpriority)) {
      $attributes['fetchpriority'] = $fetchpriority;
    }

    // Добавляем loading lazy если не отключен
    if ($lazyload) {
      $attributes['loading'] = 'lazy';
    }

    if ($attributes['svg-inline']) {
      $mime_type = get_post_mime_type($attachment_id);
      if ($mime_type === 'image/svg+xml') {
        return get_svg_inline_by_attachmentID($attachment_id);
      }
    }
    // Удаляем их из массива атрибутов, чтобы не передавать в wp_get_attachment_image
    unset($attributes['fetchpriority'], $attributes['lazyload'], $attributes['svg-inline']);
    $attachment_image = wp_get_attachment_image($attachment_id, $size, $icon, $attributes);
    // Сохраняем в кэш на 24 часа
    set_transient($cache_key, $attachment_image, DAY_IN_SECONDS);
    return $attachment_image;
  } else {
    return null;
  }
}

function get_svg_inline_by_attachmentID($attachmentID)
{
  // Кэшируем результат на день (уменьшаем нагрузку)
  $cache_key = 'svg_inline_' . $attachmentID;
  $cached = get_transient($cache_key);
  if ($cached !== false) {
    return $cached;
  }

  // Получаем абсолютный путь к файлу на сервере
  $file_path = get_attached_file($attachmentID);
  if (!$file_path || !file_exists($file_path)) {
    // Логируем ошибку, возвращаем пустую строку
    error_log("SVG file not found for attachment ID: $attachmentID");
    return '';
  }

  // Читаем файл напрямую с файловой системы (быстро, без HTTP)
  $svg = file_get_contents($file_path);
  if ($svg === false) {
    error_log("Failed to read SVG file: $file_path");
    return '';
  }

  // Далее обработка (удаление XML-декларации, добавление viewBox, title)
  $svg = trim(preg_replace('/<\?xml[^>]+\?>/', '', $svg));

  if (strpos($svg, 'viewBox=') === false) {
    $width_pattern = '/width=("|\')([0-9.]+)("|\')/';
    $height_pattern = '/height=("|\')([0-9.]+)("|\')/';

    $width = null;
    $height = null;

    if (preg_match($width_pattern, $svg, $width_matches)) {
      $width = $width_matches[2];
    }
    if (preg_match($height_pattern, $svg, $height_matches)) {
      $height = $height_matches[2];
    }

    if ($width && $height) {
      $viewbox = 'viewBox="0 0 ' . $width . ' ' . $height . '"';
      $svg = preg_replace('/<svg([^>]*)>/', '<svg$1 ' . $viewbox . '>', $svg);
    }
  }

  if (strpos($svg, '<title>') === false) {
    $alt_text = get_post_meta($attachmentID, '_wp_attachment_image_alt', true);
    if (!empty($alt_text)) {
      $svg = preg_replace(
        '/(<svg[^>]*>)(.*?)(<\/svg>)/s',
        '$1<title>' . esc_html($alt_text) . '</title>$2$3',
        $svg
      );
    }
  }

  // Сохраняем в кэш на 24 часа
  set_transient($cache_key, $svg, DAY_IN_SECONDS);

  return $svg;
}


function parse_args_filtered($args, $defaults)
{
  return (
    (isset($args) && is_array($args))
    ? wp_parse_args(
      array_filter($args, fn($v) => $v),
      $defaults
    )
    : $defaults
  );
}

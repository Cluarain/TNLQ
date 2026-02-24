<?php

function add_breadcrumbs_auto()
{
  if (function_exists('yoast_breadcrumb') && !is_front_page()) {
    yoast_breadcrumb('<div class="breadcrumbs container">', '</div>');
  }
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

function get_relative_theme_file_uri($path = '')
{
  return wp_make_link_relative(get_theme_file_uri($path));
}

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

    return wp_get_attachment_image($attachment_id, $size, $icon, $attributes);
  } else {
    return null;
  }
}

function get_svg_inline_by_attachmentID($attachmentID)
{
  $svg = file_get_contents(wp_get_attachment_url($attachmentID));

  // Удаляем XML декларацию если есть, чтобы избежать проблем
  $svg = trim(preg_replace('/<\?xml[^>]+\?>/', '', $svg));

  if (strpos($svg, 'viewBox=') === false) {
    // Extract width and height using regex
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

    // If both width and height are found, add viewBox
    if ($width && $height) {
      $viewbox = 'viewBox="0 0 ' . $width . ' ' . $height . '"';
      // Insert viewBox after the opening <svg tag
      $svg = preg_replace('/<svg([^>]*)>/', '<svg$1 ' . $viewbox . '>', $svg);
    }
  }

  if (strpos($svg, '<title>') === false) {
    $alt_text = get_post_meta($attachmentID, '_wp_attachment_image_alt', true);
    if (!empty($alt_text)) {
      // Вставляем title как первый дочерний элемент внутри svg
      $svg = preg_replace(
        '/(<svg[^>]*>)(.*?)(<\/svg>)/s',
        '$1<title>' . esc_html($alt_text) . '</title>$2$3',
        $svg
      );
    }
  }

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

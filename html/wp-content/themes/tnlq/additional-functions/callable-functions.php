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
            {$answer}
          </div>
        </div>
      </div>
    </div>
    HTML;
}

function get_attachment_image_by_name($attachment_name, $size = 'full', $icon = false, $fetchpriority = '', $lazyload = true, $attr = '')
{
  if (!$attachment_name) {
    return null;
  }

  // Получаем ID вложения по названию
  $attachment = get_posts(array(
    'post_type' => 'attachment',
    'name' => $attachment_name,
    'post_status' => 'inherit',
    'numberposts' => 1
  ));

  if ($attachment) {
    $attachment_id = $attachment[0]->ID;

    // Преобразуем атрибуты в массив для модификации
    $attributes = $attr;
    if (!is_array($attributes)) {
      $attributes = array();
    }

    // Добавляем fetchpriority если задан
    if (!empty($fetchpriority)) {
      $attributes['fetchpriority'] = $fetchpriority;
    }

    // Добавляем loading lazy если не отключен
    if ($lazyload) {
      $attributes['loading'] = 'lazy';
    }

    return wp_get_attachment_image($attachment_id, $size, $icon, $attributes);
  } else {
    return null;
  }
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

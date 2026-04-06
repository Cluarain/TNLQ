<?php
/**
 * Plugin Name: WebP Generator
 * Description: Генерирует WebP изображения и подключает их для поддерживающих браузеров
 * Version: 1.3
 */

class WebPGenerator {
    private $quality_options = [
        30 => 'Очень сжатое (30%)',
        60 => 'Сжатое (60%)',
        80 => 'Нормальное (80%)',
        90 => 'Почти без потерь (90%)',
        100 => 'Без потерь (100%)'
    ];

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('add_attachment', [$this, 'generate_webp']);
        add_filter('wp_get_attachment_image_attributes', [$this, 'replace_attributes'], 10, 3);
        add_filter('the_content', [$this, 'replace_content_images']);
        add_filter('wp_generate_attachment_metadata', [$this, 'regenerate_webp'], 10, 2);
        add_filter('wp_calculate_image_srcset', [$this, 'replace_srcset'], 10, 5);
    }

    public function register_settings() {
        register_setting('webp_settings', 'webp_quality');
        add_option('webp_quality', 80);
    }

    public function add_admin_menu() {
        add_options_page('Настройки WebP', 'WebP Generator', 'manage_options', 'webp-generator', [$this, 'show_settings']);
    }

    public function show_settings() {
        ?>
        <div class="wrap">
            <h1>Настройки WebP генератора</h1>
            <form method="post" action="options.php">
                <?php settings_fields('webp_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Качество сжатия</th>
                        <td>
                            <select name="webp_quality">
                                <?php foreach($this->quality_options as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php selected(get_option('webp_quality'), $value); ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function generate_webp($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        $quality = get_option('webp_quality', 80);
        
        if (!$this->is_supported_image($file_path)) {
            return;
        }

        // Генерируем WebP для основного файла
        $webp_path = $this->get_webp_path($file_path, $quality);
        $this->convert_to_webp($file_path, $webp_path, $quality);

        // Генерируем WebP для всех размеров
        $metadata = wp_get_attachment_metadata($attachment_id);
        if ($metadata && isset($metadata['sizes'])) {
            $upload_dir = wp_upload_dir();
            $file_dir = dirname($file_path);
            
            foreach ($metadata['sizes'] as $size) {
                $size_path = $file_dir . '/' . $size['file'];
                
                if (file_exists($size_path) && $this->is_supported_image($size_path)) {
                    $size_webp_path = $this->get_webp_path($size_path, $quality);
                    $this->convert_to_webp($size_path, $size_webp_path, $quality);
                }
            }
        }
    }

    public function regenerate_webp($metadata, $attachment_id) {
        $this->generate_webp($attachment_id);
        return $metadata;
    }

    private function is_supported_image($file_path) {
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png']);
    }

    private function is_supported_image_url($url) {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png']);
    }

    private function get_webp_path($original_path, $quality) {
        $dir = dirname($original_path);
        $filename = pathinfo($original_path, PATHINFO_FILENAME);
        $webp_dir = $dir . '/webp';
        
        if (!is_dir($webp_dir)) {
            wp_mkdir_p($webp_dir);
        }

        return trailingslashit($webp_dir) . "{$filename}-q{$quality}.webp";
    }

    private function convert_to_webp($source, $destination, $quality) {
        if (file_exists($destination)) {
            return true;
        }

        $image = null;
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));

        switch($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($source);
                break;
            case 'png':
                $image = imagecreatefrompng($source);
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
        }

        if ($image) {
            $result = imagewebp($image, $destination, $quality);
            imagedestroy($image);
            return $result;
        }

        return false;
    }

    public function replace_attributes($attr, $attachment, $size) {
        if (!$this->browser_supports_webp()) {
            return $attr;
        }

        if (isset($attr['src'])) {
            $webp_url = $this->get_webp_url($attr['src']);
            if ($webp_url) {
                $attr['src'] = $webp_url;
            }
        }

        return $attr;
    }

    public function replace_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
        if (!$this->browser_supports_webp() || empty($sources)) {
            return $sources;
        }

        foreach ($sources as $width => $source) {
            $webp_url = $this->get_webp_url($source['url']);
            if ($webp_url) {
                $sources[$width]['url'] = $webp_url;
            }
        }

        return $sources;
    }

    public function replace_content_images($content) {
        if (empty($content) || !$this->browser_supports_webp()) {
            return $content;
        }

        $content = preg_replace_callback('/<img[^>]+>/i', [$this, 'replace_img_tag'], $content);
        $content = preg_replace_callback('/<source[^>]+>/i', [$this, 'replace_source_tag'], $content);

        return $content;
    }

    private function replace_img_tag($matches) {
        $img_tag = $matches[0];

        // Заменяем src
        $img_tag = preg_replace_callback('/src=(["\'])([^"\']+)\1/i', function($matches) {
            if (!$this->is_supported_image_url($matches[2])) {
                return $matches[0];
            }
            
            $webp_url = $this->get_webp_url($matches[2]);
            return $webp_url ? 'src=' . $matches[1] . $webp_url . $matches[1] : $matches[0];
        }, $img_tag);

        // Заменяем srcset
        $img_tag = preg_replace_callback('/srcset=(["\'])([^"\']+)\1/i', function($matches) {
            $srcset = $matches[2];
            $sources = explode(',', $srcset);
            $new_sources = [];

            foreach ($sources as $source) {
                $parts = array_map('trim', explode(' ', $source));
                if (!empty($parts[0]) && $this->is_supported_image_url($parts[0])) {
                    $webp_url = $this->get_webp_url($parts[0]);
                    if ($webp_url) {
                        $parts[0] = $webp_url;
                    }
                    $new_sources[] = implode(' ', $parts);
                } else {
                    $new_sources[] = $source;
                }
            }

            return 'srcset=' . $matches[1] . implode(', ', $new_sources) . $matches[1];
        }, $img_tag);

        return $img_tag;
    }

    private function replace_source_tag($matches) {
        $source_tag = $matches[0];

        // Заменяем srcset в source тегах
        $source_tag = preg_replace_callback('/srcset=(["\'])([^"\']+)\1/i', function($matches) {
            $srcset = $matches[2];
            $sources = explode(',', $srcset);
            $new_sources = [];

            foreach ($sources as $source) {
                $parts = array_map('trim', explode(' ', $source));
                if (!empty($parts[0]) && $this->is_supported_image_url($parts[0])) {
                    $webp_url = $this->get_webp_url($parts[0]);
                    if ($webp_url) {
                        $parts[0] = $webp_url;
                    }
                    $new_sources[] = implode(' ', $parts);
                } else {
                    $new_sources[] = $source;
                }
            }

            return 'srcset=' . $matches[1] . implode(', ', $new_sources) . $matches[1];
        }, $source_tag);

        return $source_tag;
    }

    private function browser_supports_webp() {
        if (!isset($_SERVER['HTTP_ACCEPT'])) {
            return false;
        }
        
        return strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;
    }

    private function get_webp_url($original_url) {
        // Проверяем, поддерживается ли изображение
        if (!$this->is_supported_image_url($original_url)) {
            return false;
        }

        $upload_dir = wp_upload_dir();
        $quality = get_option('webp_quality', 80);
        
        // Получаем локальный путь из URL
        $file_path = $this->url_to_path($original_url);
        if (!$file_path || !file_exists($file_path)) {
            return false;
        }

        // Получаем путь к WebP версии
        $webp_path = $this->get_webp_path($file_path, $quality);
        
        if (!file_exists($webp_path)) {
            return false;
        }

        // Конвертируем путь обратно в URL
        return $this->path_to_url($webp_path);
    }

    private function url_to_path($url) {
        $upload_dir = wp_upload_dir();
        
        // Пробуем разные варианты базовых URL
        $base_urls = [
            $upload_dir['baseurl'],
            home_url('/wp-content/uploads'),
            site_url('/wp-content/uploads')
        ];
        
        foreach ($base_urls as $base_url) {
            if (strpos($url, $base_url) !== false) {
                return str_replace($base_url, $upload_dir['basedir'], $url);
            }
        }
        
        return false;
    }

    private function path_to_url($path) {
        $upload_dir = wp_upload_dir();
        
        if (strpos($path, $upload_dir['basedir']) !== false) {
            return str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $path);
        }
        
        return false;
    }
}

new WebPGenerator();
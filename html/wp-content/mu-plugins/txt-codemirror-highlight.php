<?php

/**
 * Plugin Name: TXT Syntax Highlighter for Theme Editor
 * Description: Подсветка {{var:word}} в .txt файлах через CodeMirror
 * Version: 0.0.2
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('admin_enqueue_scripts', 'my_txt_codemirror_init');

function my_txt_codemirror_init($hook)
{
    if ('theme-editor.php' !== $hook) {
        return;
    }

    $file = isset($_GET['file']) ? sanitize_file_name(wp_unslash($_GET['file'])) : '';
    if (! $file || 'txt' !== strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
        return;
    }

    add_action('admin_footer', function () {
?>
        <style type="text/css">
            .cm-mytpl-variable {
                color: #e83e8c !important;
                font-weight: 600 !important;
            }

            .cm-mytpl-keyword {
                color: #d73a49 !important;
                font-weight: 500 !important;
            }

            .cm-mytpl-function {
                color: #0366d6 !important;
                font-weight: 600 !important;
            }
        </style>
        <script>
            window.addEventListener('load', function() {
                // Ждем еще 2 секунды после загрузки страницы, как просили
                setTimeout(function() {
                    var codeBlock = document.querySelector('.CodeMirror-code');
                    if (!codeBlock) return;

                    // Проходимся по всем строкам (линиям) кода
                    var lines = codeBlock.querySelectorAll('.CodeMirror-line');

                    for (var i = 0; i < lines.length; i++) {
                        var line = lines[i];

                        // Чтобы не сломать верстку повторным вызовом, проверяем, есть ли уже наши классы
                        if (line.innerHTML.indexOf('cm-mytpl-variable') !== -1) {
                            continue;
                        }

                        var html = line.innerHTML;

                        // "Тупая" замена по регулярке: ищем {{var:word}} и заворачиваем в span
                        // Модификатор 'g' (global) нужен, чтобы заменить все вхождения в строке
                        var newHtml = html.replace(/(\{\{var:[a-zA-Z_][a-zA-Z0-9_]*\}\})/g, '<span class="cm-mytpl-variable">$1</span>');

                        // Если что-то нашли и заменили, обновляем HTML строки
                        if (html !== newHtml) {
                            line.innerHTML = newHtml;
                        }
                    }
                }, 100);
            });
        </script>
<?php
    });
}

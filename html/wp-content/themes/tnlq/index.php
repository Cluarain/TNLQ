<!DOCTYPE html>
<html <?php language_attributes() ?>>

<head>
    <?php wp_head() ?>
</head>

<body <?php body_class() ?>>
    <?php get_header() ?>
    <main id="main" class="main" role="main">

        <?php
        // Получаем контент и парсим блоки
        $post_content = get_the_content();
        $blocks = parse_blocks($post_content);

        $main_content = '';
        $lazy_blocks = [];

        // Разделяем контент и Lazy Blocks
        foreach ($blocks as $block) {
            $block_name = $block['blockName'] ?? '';

            // Проверяем, является ли блок Lazy Block
            if ($block_name && substr($block_name, 0, 10) === 'lazyblock/') {
                $lazy_blocks[] = $block;
            } else {
                $main_content .= render_block($block);
            }
        }
        ?>

        <?php add_breadcrumbs_auto() ?>

        <article class="page">
            <div class="page__inner container">
                <div class="page__inner__wrapper padding-block-xxxl">
                    <h1 class="project__title page__title arrow-sign"><?php the_title() ?></h1>
                    <div class="left-padding margin-m page__inner__content">
                        <?php echo apply_filters('the_content', $main_content); ?>
                    </div>
                </div>
            </div>
        </article>


        <?php
        // Выводим Lazy Blocks после секции
        foreach ($lazy_blocks as $block) {
            echo render_block($block);
        }
        ?>
    </main>

    <?php
    // get_sidebar();
    get_footer();
    wp_footer();
    ?>
</body>

</html>
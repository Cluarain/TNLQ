<?php

/**
 * Template Name: Страница
 */
?>
<!DOCTYPE html>
<html <?php language_attributes() ?>>

<head>
    <?php wp_head() ?>
</head>

<body <?php body_class() ?>>
    <?php get_header() ?>
    <main id="main" class="main">
        <?php add_breadcrumbs_auto() ?>
        <?php the_content() ?>
    </main>
    <?php
    // get_sidebar();
    get_footer();
    wp_footer();
    ?>
</body>

</html>
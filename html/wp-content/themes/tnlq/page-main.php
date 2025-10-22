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
    <main id="main" class="main" role="main">

        <?php
        // get_template_part('templates/main-banner');
        // get_template_part('templates/why-tuneliqa');
        // get_template_part('templates/pricing');
        // get_template_part('templates/privacy-matters');


        the_content();
        ?>


    </main>
    <?php
    // get_sidebar();
    get_footer();
    wp_footer();
    ?>
</body>

</html>
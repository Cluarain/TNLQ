<?php

/**
 * Template Name: Affiliate
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
        
        <?php get_template_part('templates/tuneliqa-different', null, [
            'title' => 'Why join',
            'items' => [
                [
                    'item_img' => 'advantage1',
                    'item_title' => '10% from every payment',
                ],
                [
                    'item_img' => 'advantage2',
                    'item_title' => 'Lifetime revenue from each client',
                ],
                [
                    'item_img' => 'advantage3',
                    'item_title' => 'Recurring payouts',
                ],
                [
                    'item_img' => 'advantage4',
                    'item_title' => 'Simple and transparent tracking',
                ],
                [
                    'item_img' => 'advantage4',
                    'item_title' => 'Works with any traffic',
                ],
                [
                    'item_img' => 'advantage4',
                    'item_title' => 'Powerfull affiliate tools',
                ],
            ],
        ]) ?>



    </main>
    <?php
    get_footer();
    wp_footer();
    ?>
</body>

</html>
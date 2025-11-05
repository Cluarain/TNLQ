<?php

$defaults = array(
    'titles' => [
        ['title' => 'resist mass surveillance'],
        ['title' => 'CONNECT. OBFUSCATE. VANISH'],
        ['title' => 'TRUST MATH, NOT MARKETING'],
        ['title' => 'ENCRYPTION IS FREEDOM'],
        ['title' => 'BYPASS EVERYTHING'],
        ['title' => 'STAY PRIVATE. STAY TRUE'],
    ],
);
extract(parse_args_filtered($args, $defaults));
?>

<section class="resist-mass">
    <div class="resist-mass__banner">
        <div class="resist-mass__img"><?php echo get_attachment_image_by_name('green_hole') ?></div>
        <?php
        shuffle($titles);
        foreach ($titles as $index => $title) {

            if ($index == 0) {
                echo '
                <h2 class="resist-mass__title glow active">
                    ' . $title['title'] . '
                </h2>';
            } else {
                echo '
                <p class="resist-mass__title glow">
                    ' . $title['title'] . '
                </p>';
            }
        }
        ?>
    </div>
</section>
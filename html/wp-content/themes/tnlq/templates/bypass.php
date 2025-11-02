<?php
$defaults = array(
    'title' => 'bypass everything',

    'tags' => [
        ['tag' => 'Zero logs'],
        ['tag' => 'No tracking'],
        ['tag' => 'Just exit'],
        ['tag' => 'Connect.'],
        ['tag' => 'Obfuscate.'],
        ['tag' => 'Vanish.'],
        ['tag' => 'Crypto only']
    ],

    'card_title' => 'Ready to escape surveillance?',
    'card_button1' => 'Get Started',
    // 'card_button2' => 'View Plans',
    'additional_attributes' => '',
);
extract(parse_args_filtered($args, $defaults));

?>
<section class="bypass padding-block-xxxl" <?php echo $additional_attributes ?>>
    <div class="container">
        <div class="bypass__inner">
            <div class="bypass__separator">
                <div class="bypass__logo">
                    <?php echo get_attachment_image_by_name('block_logo') ?>
                    <!-- <img src="/assets/images/block_logo.svg" alt="block logo" width="1016" height="224" loading="lazy" decoding="async"> -->
                </div>
                <div class="bypass__slogan bypass-double-sign">
                    <h3 class="bypass__slogan-title"><?php echo $title ?></h3>
                </div>
                <div class="bypass__tags">
                    <?php
                    $split_index = floor(count($tags) / 2);
                    $first_half = array_slice($tags, 0, $split_index);
                    $second_half = array_slice($tags, $split_index);

                    ?>
                    <div class="bypass__tags top-container">
                        <?php
                        foreach ($first_half as $tag) {
                            echo '<span class="bypass__tags-text top-text">' . $tag["tag"] . '</span>';
                        }
                        ?>
                    </div>
                    <div class="bypass__tags bottom-container">
                        <?php
                        foreach ($second_half as $tag) {
                            echo '<span class="bypass__tags-text bottom-text">' . $tag["tag"] . '</span>';
                        }
                        ?>
                    </div>
                </div>
                <div class="bypass__buttons">
                    <h3 class="bypass__buttons-subtitle"><?php echo $card_title ?></h3>
                    <div class="main-banner__inner__buttons">
                        <a href="<?php print_global_var('pricing_link') ?>" class="btn btn-lg btn-primary arrow-sign hover-active-2"><?php echo $card_button1 ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
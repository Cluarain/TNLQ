<?php
$defaults = array(
    'title' => 'Take Back Your Internet',
    'button1' => 'Get Started',
    // 'button2' => 'View Plans',
);
extract(parse_args_filtered($args, $defaults));
?>
<style>
    @media (max-width: 1920px) {

        .main-banner__img {
            /* top: -10%; */

            &>* {
                transform: scale(1.2);
            }
        }
    }

    @media (max-width: 1020px) {

        .main-banner__img {
            top: -20%;
        }
    }

    @media (max-width: 768px) {
        .main-banner__img {
            top: -40%;
        }
    }
</style>

<section class="main-banner">
    <div class="main-banner__img bottom__gradient">
        <?php
        echo get_attachment_image_by_name(
            'hole',
            'full',
            false,
            [
                'fetchpriority' => 'high',
                'lazyload' => false,
                // 'svg-inline' => true
            ]
        );
        ?>
    </div>
    <div class="container">
        <div class="main-banner__inner margin-xxxl">
            <h1 class="main-banner__inner__title project__title">
                <span class="font-l"><?php echo $title ?></span>
            </h1>
            <div class="main-banner__inner__buttons">
                <a href="<?php print_global_var('pricing_link') ?>" class="btn btn-lg btn-primary arrow-sign hover-active-2"><?php echo $button1 ?></a>
            </div>
        </div>
    </div>
</section>
<?php
$defaults = array(
    'title' => 'Take Back Your Internet',
    'button1' => 'Get Started',
    'button2' => 'View Plans',
);
extract(parse_args_filtered($args, $defaults));
?>


<section class="main-banner">
    <div class="main-banner__img">
        <?php
        // $param = isset($_GET['test']) ? sanitize_text_field($_GET['test']) : '';
        // if ($param === '1') {
        // } else {
        //     echo get_attachment_image_by_name('main_banner', 'full', false, array('class' => 'main-banner__img'));
        // }
        echo '<img src="/assets/images/hole.svg" alt="' . $title . '" decoding="async" fetchpriority="high" >';
        ?>
    </div>
    <div class="container">
        <div class="main-banner__inner margin-xxxl">
            <h2 class="main-banner__inner__title project__title">
                <span class="font-l"><?php echo $title ?></span>
            </h2>
            <div class="main-banner__inner__buttons">
                <a href="#" class="btn btn-lg btn-primary arrow-sign hover-active-2"><?php echo $button1 ?></a>
                <a href="<?php print_global_var('pricing_link') ?>" class="btn btn-lg btn-transparent arrow-sign"><?php echo $button2 ?></a>
            </div>
        </div>
    </div>
</section>
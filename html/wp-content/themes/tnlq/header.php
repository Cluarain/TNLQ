<?php

$client_ip = get_real_user_ip();
$in_tunnel = check_if_in_tunnel($client_ip);


$why_link = get_global_var('why-tuneliqa_link');
$pricing_link = get_global_var('pricing_link');
$privacy_link = get_global_var('privacy-matters_link');
$faq_link = get_global_var('faq_link');


$affiliate_link_label = <<<HTML
<a href="/affiliate/" class="text-success active-2 hover-active-1 nav-link arrow-sign">Affiliate</a>
HTML;

if (is_user_logged_in()) {
    $affiliate_link_label = <<<HTML
        <div class="nav-links-group-v">
            <a href="/affiliate/" class="text-success active-2 hover-active-1 nav-link arrow-sign">Affiliate</a>
            <a href="/affiliate/affiliate-area/" class="text-affiliate hover-active-1 hover-active-2 nav-link arrow-sign">Area</a>
        </div>
    HTML;
}


$nav_links = <<<HTML
    <a href="{$why_link}" class="hover-active-1 nav-link">Why Tuneliqa</a>
    <a href="{$pricing_link}" class="hover-active-1 nav-link">Pricing</a>
    <a href="{$privacy_link}" class="hover-active-1 nav-link">Privacy</a>
    <a href="{$faq_link}" class="hover-active-1 nav-link">FAQ</a>
    {$affiliate_link_label}
HTML;

?>

<header id="header">
    <div class="container">
        <div class="header__label">
            <div class="header__label__box">
                <div class="header__ip">
                    <span>Your IP: </span>
                    <span class="client-ip text-tertiary">
                        <?php echo $client_ip ?>
                    </span>
                </div>
                <div class="header__status">
                    <span>Your status: </span>
                    <span class="text-<?php echo $in_tunnel ? 'secondary' : 'quaternary'; ?>">
                        <?php echo $in_tunnel ? 'In the Tunnel' : 'Not in the Tunnel'; ?>
                    </span>
                </div>
            </div>
            <div class="header__label__box">
                <label class="theme-switch"></label>
            </div>
        </div>

        <div class="header__nav">
            <nav class="header__nav__inner margin-l">
                <div class="header__nav__inner__mobile">
                    <?php
                    $burger_svg = get_svg_inline_by_attachmentID(get_attachment_image_ID_by_name('burger'));
                    $css_burger = '--default-burger: url(data:image/svg+xml;charset=utf8,' . rawurlencode($burger_svg) . ');';
                    ?>
                    <div id="mobile_burger" class="header__nav__inner__mobile__burger hover-active-1" style="<?php echo $css_burger ?>"></div>
                    <div class="header__nav__inner__mobile__modal">
                        <span class="header__nav__inner__mobile__modal__title project__title"><span class="font-l">Menu</span></span>
                        <?php echo $nav_links ?>
                    </div>
                </div>
                <a href="/" class="tnlq-logo">
                    <span class="d-none">Main page</span>
                    <?php echo get_attachment_image_by_name('tuneliqa_logo') ?>
                </a>
                <div class="header__nav__inner__desktop">
                    <?php echo $nav_links ?>
                    <a href="<?php print_global_var('pricing_link') ?>" class="btn btn-lg btn-transparent hover-active-2 arrow-sign">View Plans</a>
                </div>
            </nav>
        </div>
    </div>
</header>
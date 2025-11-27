<header id="header">
    <div class="container">
        <div class="header__label">
            <div class="header__label__box">
                <div class="header__ip">
                    <span>Your IP: </span>
                    <span class="text-tertiary">
                        <?php print_global_var('client_ip') ?>
                    </span>
                </div>
                <div class="header__status">
                    <span>Your status: </span>
                    <span class="text-quaternary"><?php print_global_var('client_status') ?></span>
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
                        <a href="<?php print_global_var('why-tuneliqa_link') ?>" class="hover-active-1 nav-link">Why
                            Tuneliqa</a>
                        <a href="<?php print_global_var('pricing_link') ?>" class="hover-active-1 nav-link">Pricing</a>
                        <a href="<?php print_global_var('privacy-matters_link') ?>" class="hover-active-1 nav-link">Privacy</a>
                        <a href="<?php print_global_var('faq_link') ?>" class="hover-active-1 nav-link">FAQ</a>
                        <a href="<?php print_global_var('contact_link') ?>"
                            class="text-success hover-active-1 nav-link arrow-sign">contact</a>
                    </div>
                </div>
                <a href="/" class="tnlq-logo">
                    <span class="d-none">Main page</span>
                    <?php echo get_attachment_image_by_name('tuneliqa_logo') ?>
                </a>
                <div class="header__nav__inner__desktop">
                    <a href="<?php print_global_var('why-tuneliqa_link') ?>" class="hover-active-1 nav-link">Why
                        Tuneliqa</a>
                    <a href="<?php print_global_var('pricing_link') ?>" class="hover-active-1 nav-link">Pricing</a>
                    <a href="<?php print_global_var('privacy-matters_link') ?>" class="hover-active-1 nav-link">Privacy</a>
                    <a href="<?php print_global_var('faq_link') ?>" class="hover-active-1 nav-link">FAQ</a>
                    <a href="<?php print_global_var('contact_link') ?>"
                        class="text-success hover-active-1 hover-active-2 nav-link arrow-sign">contact</a>
                    <a href="<?php print_global_var('pricing_link') ?>" class="btn btn-lg btn-transparent hover-active-2 arrow-sign">View Plans</a>
                </div>
            </nav>
        </div>
    </div>
</header>
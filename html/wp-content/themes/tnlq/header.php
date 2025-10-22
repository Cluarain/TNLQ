<header>
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
                <a href="/" class="tnlq-logo text-accent hover-active-1"><img src="/assets/images/tuneliqa_logo.svg" width="275" height="43" alt="logo"></a>
                <a href="<?php print_global_var('why-tuneliqa_link') ?>" class="hover-active-1 nav-link">Why Tuneliqa</a>
                <a href="<?php print_global_var('pricing_link') ?>" class="hover-active-1 nav-link">Pricing</a>
                <a href="/privacy-policy/" class="hover-active-1 nav-link">Privacy</a>
                <a href="<?php print_global_var('faq_link') ?>" class="hover-active-1 nav-link">FAQ</a>
                <a href="<?php print_global_var('contact_link') ?>" class="text-success hover-active-1 nav-link arrow-sign">contact</a>
                <a href="#" class="btn btn-transparent arrow-sign">Generate account</a>
            </nav>
        </div>
    </div>
</header>
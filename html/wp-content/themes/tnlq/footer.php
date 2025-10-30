<footer class="footer padding-block-xxxl">
    <div class="container">
        <div class="footer__inner">
            <div class="footer__inner__box">
                <a href="/" class="tnlq-logo text-accent hover-active-1">
                    <span class="d-none">Main page</span>
                    <?php echo get_attachment_image_by_name('tuneliqa_logo', 'full', false, ['svg-inline' => true]) ?>
                </a>
                <h2 class="footer__title project__title">
                    Take Back Your Internet
                </h2>
                <p class="footer__subtitle text-tertiary double-colon-mark">Your data is yours. We just help you keep it that way. No ads. No trackers. No logs. Just tunnels of trust</p>
            </div>
            <div class="footer__inner__box footer__buttons">
                <a href="#" class="btn btn-transparent arrow-sign">Generate account</a>
                <a href="<?php print_global_var('contact_link') ?>" class="text-success hover-active-1 nav-link arrow-sign font-l">contact</a>
                <div class="footer__social">
                    <?php get_template_part('templates/social') ?>
                </div>
            </div>
            <div class="footer__bottom text-tertiary">
                <span>(c) 2025 Tuneliqa.</span>
                <a href="/privacy-policy/" class="hover-active-1">Privacy Policy</a>
                <a href="/terms-of-service/" class="hover-active-1">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>
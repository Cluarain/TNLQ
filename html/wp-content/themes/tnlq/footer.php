<dialog id="paymentDialog">
    <form method="dialog" class="modal-close">
        <button type="button">&times;</button>
    </form>
    <div id="modal-wrapper" class="modal-payment">
        <div class="modal-content">
            <form id="paymentForm" method="POST" action="<?php echo get_template_directory_uri(); ?>/process-payment.php">
                <?php wp_nonce_field('direct_payment_nonce', '_wpnonce'); ?>
                
                <p class="modal-heading">Specify the email address. The configuration for the VPN will be sent to it.</p>

                <div class="form-control-wrapper">
                    <input class="form-control input" type="email" id="email" name="email" placeholder=" " required>
                    <label class="placeholder arrow-sign" for="email">Your email is </label>
                </div>

                <input type="hidden" id="productId" name="product_id">
                <input type="hidden" id="tariffPeriod" name="tariff_period">
                
                <div class="modal-buttons">
                    <button type="submit" class="btn btn-primary arrow-sign hover-active-2">Continue</button>
                </div>
            </form>
        </div>
    </div>
</dialog>

<footer id="footer" class="footer padding-block-xxxl">
    <div class="container">
        <div class="footer__inner">
            <div class="footer__inner__box">
                <a href="/" class="tnlq-logo text-accent hover-active-1">
                    <span class="d-none">Main page</span>
                    <?php echo get_attachment_image_by_name('tuneliqa_logo', 'full', false, ['svg-inline' => false]) ?>
                </a>
                <h2 class="footer__title project__title">
                    Take Back Your Internet
                </h2>
                <p class="footer__subtitle text-tertiary double-colon-mark">Your data is yours. We just help you keep it that way. No ads. No trackers. No logs. Just tunnels of trust</p>
            </div>
            <div class="footer__inner__box footer__buttons">
                <a href="<?php print_global_var('pricing_link') ?>" class="btn btn-transparent btn-lg hover-active-2 arrow-sign">View Plans</a>
                <a href="<?php print_global_var('contact_link') ?>" class="text-success hover-active-2 nav-link arrow-sign font-l">contact</a>
                <div class="footer__social">
                    <?php get_template_part('templates/social') ?>
                </div>

            </div>
            <div class="footer__bottom text-tertiary">
                <span>(c) 2025 Tuneliqa.</span>
                <a href="/privacy-policy/" class="hover-active-1">Privacy Policy</a>
                <a href="/terms-of-service/" class="hover-active-1">Terms of Service</a>
                <a href="/legal/" class="hover-active-1">Legal and Jurisdiction</a>
            </div>
        </div>
    </div>
</footer>
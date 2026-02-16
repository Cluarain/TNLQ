<dialog id="paymentDialog">
    <form method="dialog" class="modal-close">
        <button type="submit">&times;</button>
    </form>
    <div id="modal-wrapper" class="modal-wrapper">
        <div class="modal-content">
            <form id="paymentForm" class="modal-form" method="POST" action="/process-payment.php">
                <?php wp_nonce_field('direct_payment_nonce', '_wpnonce') ?>

                <div class="modal-heading">
                    <p class="arrow-sign">enter email — config delivery only.</p>
                    <p class="arrow-sign">no newsletters. no marketing.</p>
                </div>

                <div class="form-control-wrapper">
                    <input class="form-control input" type="email" autocomplete="true" id="email" name="email" placeholder="Enter your email" required>

                    <details style="height: 90px; margin-top: 10px;">
                        <summary class="btn" style="font-size: inherit; padding: 0; color: var(--text-secondary); text-decoration: underline dotted;">have promocode?</summary>
                        <input class="form-control input" type="promo" id="promo" name="promo" placeholder="Enter your promo">
                    </details>

                    <!-- <label class="placeholder arrow-sign" for="email">Enter your email </label> -->
                </div>

                <input type="hidden" id="productId" name="product_id">
                <input type="hidden" id="tariffPeriod" name="tariff_period">

                <div class="modal-buttons">
                    <button type="submit" class="btn btn-primary arrow-sign hover-active-2">Go to payment</button>
                </div>
            </form>
        </div>
    </div>
</dialog>
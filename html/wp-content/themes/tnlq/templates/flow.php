<?php
$defaults = array(
    'title' => 'Payouts & money flow',
    'subtitle' => 'Fast and Easy Payouts',
    'card1text' => 'No complex rules. <br>No manual approval. <br>No waiting weeks.',
    'card2text' => 'Payouts in crypto <br>USDT, XMR, BTC, ETH <br>Automatic balance tracking <br>Clear payout history',
    'bottomtext' => 'You earn → balance updates → payout. <br>Simple as that.'
);

extract(parse_args_filtered($args, $defaults));
?>

<style>
    .flowsection__cards {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }

    .flowsection__card {
        padding: 50px;
        display: flex;
        flex-flow: column;
        gap: 30px;
        border: 1px solid var(--border-tertiary);
        align-items: center;
    }

    .flowsection__bottom {
        text-align: center;
    }

    @media (max-width: 768px) {
        .flowsection__cards {
            grid-template-columns: 1fr;
        }

        .flowsection__bottom>br {
            display: none;
        }
    }
</style>

<section class="flowsection padding-block-xxxl">
    <div class="container">
        <h2 class="arrow-sign project__title"><?php echo $title ?></h2>
        <p class="project__subtitle text-secondary margin-s project__subtitle">
            <?php echo $subtitle ?>
        </p>

        <div class="flowsection__cards margin-s">
            <div class="flowsection__card">
                <div class="flowsection__card__img">
                    <?php
                    echo get_attachment_image_by_name('fingerprint', 'full', false, ['svg-inline' => true]);
                    ?>
                </div>
                <div class="flowsection__card__text text-secondary font-xxl">
                    <?php echo $card1text ?>
                </div>
            </div>
            <div class="flowsection__card">
                <div class="flowsection__card__img">
                    <?php
                    echo get_attachment_image_by_name('treangle', 'full', false, ['svg-inline' => true]);
                    ?>
                </div>
                <div class="flowsection__card__text text-secondary font-xxl">
                    <?php echo $card2text ?>
                </div>
            </div>
        </div>

        <div class="flowsection__bottom margin-s font-xxl">
            <?php echo $bottomtext ?>
        </div>
    </div>
</section>
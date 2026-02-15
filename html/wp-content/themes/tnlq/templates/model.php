<?php
$defaults = array(
    'title' => 'Commission model',
    'card_bottom' => 'Client renews monthly or yearly — you earn again.'
);

extract(parse_args_filtered($args, $defaults));
?>

<style>
    .commission-model__inner {
        position: relative;
    }

    .commission-model__img {
        position: absolute;
        inset-inline-start: 0;
        bottom: 0;
        z-index: -1;
        transform: translate(0%, -50%);
    }

    .commission-model__card {
        background: var(--bg-tertiary);
        box-shadow: var(--card-shadow);
        font-size: 24px;
        max-width: 880px;
        margin-inline: auto;
        padding-block: clamp(40px, 4vw, 55px);
        padding-inline: clamp(10px, 3vw, 70px);
    }

    .commission-model__card__inner__body {
        /* display: grid; */
        /* grid-template-columns: repeat(3, 1fr); */

        display: flex;
        flex-flow: row wrap;
        gap: 25px;
        justify-content: space-around;

        & dl {
            display: flex;
            flex-flow: column;
            align-items: center;
            justify-content: space-between;
            line-height: 1;
            gap: 10px;
        }
    }

    .commission-model__card__inner__bottom {
        border-top: 3px solid var(--border-secondary);
        text-align: center;
        margin-top: clamp(35px, 4vw, 50px);
        padding-top: clamp(35px, 4vw, 50px);
    }

    .commission-model-highlight {
        color: #A123A1;
        font-size: clamp(48px, 5vw, 64px);
    }
</style>

<section class="commission-model padding-block-xxxl">
    <div class="container">
        <h2 class="arrow-sign project__title"><?php echo $title ?></h2>

        <div class="commission-model__inner">
            <div class="commission-model__img">
                <svg width="137" height="137" viewBox="0 0 137 137" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M68.11 136.22L58.71 90.79L19.95 116.27L45.43 77.5L0 68.11L45.43 58.71L19.95 19.95L58.71 45.43L68.11 0L77.5 45.43L116.27 19.95L90.81 58.71L136.24 68.11L90.81 77.5L116.29 116.27L77.52 90.79L68.11 136.22ZM59.39 89.14L68.11 131.28L76.81 89.14L112.81 112.78L89.17 76.78L131.31 68.07L89.17 59.36L112.81 23.36L76.81 46.99L68.1 4.86L59.38 46.99L23.44 23.44L47.07 59.44L4.94 68.11L47.07 76.82L23.44 112.82L59.39 89.14Z" fill="#66BB6A" />
                </svg>
            </div>

            <div class="commission-model__card margin-m">
                <div class="commission-model__card__inner">
                    <div class="commission-model__card__inner__body">
                        <dl>
                            <dt class="text-secondary">Commission rate:</dt>
                            <dd class="commission-model-highlight">10%</dd>
                        </dl>
                        <dl>
                            <dt class="text-secondary">Type:</dt>
                            <dd class="font-xxl">Recurring</dd>
                        </dl>
                        <dl>
                            <dt class="text-secondary">Duration:</dt>
                            <dd class="font-xxl">Lifetime</dd>
                        </dl>
                    </div>
                    <div class="commission-model__card__inner__bottom text-secondary">
                        <?php echo $card_bottom ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
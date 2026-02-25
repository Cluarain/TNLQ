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
                <?php
                echo get_attachment_image_by_name('star8', 'full', false, ['svg-inline' => true]);
                ?>
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
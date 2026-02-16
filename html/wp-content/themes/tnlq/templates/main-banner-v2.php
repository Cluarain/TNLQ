<?php
$defaults = array(
    'title' => 'Take Back Your Internet',
    'subtitle' => '10% Lifetime Commission',
    'tags' => [
        ['tag' => 'Bring traffic.'],
        ['tag' => 'Get paid from every client payment.'],
        ['tag' => 'Forever.'],
    ],
    'cta_title' => 'Begin to earn',
    'button1' => 'Get started',
    'button2' => 'Login',
    'button_tags' => [
        ['tag' => 'Fast payouts'],
        ['tag' => 'No KYC'],
        ['tag' => 'No limits'],
    ],
);
extract(parse_args_filtered($args, $defaults));
?>

<style>
    .main-bannerV2 {
        overflow: hidden;
        position: relative;
    }

    .main-bannerV2__img {
        position: absolute;
        z-index: -1;
        opacity: 0.5;
        height: 100%;

        &>* {
            object-fit: contain;
            object-position: bottom;
            /* transform: scale(1.1); */
        }
    }

    .main-bannerV2__img__small {
        position: absolute;
        z-index: -1;

        &.left {
            --size: clamp(150px, 30vw, 440px);
            inset-inline-start: 0;
            height: var(--size);
            width: var(--size);
            filter: blur(clamp(4px, 1vw, 10px));

            transform: rotate(90deg) translate(0, 30%);
        }


        &.right {
            --size: clamp(75px, 15vw, 220px);
            inset-inline-end: 0;
            height: var(--size);
            width: var(--size);

            transform: translate(30%, 0);
        }
    }

    .main-bannerV2__inner {
        display: flex;
        flex-flow: column;
        gap: 20px;
        align-items: center;
        /* justify-content: center;  */
        text-align: center;
    }

    .main-bannerV2__inner-container {
        text-align: center;
    }

    .main-bannerV2__inner__title {
        text-transform: uppercase;
        position: relative;
    }

    .main-bannerV2__inner__title__img {
        --size: clamp(45px, 10vw, 140px);
        position: absolute;
        inset-inline-start: 0%;
        top: 0%;
        height: var(--size);
        width: var(--size);
        transform: rotate(-18deg) translate(-200%, 0%);
    }

    .main-bannerV2__inner__subtitle {
        font-size: clamp(20px, 2.8vw, 32px);
    }

    .main-bannerV2__container {
        display: flex;
        flex-flow: column;
        gap: 200px;
        z-index: 1;
        /* margin-inline: clamp(23px, 12.5vw, 224px); */
        padding-block-end: 20px;
    }

    .main-bannerV2__inner-tag {
        font-size: clamp(16px, 2.6vw, 32px);
    }

    .main-bannerV2__buttons-container {
        background: hsla(264, 11%, 9%, 100%);
        display: flex;
        flex-flow: column;
        gap: clamp(20px, 2.8vw, 34px);
        padding: clamp(35px, 4vw, 50px);
        z-index: 1;
        max-width: 850px;
        margin-inline: auto;
    }

    .main-bannerV2__buttons {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, auto));
        justify-content: center;
        gap: clamp(20px, 3vw, 35px);
    }

    .main-bannerV2__buttons-title {
        font-size: clamp(24px, 3.2vw, 36px);
        text-transform: uppercase;
        text-align: center;
    }

    .btn-secondary {
        border: 1px solid var(--border-primary);
    }

    .main-bannur__button {
        text-align: center;
    }

    .main-bannerV2__buttons-tags {
        text-align: center;
        font-size: clamp(14px, 2.8vw, 32px);
    }

    @media (max-width: 768px) {
        .main-bannerV2__img>* {
            object-position: center;
            transform: scale(2.5);
        }

        .main-bannerV2__img__small {
            &.left {
                bottom: 10%;
            }

            &.right {
                top: 20%;
            }
        }

        .main-bannerV2__inner__title__img {
            transform: rotate(-18deg) translate(0%, -150%);
        }
    }
</style>

<section class="main-bannerV2">
    <div class="main-bannerV2__img bottom__gradient">
        <?php
        echo get_attachment_image_by_name(
            'digital-mountains',
            'full',
            false,
            [
                'fetchpriority' => 'high',
                'lazyload' => false,
                // 'svg-inline' => true
            ]
        );
        ?>
    </div>

    <div class="main-bannerV2__img__small left">
        <?php echo get_attachment_image_by_name('bucks', 'full', false, ['svg-inline' => true]) ?>
    </div>

    <div class="main-bannerV2__img__small right">
        <?php echo get_attachment_image_by_name('bucks', 'full', false, ['svg-inline' => true]) ?>
    </div>
    <div class="container">
        <div class="main-bannerV2__container">
            <div class="main-bannerV2__inner-container">
                <div class="main-bannerV2__inner" style="padding-block: clamp(100px, 25vw, 235px)">
                    <h1 class="main-bannerV2__inner__title project__title">
                        <span class="font-l"><?php echo $title ?></span>

                        <div class="main-bannerV2__inner__title__img">
                            <?php echo get_attachment_image_by_name('diamond', 'full', false, ['svg-inline' => true]) ?>
                        </div>

                    </h1>
                    <p class="main-bannerV2__inner__subtitle project__subtitle text-secondary">
                        <?php echo $subtitle ?>
                    </p>
                </div>
                <div class="main-bannerV2__inner__tags">
                    <?php
                    foreach ($tags as $tag) {
                        echo '<p class="main-bannerV2__inner-tag">' . $tag["tag"] . '</span>';
                    }
                    ?>
                </div>
            </div>
            <div class="main-bannerV2__buttons-container">
                <p class="main-bannerV2__buttons-title">
                    <?php echo $cta_title ?>
                </p>
                <div class="main-bannerV2__buttons">
                    <a href="#" class="main-bannur__button btn btn-primary arrow-sign hover-active-2"><?php echo $button1 ?></a>
                    <a href="#" class="main-bannur__button btn btn-secondary arrow-sign hover-active-2"><?php echo $button2 ?></a>
                </div>
                <div class="main-bannerV2__buttons-tags text-secondary">
                    <?php
                    $tags_array = array_map(function ($tag) {
                        return '<span class="main-bannerV2__buttons-tag">' . $tag["tag"] . '</span>';
                    }, $button_tags);

                    echo implode(' • ', $tags_array);
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>
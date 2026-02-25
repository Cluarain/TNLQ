<?php
$defaults = array(
    'title' => 'Affiliate Portal',
    'subtitle' => 'Everything you need to earn — in one place.',
    'text1' => 'You get a clean, fast affiliate portal. <br>No clutter. No learning curve.',
    'text2' => 'Personal affiliate dashboard <br>Instant referral links <br>Real-time clicks and conversions <br>Live commission balance <br>Clear payout history <br>One-click link generator <br>Works on desktop and mobile',
    'card_title' => 'GET ACCESS TO AFFILIATE PORTAL',
    'card_subtitle' => 'Built on proven affiliate infrastructure. <br>Used by thousands of programs.',
    'card_button_text' => 'BEGIN TO EARN',
    'card_button_link' => '#',
);

extract(parse_args_filtered($args, $defaults));
?>

<style>
    .portal {
        position: relative;
        overflow: hidden;
    }

    .portal-bg-img {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        z-index: -1;

        &>* {
            width: 100%;
            min-height: fit-content;
        }

        /* opacity: 0.5; */
    }

    .portal__inner__texts {
        display: flex;
        flex-flow: row wrap;
        gap: clamp(30px, 4vw, 70px);
        justify-content: space-between;
        font-size: clamp(16px, 2vw, 24px);
    }

    .portal__inner__text1 {
        display: flex;
        flex-flow: column;
        gap: 20px;
        justify-content: space-between;
    }

    .portal__inner__card {
        padding-block: clamp(25px, 3vw, 55px);
        padding-inline: 30px;
        background-color: #161419;

        display: flex;
        flex-flow: row wrap;
        justify-content: space-around;
        gap: 20px;
        align-items: center;
    }

    .portal__inner__card__body {
        display: flex;
        flex-flow: column;
        gap: clamp(20px, 2.5vw, 30px);
    }


    .portal__inner__card__btn {
        display: block;
        height: auto;
        padding-inline: 50px;
    }

    .portal__inner__text1__img {
        --size: clamp(99px, 10vw, 135px);
        height: var(--size);
        width: var(--size);
    }

    @media (max-width: 768px) {
        .portal__inner__card__body {
            text-align: center;
        }
    }
</style>

<section class="portal padding-block-xxxl">
    <div class="portal-bg-img top_bottom__gradient">
        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1"
            width="2000" height="1000" viewBox="0 0 2000 1000" preserveAspectRatio="xMidYMid slice">
            <defs>
                <filter id="noise" x="-20%" y="-20%" width="140%" height="140%"
                    filterUnits="objectBoundingBox" color-interpolation-filters="linearRGB">
                    <feTurbulence type="fractalNoise" baseFrequency="0.2" numOctaves="4" seed="15"
                        stitchTiles="stitch" x="0%" y="0%" width="100%" height="100%" result="turbulence"></feTurbulence>
                    <feSpecularLighting surfaceScale="33" specularConstant="3" specularExponent="20"
                        lighting-color="#fff" x="0%" y="0%" width="100%" height="100%"
                        in="turbulence" result="specularLighting">
                        <feDistantLight azimuth="3" elevation="116"></feDistantLight>
                    </feSpecularLighting>
                </filter>
            </defs>
            <rect width="100%" height="100%" fill="#fff" opacity="0.3" filter="url(#noise)"></rect>
        </svg>
    </div>
    <div class="container">
        <h2 class="arrow-sign project__title"><?php echo $title ?></h2>
        <p class="project__subtitle margin-s text-secondary">
            <?php echo $subtitle ?>
        </p>
        <div class="portal__inner font-xl">
            <div class="portal__inner__texts margin-l">
                <div class="portal__inner__text1 text-secondary">
                    <div>
                        <?php echo $text1 ?>
                    </div>
                    <div class="portal__inner__text1__img">
                        <?php
                        echo get_attachment_image_by_name('qudro-abstract', 'full', false, ['svg-inline' => true]);
                        ?>
                    </div>
                </div>
                <div class="portal__inner__text2">
                    <?php echo $text2 ?>
                </div>
            </div>

            <div class="portal__inner__card margin-xxxl">
                <div class="portal__inner__card__body">
                    <h3 class="portal__inner__card__title font-xxl">
                        <?php echo $card_title ?>
                    </h3>
                    <p class="portal__inner__card__subtitle text-secondary">
                        <?php echo $card_subtitle ?>
                    </p>
                </div>
                <a href="<?php echo $card_button_link ?>" class="portal__inner__card__btn btn btn-primary arrow-sign hover-active-2"><?php echo $card_button_text ?></a>
            </div>
        </div>
    </div>
</section>
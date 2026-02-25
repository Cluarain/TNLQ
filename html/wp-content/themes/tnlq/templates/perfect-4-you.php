<?php
$defaults = array(
    'title' => 'Who this is for',

    'list' => '— Run websites or blogs <br>— Manage servers or communities <br>— Work with traffic or ads <br>— Recommend tools you actually use',
    'bottom_text' => 'If you can send traffic — you can earn.',
);

extract(parse_args_filtered($args, $defaults));
?>

<style>
    .perfect-4-you {
        background: #161914;
    }

    .perfect-4-you__inner {
        padding-inline-start: clamp(20px, 3vw, 50px);
        display: flex;
        flex-flow: row wrap;
        gap: 30px;
        align-items: center;

        justify-content: space-around;
    }

    .perfect-4-you__inner__text {
        display: flex;
        flex-flow: column;
        gap: 1em;
        font-size: clamp(16px, 2vw, 30px);
    }

    .perfect-4-you__inner__img {
        height: clamp(135px, 17vw, 235px);
    }

    .perfect-4-you__bottom__text {
        color: #FFF696;
        font-size: clamp(20px, 2.5vw, 32px);
    }

    .perfect-4-you__inner__text__title {
        font-size: 0.8em;
    }
</style>

<section class="perfect-4-you padding-block-xxxl">
    <div class="container">
        <h2 class="arrow-sign project__title margin-m"><?php echo $title ?></h2>

        <div class="perfect-4-you__inner text-secondary margin-m">
            <div class="perfect-4-you__inner__text">
                <p class="perfect-4-you__inner__text__title">
                    Perfect if you:
                </p>
                <div class="perfect-4-you__inner__text__list">
                    <?php echo $list ?>
                </div>
            </div>

            <div class="perfect-4-you__inner__img">
                <?php
                echo get_attachment_image_by_name('meta-human', 'full', false, ['svg-inline' => true]);
                ?>
            </div>
        </div>

        <p class="perfect-4-you__bottom__text margin-m">
            <?php echo $bottom_text ?>
        </p>
    </div>
</section>
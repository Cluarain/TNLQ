<?php
$defaults = array(
    'title' => 'How it works',
    'items' => [
        [
            // номер подтягивается из порядкового номера
            'item_title' => 'Register',
            'item_text' => 'Create an affiliate account. <br>Get your referral link instantly.',
        ],
        [
            'item_title' => 'Share',
            'item_text' => 'Post your link. <br>Website, ads, social, <br>messengers — your choice.',
        ],
        [
            'item_title' => 'Earn',
            'item_text' => 'Client pays. <br>You get 10%. <br>Every time.',
        ],
    ]
);

extract(parse_args_filtered($args, $defaults));
?>

<style>
    .step-by-step {
        background: var(--bg-secondary);
    }

    .step-by-step__inner__container {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: clamp(100px, 10vw, 190px);
        position: relative;

        &::after {
            content: "";
            position: absolute;
            top: 0;
            inset-inline-end: -10%;
            background: no-repeat url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 189 189' fill='none'%3E%3Cpath d='M175.236 189H135.65a13.79 13.79 0 0 1-9.733-4.034c-2.583-2.579-4.037-6.077-4.045-9.727v-34.887a12.35 12.35 0 0 0-3.618-8.734c-2.317-2.317-5.46-3.618-8.737-3.618H74.721a13.79 13.79 0 0 1-9.729-4.044c-2.58-2.582-4.031-6.081-4.035-9.731V79.451a12.38 12.38 0 0 0-3.627-8.726c-2.315-2.315-5.454-3.619-8.728-3.626H13.764a13.78 13.78 0 0 1-9.728-4.035C1.455 60.484.004 56.986 0 53.338V13.761c.004-3.648 1.455-7.146 4.035-9.726A13.78 13.78 0 0 1 13.764 0H53.35a13.78 13.78 0 0 1 9.728 4.034c2.58 2.58 4.032 6.078 4.035 9.726v34.831a12.38 12.38 0 0 0 3.632 8.731c2.318 2.315 5.46 3.617 8.737 3.621h34.824a13.79 13.79 0 0 1 13.778 13.761v34.831a12.35 12.35 0 0 0 3.619 8.734c2.317 2.317 5.459 3.618 8.736 3.618h34.796c3.651.008 7.149 1.462 9.729 4.044a13.79 13.79 0 0 1 4.035 9.731v39.577c-.007 3.648-1.46 7.143-4.04 9.722a13.79 13.79 0 0 1-9.724 4.039zM13.764 1.408c-3.277 0-6.419 1.301-8.736 3.618a12.35 12.35 0 0 0-3.619 8.734v39.578a12.35 12.35 0 0 0 3.619 8.734c2.317 2.317 5.459 3.618 8.736 3.618h34.839a13.78 13.78 0 0 1 9.728 4.034c2.58 2.58 4.032 6.078 4.035 9.726v34.831a12.38 12.38 0 0 0 3.622 8.735c2.316 2.318 5.456 3.623 8.733 3.631h34.839c3.649.004 7.148 1.455 9.728 4.034a13.77 13.77 0 0 1 4.035 9.726v34.831a12.38 12.38 0 0 0 3.632 8.731c2.319 2.316 5.46 3.618 8.737 3.622h39.544c3.275-.008 6.413-1.312 8.728-3.627a12.37 12.37 0 0 0 3.627-8.726v-39.577c-.003-3.276-1.306-6.417-3.622-8.735a12.38 12.38 0 0 0-8.733-3.631H140.44a13.78 13.78 0 0 1-9.728-4.035 13.77 13.77 0 0 1-4.036-9.726V74.704c-.007-3.276-1.313-6.416-3.632-8.731s-5.46-3.617-8.737-3.621H79.44c-3.65-.004-7.151-1.454-9.733-4.034s-4.037-6.077-4.044-9.727V13.761a12.35 12.35 0 0 0-3.619-8.734c-2.317-2.316-5.459-3.618-8.736-3.618H13.764z' fill='%23fe35fe'/%3E%3C/svg%3E");
            width: 190px;
            height: 190px;
        }
    }

    .step-by-step-item {
        display: flex;
        flex-flow: column;
        gap: clamp(10px, 1.4vw, 20px);
        position: relative;

        &:not(:last-child) {
            &::after {
                content: "";
                position: absolute;
                top: 0;
                inset-inline-end: 0;
                width: 100px;
                height: 170px;
                transform: translate(150%, 50%);
                background: no-repeat url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 94 171' fill='none'%3E%3Cpath d='M93.07 85.27l-50 85.26v-32.16H0V32.16h43.05V0l50.02 85.27zm-49 81.58l47.86-81.58L44.07 3.68v29.48H1v104.21h43.05l.02 29.48z' fill='%23fff'/%3E%3C/svg%3E");
            }
        }
    }

    .step-by-step-number {
        font-size: clamp(64px, 7vw, 96px);

    }

    .step-by-step-title {
        font-size: clamp(24px, 2.5vw, 28px);
    }

    @media (max-width: 1750px) {
        .step-by-step__inner__container {
            grid-template-columns: 1fr;
            padding-inline-start: 35px;

            &::after {
                top: 77%;
                width: 130px;
                inset-inline-end: 10%;
            }
        }

        .step-by-step-item:not(:last-child)::after {
            top: 100%;
            inset-inline-start: 0;
            height: 100px;
            width: 60px;
            transform: translate(50%, 0) rotate(90deg);
        }
    }
</style>

<section class="step-by-step padding-block-xxxl">
    <div class="container">
        <h2 class="step-by-step__inner__title arrow-sign project__title"><?php echo $title ?></h2>
        <div class="step-by-step__inner__container">
            <?php foreach ($items as $index => $item): ?>
                <div class="step-by-step-item">
                    <div class="step-by-step-number"><?php echo $index + 1; ?></div>
                    <h3 class="step-by-step-title text-accent"><?php echo esc_html($item['item_title']); ?></h3>
                    <p class="step-by-step-text text-secondary font-xl"><?php echo wp_kses_post($item['item_text']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
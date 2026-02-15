<?php
$defaults = array(
    'title' => 'Why Tuneliqa is different?',
    'items' => [
        [
            'item_img' => 'advantage1',
            'item_title' => 'Diskless servers <i>(booted from RAM)</i>',
        ],
        [
            'item_img' => 'advantage2',
            'item_title' => 'Open source, verifiable code',
        ],
        [
            'item_img' => 'advantage3',
            'item_title' => 'No payment processors <i>(crypto only)</i>',
        ],
        [
            'item_img' => 'advantage4',
            'item_title' => 'Zero logs — enforced by design',
        ],
    ],

    'additional_attributes' => 'data-star="top,right"',
);
extract(parse_args_filtered($args, $defaults));
?>

<section class="tuneliqa-different padding-block-xxxl" <?php echo $additional_attributes ?>>
    <div class="container">
        <h2 class="tuneliqa-different__title project__title arrow-sign"><?php echo $title ?></h2>
        <div class="tuneliqa-different__advantages margin-m">
            <?php foreach ($items as $item) { ?>
                <div class="tuneliqa-different__item">
                    <div class="tuneliqa-different__item-img">
                        <?php
                        if (!is_array($item['item_img'])) {
                            echo get_attachment_image_by_name($item['item_img']);
                        } else {
                            $attachment_id = $item['item_img']['id'];
                            $mime_type = get_post_mime_type($attachment_id);
                            if ($mime_type === 'image/svg+xml') {
                                echo get_svg_inline_by_attachmentID($attachment_id);
                            } else {
                                echo wp_get_attachment_image($attachment_id);
                            }
                        }
                        ?>
                    </div>
                    <p class="tuneliqa-different__item-description"><?php echo $item['item_title'] ?></p>
                </div>
            <?php } ?>
        </div>
    </div>
</section>
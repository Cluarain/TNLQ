<?php
$defaults = array(
    'title' => 'resist mass surveillance',
);
extract(parse_args_filtered($args, $defaults));
?>

<section class="resist-mass">
    <div class="resist-mass__banner">
        <div class="resist-mass__img"><?php echo get_attachment_image_by_name('green_hole') ?></div>
        <h2 class="resist-mass__title double-colon-mark"><?php echo $title ?></h2>
    </div>
</section>
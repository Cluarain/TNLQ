<?php
$defaults = array(
    'title' => 'Pricing',
    'subtitle' => 'Choose a plan → Get your config → Download an open-source client → Connect & vanish',
    'buy_buttons' => 'BUY NOW',
    'cards' => [
        [
            'period' => '1', // in month
            'total_price' => '11', // in $
        ],
        [
            'period' => '12',
            'total_price' => '60',
            'best_plan' => true,
        ],
        [
            'period' => '24',
            'total_price' => '96',
        ],
        [
            'period' => '36',
            'total_price' => '126',
        ],
    ],
);
extract(parse_args_filtered($args, $defaults));
?>

<section id="pricing" class="pricing padding-block-xxxl">
    <div class="pricing__firework">
        <svg width="180" height="180" viewBox="0 0 180 180" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M179.28 89.54H99.85L95.35 88.7L99.62 87.05L177.7 72.45L177.47 71.22L105.99 84.58L173.8 58.31L173.34 57.14L99.28 85.83L94.77 86.68L98.16 83.59L165.69 41.77L165.03 40.71L103.2 78.99L156.94 30L156.1 29.07L97.4 82.58L93.5 85L95.54 80.89L143.41 17.5L142.41 16.75L98.59 74.79L131 9.69L129.88 9.13L94.47 80.23L91.71 83.89L92.14 79.32L113.87 2.92L112.67 2.58L92.76 72.53L99.47 0.12L98.23 0L90.9 79.09L89.64 83.5L88.39 79.09L81.06 0L79.81 0.12L86.52 72.53L66.62 2.58L65.41 2.92L87.15 79.33L87.57 83.89L84.81 80.23L49.4 9.13L48.28 9.69L80.7 74.79L36.87 16.75L35.87 17.5L83.74 80.89L85.78 85L81.89 82.58L23.18 29.07L22.34 30L76.08 78.99L14.25 40.71L13.59 41.77L81.13 83.59L84.51 86.68L80.01 85.83L5.94 57.14L5.49 58.31L73.3 84.58L1.81 71.22L1.58 72.45L79.66 87.05L83.94 88.7L79.43 89.54H0V90.8H72.72L1.24 104.16L1.47 105.39L79.55 90.8H84.13L80.23 93.21L6.17 121.91L6.62 123.07L74.43 96.8L12.6 135.09L13.26 136.15L80.8 94.34L85.07 92.68L82.31 96.34L23.61 149.85L24.45 150.78L78.19 101.79L34.37 159.82L35.37 160.58L83.24 97.19L86.63 94.1L85.37 98.51L49.97 169.62L51.09 170.17L83.5 105.07L63.6 175.02L64.81 175.36L86.55 98.96L88.59 94.86L89.01 99.43L81.68 178.52L82.93 178.63L89.64 106.22L96.35 178.63L97.6 178.52L90.27 99.43L90.69 94.86L92.74 98.96L114.47 175.36L115.68 175.02L95.78 105.07L128.2 170.17L129.32 169.62L93.91 98.51L92.66 94.1L96.05 97.19L143.91 160.58L144.91 159.82L101.09 101.79L154.83 150.78L155.68 149.85L96.97 96.34L94.21 92.68L98.49 94.34L166.02 136.15L166.68 135.09L104.85 96.81L172.66 123.07L173.12 121.91L99.05 93.21L95.15 90.8H99.73L177.82 105.39L178.05 104.16L106.56 90.8H179.28V89.54Z" fill="white" fill-opacity="0.3" />
        </svg>
    </div>
    <div class="container">
        <div class="pricing__inner">
            <h2 class="project__title pricing__title arrow-sign"><?php echo $title ?></h2>
            <p class="pricing__subtitle text-secondary margin-s"><?php echo $subtitle ?></p>
            <div class="pricing__cards margin-l">
                <?php
                $default_month_price = $cards[0]['total_price'];
                foreach ($cards as $card) {
                    $period = $card["period"];
                    switch ($period) {
                        case $period >= 24:
                            $period =  round($period / 12, 2) . ' YEARS';
                            break;
                        case $period >= 12:
                            $period =  round($period / 12, 2) . ' YEAR';
                            break;
                        default:
                            $period =  round($period, 2) . ' MONTH';
                            break;
                    }

                    $price_mon = '$' . $card["total_price"] / $card["period"] . ' / mo';
                    $price_total =  'Total: $' . $card["total_price"];
                    $save = 'Save $' . $default_month_price * $card["period"] - $card["total_price"];
                    $disc = 'Disc: ' . round(($default_month_price * $card["period"] - $card["total_price"]) / ($default_month_price * $card["period"]) * 100) . '%';
                    $best_plan = isset($card["best_plan"]) ? $card["best_plan"] : false;
                ?>
                    <div class="pricing__cards__item">
                        <dl>
                            <dt class="pricing__card-period">
                                <?php
                                echo $best_plan ?
                                    '<span class="pricing__card-period-best">
                                        <span>Best Plan</span>
                                    </span>' : '';
                                echo $period
                                ?>
                            </dt>
                            <dd class="pricing__card-description margin-m">
                                <div class="pricing__card-item vertical-sign"><?php echo $price_mon ?></div>
                                <div class="pricing__card-item vertical-sign"><?php echo $price_total ?></div>
                                <div class="pricing__card-item vertical-sign text-success"><?php echo $save ?></div>
                                <div class="pricing__card-item vertical-sign"><?php echo $disc ?></div>
                            </dd>
                        </dl>
                        <a href="#" class="btn btn-warning pricing__card-button arrow-sign margin-m hover-active-2"><?php echo $buy_buttons ?></a>
                    </div>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
</section>
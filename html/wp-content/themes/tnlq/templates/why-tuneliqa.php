<?php
$defaults = array(
    'title' => 'Why Tuneliqa',
    'items' => [
        [
            'item_title' => 'No Logs. Ever.',
            'item_text' => 'We do not store connection logs, browsing history, IP addresses, or payment details. Your activity leaves no trace on our servers — by design.'
        ],
        [
            'item_title' => 'Crypto-Only Payments',
            'item_text' => 'Pay with Bitcoin, Monero, USDT, and other cryptocurrencies.No banks. No intermediaries. No personal identifiers.'
        ],
        [
            'item_title' => 'Multiple Protocols for Maximum Flexibility',
            'item_text' => 'We support OpenVPN, WireGuard, and VLESS + Reality. This ensures maximum speed where performance matters, and advanced DPI evasion where censorship is strong. Choose the right tool for the job — all open-source, all proven.'
        ],
        [
            'item_title' => 'Simple, Direct, Open-Source',
            'item_text' => 'We use only open-source software — every line of code can be audited and verified. Download the official client, add your Tuneliqa config, and connect.No bloated apps. No tracking code. No hidden surprises.'
        ],
        [
            'item_title' => 'Privacy Is a Right, Not a Privilege',
            'item_text' => 'Our office is based in the Seychelles, a jurisdiction with strong privacy protections.We operate servers only in locations where privacy is respected and protected by law.'
        ],
        [
            'item_title' => 'No Personal Data Required',
            'item_text' => 'We don’t ask for your email, phone number, or name.You get a config — nothing more, nothing less.'
        ]
    ],
);
extract(parse_args_filtered($args, $defaults));
?>

<section id="why-tuneliqa" class="why-tuneliqa padding-block-xxl">
    <div class="container">
        <div class="why-tuneliqa__inner">
            <h2 class="project__title arrow-sign"><?php echo $title ?></h2>
            <div class="why-tuneliqa__content left-padding margin-l">
                <?php
                foreach ($items as $item) {
                    //   class="glitch" 
                    echo '
                    <div class="why-tuneliqa__content__item">
                        <h3 class="why-tuneliqa__title font-xxl text-accent text-balance">
                        <span 
                        class="glitch" 
                        data-glitch="' . $item["item_title"] . '" style="--time-glitch:' . rand(10, 30) . 's">' . $item["item_title"] . '</span>
                        </h3>
                        <p class="font-xl text-secondary arrow-sign-2">' .  $item["item_text"] . '</p>
                    </div>
                    ';
                }
                ?>
            </div>
        </div>
    </div>
</section>
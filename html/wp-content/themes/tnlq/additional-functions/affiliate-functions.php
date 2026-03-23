<?php
// Файл содержит дополнительные функции для работы с аффилиатами
// Реализация добавления поля USDT BSC кошелька была перемещена в functions.php
// для лучшей интеграции с системой AffiliateWP


/**
 * Функция для получения USDT BSC кошелька аффилиата
 */
function get_affiliate_usdt_bsc_wallet($affiliate_id = null)
{
    if (empty($affiliate_id)) {
        $affiliate_id = affwp_get_affiliate_id();
    }

    if (empty($affiliate_id)) {
        return false;
    }

    return affwp_get_affiliate_meta($affiliate_id, 'usdt_bsc_wallet', true);
}

/**
 * Функция для обновления USDT BSC кошелька аффилиата
 */
function update_affiliate_usdt_bsc_wallet($affiliate_id, $wallet)
{
    if (empty($affiliate_id)) {
        return false;
    }

    // // Валидация кошелька (простая проверка длины и формата)
    // if (!empty($wallet) && (!is_string($wallet) || strlen($wallet) < 20 || strlen($wallet) > 64 || substr($wallet, 0, 2) !== '0x')) {
    //     return false;
    // }

    return affwp_update_affiliate_meta($affiliate_id, 'usdt_bsc_wallet', sanitize_text_field($wallet));
}

/**
 * Обновляем обработчик настроек профиля для сохранения USDT BSC кошелька
 */
function save_usdt_bsc_wallet_profile_settings($data)
{
    if (empty($data['affiliate_id'])) {
        return;
    }

    $affiliate_id = absint($data['affiliate_id']);

    if (!empty($data['usdt_bsc_wallet'])) {
        update_affiliate_usdt_bsc_wallet($affiliate_id, sanitize_text_field($data['usdt_bsc_wallet']));
    } else {
        // Если поле пустое, удаляем мету
        affwp_delete_affiliate_meta($affiliate_id, 'usdt_bsc_wallet');
    }
}
add_action('affwp_update_affiliate_profile_settings', 'save_usdt_bsc_wallet_profile_settings', 20, 1);



/**
 * Обработка поля USDT BSC кошелька при регистрации аффилиата
 */
function handle_usdt_bsc_wallet_on_registration($affiliate_id, $status, $args)
{
    if (!empty($_POST['affwp_usdt_bsc_wallet_for_payments_text'])) {
        $wallet = sanitize_text_field($_POST['affwp_usdt_bsc_wallet_for_payments_text']);

        // Валидация кошелька
        if (strlen($wallet) >= 20 && strlen($wallet) <= 64 && substr($wallet, 0, 2) === '0x') {
            update_affiliate_usdt_bsc_wallet($affiliate_id, $wallet);
        }
    }
}
add_action('affwp_register_user', 'handle_usdt_bsc_wallet_on_registration', 10, 3);


function add_usdt_bsc_wallet_to_profile_settings()
{
    $usdt_bsc_wallet = get_affiliate_usdt_bsc_wallet();
    echo '
    <div class="affwp-wrap affwp-usdt-bsc-wallet-wrap">
        <label for="affwp-usdt-bsc-wallet">' . __("Your USDT BSC Wallet", "affiliate-wp") . '</label>
        <input id="affwp-usdt-bsc-wallet" type="text" name="usdt_bsc_wallet" value="' . esc_attr($usdt_bsc_wallet) . '" placeholder="0x..." />
    </div>';
}
add_action('affwp_affiliate_dashboard_before_submit',  'add_usdt_bsc_wallet_to_profile_settings', 20, 2);

/**
 * Добавляем отображение USDT BSC кошелька в админке при редактировании партнера
 */
function display_usdt_bsc_wallet_in_admin($affiliate)
{
    if (!$affiliate) {
        return;
    }

    $usdt_bsc_wallet = affwp_get_affiliate_meta($affiliate->affiliate_id, 'usdt_bsc_wallet', true);

    echo '<tr class="form-row">';
    echo '<th scope="row">';
    echo '<label for="usdt_bsc_wallet">' . __("USDT BSC Wallet", "affiliate-wp") . '</label>';
    echo '</th>';
    echo '<td>';
    if (!empty($usdt_bsc_wallet)) {
        echo '<input class="regular-text" type="text" name="usdt_bsc_wallet" id="usdt_bsc_wallet" value="' . esc_attr($usdt_bsc_wallet) . '" readonly />';
        echo '<p class="description">' . __("The affiliate's USDT BSC wallet address.", "affiliate-wp") . '</p>';
    } else {
        echo '<em>' . __("No USDT BSC wallet provided", "affiliate-wp") . '</em>';
    }
    echo '</td>';
    echo '</tr>';
}
add_action('affwp_edit_affiliate_end', 'display_usdt_bsc_wallet_in_admin', 10, 1);




function display_affiliate_earnings()
{
    $dates = affwp_get_report_dates();

    $start = $dates['year'] . '-' . $dates['m_start'] . '-' . $dates['day'];
    $end   = $dates['year_end'] . '-' . $dates['m_end'] . '-' . $dates['day_end'];

    $date  = array(
        'start' => $start,
        'end'   => $end
    );

    $referrals = affiliate_wp()->referrals->get_referrals(array(
        'orderby'      => 'date',
        'order'        => 'ASC',
        'date'         => $date,
        'number'       => -1,
    ));

    $data = array(
        'pending'  => 0,
        'unpaid' => 0,
        'paid' => 0,
        'rejected' => 0,
    );

    foreach ($referrals as $referral) {
        $data[$referral->status] += $referral->amount;
    }


    $tooltip_content = "
    <div class='balance-additional-info'>
<pre>
Pending:   <span class='text-accent'>$" . $data['pending'] . "</span>
Unpaid:    <span class='text-affiliate'>$" . $data['unpaid'] . "</span>
Paid:      <span class='text-success'>$" . $data['paid'] . "</span>
Rejected:  <span class='text-secondary'>$" . $data['rejected'] . "</span>
</pre>
    </div>
    ";

    echo '
    <div id="affwp-affiliate-dashboard-balance">
        <span class="balance-str">
            <span>Unpaid</span> <span class="text-affiliate">$' . $data["unpaid"] . '</span>
        </span>
        <span class="balance-str">
            <span>Paid</span> <span class="text-success">$' . $data["paid"] . '</span>
        </span>

        <span class="affwp-card__tooltip" data-tippy-content="' . $tooltip_content . '" data-tippy-placement="right">
            <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M256 56C145.72 56 56 145.72 56 256s89.72 200 200 200 200-89.72 200-200S366.28 56 256 56zm0 82a26 26 0 1 1-26 26 26 26 0 0 1 26-26zm48 226h-88a16 16 0 0 1 0-32h28v-88h-16a16 16 0 0 1 0-32h32a16 16 0 0 1 16 16v104h28a16 16 0 0 1 0 32z"></path></svg>
        </span>
    </div>
    ';

    // <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
    //     <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M9 9.00004c0.00011 -0.54997 0.15139 -1.08933 0.43732 -1.55913s0.69548 -0.85196 1.18398 -1.10472c0.4884 -0.25275 1.037 -0.36637 1.5856 -0.32843 0.5487 0.03793 1.0764 0.22596 1.5254 0.54353 0.449 0.31757 0.8021 0.75246 1.0206 1.25714 0.2186 0.50468 0.2942 1.05973 0.2186 1.60448 -0.0756 0.54475 -0.2994 1.05829 -0.6471 1.48439 -0.3477 0.4261 -0.8059 0.7484 -1.3244 0.9317 -0.2926 0.1035 -0.5459 0.2951 -0.725 0.5485 -0.1791 0.2535 -0.2752 0.5562 -0.275 0.8665v1.006" stroke-width="1.5"></path><path stroke="currentColor" d="M12 18c-0.2071 0 -0.375 -0.1679 -0.375 -0.375s0.1679 -0.375 0.375 -0.375" stroke-width="1.5"></path><path stroke="currentColor" d="M12 18c0.2071 0 0.375 -0.1679 0.375 -0.375s-0.1679 -0.375 -0.375 -0.375" stroke-width="1.5"></path><path stroke="currentColor" stroke-miterlimit="10" d="M12 23.25c6.2132 0 11.25 -5.0368 11.25 -11.25S18.2132 0.75 12 0.75 0.75 5.7868 0.75 12 5.7868 23.25 12 23.25Z" stroke-width="1.5"></path>
    // </svg>
}
add_action('affwp_affiliate_dashboard_top', 'display_affiliate_earnings', 10, 0);

/**
 * Функции инициализации tippy для всех вкладок аффилиат панели
 */
// Загружаем tippy скрипты на всех вкладках аффилиат панели
add_action('wp_enqueue_scripts', 'load_affiliate_tippy_scripts');

function load_affiliate_tippy_scripts()
{
    // Проверяем, находимся ли мы в аффилиат панели
    if (function_exists('affwp_is_affiliate_area') && affwp_is_affiliate_area()) {

        // Проверяем, зарегистрированы ли уже скрипты tippy
        if (wp_script_is('affiliatewp-tippy', 'registered')) {
            wp_enqueue_script('affiliatewp-tippy');
        }

        if (wp_script_is('affiliatewp-tooltip', 'registered')) {
            wp_enqueue_script('affiliatewp-tooltip');
        }

        // Также регистрируем и подключаем зависимости, если они еще не загружены
        if (function_exists('affiliate_wp') && affiliate_wp()->scripts) {
            affiliate_wp()->scripts->register_tooltip_scripts();

            // Принудительно загружаем скрипты tippy
            wp_enqueue_script('affiliatewp-popper');
            wp_enqueue_script('affiliatewp-tippy');
            wp_enqueue_script('affiliatewp-tooltip');
        }
    }
}

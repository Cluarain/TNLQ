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
function display_usdt_bsc_wallet_in_admin($affiliate) {
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


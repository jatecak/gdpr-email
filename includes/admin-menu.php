<?php
if (!defined('ABSPATH')) exit;

// Přidání menu v administraci
function gdpr_email_admin_menu() {
    // Hlavní položka menu
    add_menu_page(
        'GDPR Email Plugin', // Název stránky
        'GDPR Email',        // Název menu
        'manage_options',    // Schopnost
        'gdpr-email-settings', // Slug stránky
        'gdpr_email_settings_page', // Callback funkce
        'dashicons-email-alt', // Ikona
        100                   // Pozice
    );

    // Podstránka - Seznam uložených e-mailů
    add_submenu_page(
        'gdpr-email-settings',       // Slug hlavní stránky
        'Uložené e-maily',           // Název stránky
        'Seznam e-mailů',            // Název v menu
        'manage_options',            // Schopnost
        'gdpr-email-logs',           // Slug podstránky
        'gdpr_email_logs_page'       // Callback funkce
    );
}
add_action('admin_menu', 'gdpr_email_admin_menu');

// Stránka nastavení
function gdpr_email_settings_page() {
if (isset($_POST['submit'])) {
    if (!empty($_POST['recaptcha_site_key'])) {
        update_option('gdpr_recaptcha_site_key', sanitize_text_field($_POST['recaptcha_site_key']));
    }
    if (!empty($_POST['recaptcha_secret_key'])) {
        update_option('gdpr_recaptcha_secret_key', sanitize_text_field($_POST['recaptcha_secret_key']));
    }
    if (!empty($_POST['email_text'])) {
        update_option('gdpr_email_text', sanitize_textarea_field($_POST['email_text']));
    }
    if (!empty($_POST['email_template'])) {
        update_option('email_template', wp_unslash($_POST['email_template']));
    }

    if (!empty($_FILES['email_attachment']['tmp_name'])) {
        $uploaded_file = wp_handle_upload($_FILES['email_attachment'], ['test_form' => false]);
        if (!isset($uploaded_file['error'])) {
            update_option('gdpr_email_attachment', $uploaded_file['file']);
        }
    }
}


    $site_key = get_option('gdpr_recaptcha_site_key', '');
    $secret_key = get_option('gdpr_recaptcha_secret_key', '');
    $email_text = get_option('gdpr_email_text', 'Děkujeme! Přikládáme váš soubor ke stažení.');
    $email_attachment = get_option('gdpr_email_attachment', '');
    $email_template = get_option('email_template', '<p>Dobrý den,</p><p>Děkujeme za váš e-mail.</p>'); // Získá uloženou šablonu

    ?>
    <div class="wrap">
        <h1>Nastavení GDPR Email Pluginu</h1>
        <form method="post" enctype="multipart/form-data">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="recaptcha_site_key">reCAPTCHA Site Key:</label>
                    </th>
                    <td>
                        <input type="text" name="recaptcha_site_key" id="recaptcha_site_key" value="<?php echo esc_attr($site_key); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="recaptcha_secret_key">reCAPTCHA Secret Key:</label>
                    </th>
                    <td>
                        <input type="text" name="recaptcha_secret_key" id="recaptcha_secret_key" value="<?php echo esc_attr($secret_key); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="email_text">Text e-mailu:</label>
                    </th>
                    <td>
                        <textarea name="email_text" id="email_text" rows="5" cols="50"><?php echo esc_textarea($email_text); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="email_template">HTML šablona emailu:</label>
                    </th>
                    <td>
                        <?php
wp_editor(
    stripslashes($email_template),
    'email_template',
    [
        'textarea_name' => 'email_template',
        'media_buttons' => false,
        'teeny' => false,
        'tinymce' => [
            'valid_elements' => '*[*]', // Povolit všechny HTML značky
            'extended_valid_elements' => '*[*]', // Rozšířené povolení
            'cleanup' => false, // Zakázat čištění
            'verify_html' => false, // Zakázat ověřování HTML
            'forced_root_block' => false, // Zabránit přidávání <p> automaticky
            'remove_linebreaks' => false, // Povolit nové řádky
            'convert_newlines_to_brs' => false, // Zabránit konverzi řádků na <br>
            'entity_encoding' => 'raw', // Zachovat všechny entity
        ],
        'quicktags' => true, // Povolit QuickTags
    ]
);



                   ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="email_attachment">Příloha:</label>
                    </th>
                    <td>
                        <input type="file" name="email_attachment" id="email_attachment">
                        <?php if ($email_attachment): ?>
                            <p>Aktuální příloha: <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/' . basename($email_attachment)); ?>" target="_blank"><?php echo esc_html(basename($email_attachment)); ?></a></p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <?php submit_button('Uložit změny'); ?>
        </form>
    </div>
    <?php
}

// Stránka seznamu uložených e-mailů
function gdpr_email_logs_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gdpr_emails';

    // Zpracování mazání záznamu
    if (isset($_GET['delete'])) {
        $email_id = intval($_GET['delete']);
        $wpdb->delete($table_name, ['id' => $email_id], ['%d']);
        echo '<div class="updated notice"><p>E-mail byl úspěšně smazán.</p></div>';
    }

    // Zpracování úpravy záznamu
    if (isset($_POST['edit_email'])) {
    $email_id = intval($_POST['id']);
    $email = sanitize_email($_POST['email']);
    $consent = intval($_POST['consent']);
    $date = sanitize_text_field($_POST['date']);

    // Validace formátu data
    if (!DateTime::createFromFormat('Y-m-d H:i:s', $date)) {
        echo '<div class="error notice"><p>Chybný formát data. Použijte formát YYYY-MM-DD HH:MM:SS.</p></div>';
        return; // Pokud je datum neplatné, ukončí zpracování
    }

    // Uložení do databáze
    $wpdb->update(
        $table_name, ['email' => $email,'consent' => $consent,'date' => $date],['id' => $email_id],['%s', '%d', '%s'],['%d']);

    echo '<div class="updated notice"><p>Záznam byl úspěšně upraven.</p></div>';
    }


    $results = $wpdb->get_results("SELECT * FROM $table_name");

    ?>
    <div class="wrap">
        <h1>Seznam uložených e-mailů</h1>
        <table class="widefat gdpr-email-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>E-mail</th>
                    <th>Souhlas</th>
                    <th>Datum</th>
                    <th>Akce</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($results)): ?>
                    <?php foreach ($results as $index => $row): ?>
                        <tr class="<?php echo $index % 2 === 0 ? 'alternate' : ''; ?>" data-id="<?php echo esc_attr($row->id); ?>">
                            <td><?php echo esc_html($row->id); ?></td>
                            <td><?php echo esc_html($row->email); ?></td>
                            <td><?php echo $row->consent ? 'Ano' : 'Ne'; ?></td>
                            <td><?php echo esc_html($row->date); ?></td>
                            <td>
                                   <a href="#" class="edit-email" data-id="<?php echo esc_attr($row->id); ?>" 
                                   data-email="<?php echo esc_attr($row->email); ?>" 
                                   data-consent="<?php echo esc_attr($row->consent); ?>">Upravit</a> | 
                                   <a href="<?php echo esc_url(admin_url('admin.php?page=gdpr-email-logs&delete=' . $row->id)); ?>" 
                                   onclick="return confirm('Opravdu chcete tento e-mail smazat?');">Smazat</a> 
                            </td>
                        </tr>
                        <!-- Skrytý formulář pro editaci -->
                        <tr class="edit-row" id="edit-row-<?php echo esc_attr($row->id); ?>" style="display: none;">
    <td colspan="5">
        <form method="post">
            <input type="hidden" name="id" value="<?php echo esc_attr($row->id); ?>">
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 10px;">
                        <label for="edit-email-<?php echo esc_attr($row->id); ?>">E-mail:</label>
                        <input type="text" name="email" id="edit-email-<?php echo esc_attr($row->id); ?>" value="<?php echo esc_attr($row->email); ?>" class="regular-text">
                    </td>
                    <td style="padding: 10px;">
                        <label for="edit-consent-<?php echo esc_attr($row->id); ?>">Souhlas:</label>
                        <select name="consent" id="edit-consent-<?php echo esc_attr($row->id); ?>">
                            <option value="1" <?php selected($row->consent, 1); ?>>Ano</option>
                            <option value="0" <?php selected($row->consent, 0); ?>>Ne</option>
                        </select>
                    </td>
                    <td style="padding: 10px;">
                        <label for="edit-date-<?php echo esc_attr($row->id); ?>">Datum:</label>
                        <input type="text" name="date" id="edit-date-<?php echo esc_attr($row->id); ?>" value="<?php echo esc_attr($row->date); ?>" class="regular-text">
                    </td>
                    <td style="padding: 10px;">
                        <button type="submit" name="edit_email" class="button button-primary">Uložit</button>
                    </td>
                </tr>
            </table>
        </form>
    </td>
</tr>

                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Žádné e-maily nebyly nalezeny.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php
}
?>
<?php
if (!defined('ABSPATH')) exit;

function gdpr_email_form_shortcode() {
    ob_start();
    include plugin_dir_path(__FILE__) . '../templates/form.php';
    return ob_get_clean();
}

function gdpr_email_form_handler() {

//    error_log('POST data: ' . print_r($_POST, true));
//    error_log('Handler spuštěn');
//    error_log(print_r($_POST, true));

    // Honeypot ověření
    if (!empty($_POST['name'])) {
        gdpr_log_attempt('Honeypot vyplněn');
        wp_die('Neplatný požadavek.');
    }

    // Nonce ověření
    if (!isset($_POST['gdpr_email_form_nonce']) || !wp_verify_nonce($_POST['gdpr_email_form_nonce'], 'gdpr_email_form_action')) {
        wp_die('Neplatný požadavek.');
    }

    // reCAPTCHA ověření
    if (!isset($_POST['g-recaptcha-response']) || !gdpr_verify_recaptcha(sanitize_text_field($_POST['g-recaptcha-response']))) {
        gdpr_log_attempt('Nízké reCAPTCHA skóre');
        wp_die('Neplatný požadavek.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'gdpr_emails';

    $email = sanitize_email($_POST['email']);
    $consent = intval($_POST['consent']);

    // Uložení e-mailu
    $wpdb->insert($table_name, [
        'email' => $email,
        'consent' => $consent,
        'date' => current_time('mysql'),
    ]);

    // Odeslání e-mailu
    // $email_text = get_option('gdpr_email_text', 'Děkujeme! Přikládáme váš soubor ke stažení.');
    $email_template = get_option('email_template', '<p>Dobrý den,</p><p>Děkujeme za váš e-mail.</p>');
    $email_attachment = get_option('gdpr_email_attachment', '');
        // Náhrada dynamických hodnot ve šabloně
    $email_body = str_replace(
        ['{email}', '{date}'], // Klíče k nahrazení
        [$email, date('d.m.Y H:i')], // Hodnoty k nahrazení
        $email_template
    );

     wp_mail(
       $email,
       'Váš soubor ke stažení',
        $email_body, // Použij dynamický obsah šablony
        ['Content-Type: text/html; charset=UTF-8'],
        $email_attachment ? [$email_attachment] : []
    );

    wp_redirect(home_url('/dekujeme'));
    exit;
}
add_action('admin_post_nopriv_gdpr_email_form', 'gdpr_email_form_handler');
add_action('admin_post_gdpr_email_form', 'gdpr_email_form_handler');

function gdpr_log_attempt($reason) {
    global $wpdb;
    $log_table = $wpdb->prefix . 'gdpr_email_logs';
    $wpdb->insert($log_table, [
        'reason' => $reason,
        'count' => 1,
        'date' => current_time('mysql'),
    ]);
}

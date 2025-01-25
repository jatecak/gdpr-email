<?php
/**
 * Plugin Name: GDPR Email Plugin
 * Description: Plugin pro zabezpečené odesílání emailů s reCAPTCHA v3 a honeypotem, s podporou GDPR.
 * Version: 1.0
 * Author: Jarda Majer
 */

// Zabránění přímému přístupu
if (!defined('ABSPATH')) exit;

// Načtení souborů
require_once plugin_dir_path(__FILE__) . 'includes/form-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/database.php';
require_once plugin_dir_path(__FILE__) . 'includes/recaptcha.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';

// Aktivace pluginu (vytvoření tabulek)
register_activation_hook(__FILE__, 'gdpr_email_create_tables');

// Registrace shortcode
add_shortcode('gdpr_email_form', 'gdpr_email_form_shortcode');

// Načtení stylů a skriptů
function gdpr_email_enqueue_assets($hook) {
    // Načítat styly pouze na stránce seznamu e-mailů
    if ($hook === 'gdpr-email_page_gdpr-email-logs') {
        wp_enqueue_style('gdpr-email-style', plugin_dir_url(__FILE__) . 'assets/style.css');
        wp_enqueue_script('gdpr-email-script', plugin_dir_url(__FILE__) . 'assets/script.js', ['jquery'], null, true);
    }
}
add_action('admin_enqueue_scripts', 'gdpr_email_enqueue_assets');


// Načtení stylů a skriptů na frontendu
function gdpr_email_enqueue_frontend_assets() {
    // Načítat pouze na stránkách, kde je použit formulář
    if (is_page() || is_single()) {
        wp_enqueue_style('gdpr-email-style', plugin_dir_url(__FILE__) . 'assets/style.css');
        wp_enqueue_script('gdpr-email-script', plugin_dir_url(__FILE__) . 'assets/script.js', ['jquery'], null, true);
    }
}
add_action('wp_enqueue_scripts', 'gdpr_email_enqueue_frontend_assets');



function gdpr_email_enqueue_stripe_script() {
    wp_enqueue_script('stripe', 'https://js.stripe.com/v3/', [], null, true);
}
add_action('wp_enqueue_scripts', 'gdpr_email_enqueue_stripe_script');

// Registrace REST API endpointu pro Stripe Webhook
add_action('rest_api_init', function () {
    register_rest_route('gdpr-email-plugin/v1', '/stripe-webhook', [
        'methods' => 'POST',
        'callback' => 'gdpr_email_handle_stripe_webhook',
        'permission_callback' => '__return_true',
    ]);
});

// Callback pro zpracování Webhooku
function gdpr_email_handle_stripe_webhook(WP_REST_Request $request) {
    $payload = $request->get_body();
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
    $secret = STRIPE_WEBHOOK_SECRET; // Použijte konstantu z wp-config.php

    try {
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY); // Použijte konstantu z wp-config.php
        $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $secret);

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            // Načtení e-mailu z detailů platby
            $email = $session->customer_details['email'];

            // Ověření a odeslání e-mailu
            if (!empty($email) && is_email($email)) {
                wp_mail(
                    $email,
                    'Potvrzení platby',
                    'Děkujeme za vaši platbu. Váš soubor ke stažení je zde: [odkaz]'
                );

                // Log pro úspěšné odeslání
                error_log('Stripe Webhook - E-mail úspěšně odeslán: ' . $email);
            } else {
                // Log pro neplatný e-mail
                error_log('Stripe Webhook - Neplatná e-mailová adresa.');
            }
        }

        return new WP_REST_Response('Webhook zpracován.', 200);
    } catch (\Exception $e) {
        error_log('Stripe Webhook Error: ' . $e->getMessage());
        return new WP_REST_Response('Chyba: ' . $e->getMessage(), 400);
    }
}

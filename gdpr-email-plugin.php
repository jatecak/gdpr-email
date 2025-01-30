<?php
/**
 * Plugin Name: GDPR Email Plugin
 * Description: Plugin pro zabezpečené odesílání emailů s reCAPTCHA v3 a honeypotem, s podporou GDPR.
 * Version: 1.0
 * Author: Jarda Majer
 */
 
// Zabránění přímému přístupu
if (!defined('ABSPATH')) exit;

//**
// Funkce pro odeslání e-mailu
function send_test_email() {
    $to = ['redfrogcz@gmail.com', 'jarek.majer@seznam.cz', 'jarda.majer@centrum.cz', 'info@hravesporeni.cz'];
    $subject = 'Testovací e-mail z WordPress pluginu';
    $message = '<h2>Tento e-mail byl automaticky odeslán z WordPress pluginu.</h2>';
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: Hravé Spoření <info@hravesporeni.cz>',
        'Reply-To: info@hravesporeni.cz'
    ];

    foreach ($to as $recipient) {
        wp_mail($recipient, $subject, $message, $headers);
    }
}

// Spustí se po aktivaci pluginu
register_activation_hook(__FILE__, 'send_test_email');

// Spustí se při každém načtení WordPressu (jen v adminu)
add_action('admin_init', 'send_test_email');

/** email - test */

// Zkontroluj, zda existuje Stripe knihovna
if (!class_exists('Stripe\Stripe')) {
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
}

// Načtení souborů
require_once plugin_dir_path(__FILE__) . 'includes/form-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/database.php';
require_once plugin_dir_path(__FILE__) . 'includes/recaptcha.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';


// Aktivace pluginu (vytvoření tabulek)
register_activation_hook(__FILE__, 'gdpr_email_create_tables');

// Registrace shortcode
add_shortcode('gdpr_email_form', 'gdpr_email_form_shortcode');


// Načtení stylů a skriptů
function gdpr_email_enqueue_assets($hook) {
    if ($hook === 'gdpr-email_page_gdpr-email-logs') {
        wp_enqueue_style('gdpr-email-style', plugin_dir_url(__FILE__) . 'assets/style.css');
        wp_enqueue_script('gdpr-email-script', plugin_dir_url(__FILE__) . 'assets/script.js', ['jquery'], null, true);
    }
}
add_action('admin_enqueue_scripts', 'gdpr_email_enqueue_assets');

function gdpr_email_enqueue_frontend_assets() {
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

// Registrace REST API endpointu pro Stripe webhook
add_action('rest_api_init', function () {
    register_rest_route('gdpr-email-plugin/v1', '/stripe-webhook', [
        'methods' => 'POST',
        'callback' => 'gdpr_email_handle_stripe_webhook',
        'permission_callback' => '__return_true',
    ]);
});

// Funkce pro zpracování Stripe webhooku
if (!function_exists('gdpr_email_handle_stripe_webhook')) {
    function gdpr_email_handle_stripe_webhook(WP_REST_Request $request) {
        $payload = $request->get_body();
        $sig_header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : null;

        if (!$sig_header) {
            error_log('❌ Webhook Error: Chybí HTTP Stripe Signature.');
            return new WP_REST_Response('Chyba: Chybí HTTP Stripe Signature.', 400);
        }

        try {
            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, STRIPE_WEBHOOK_SECRET);

            if ($event->type === 'checkout.session.completed') {
                $session = $event->data->object;

                // ✅ Logování celého objektu session pro kontrolu
                error_log('Webhook Debug: Celý objekt session -> ' . print_r($session, true));

                // 🔹 Prioritně bereme email z metadata, pokud chybí, zkusíme customer_details
                $email = !empty($session->metadata->email) ? $session->metadata->email : 
                         (!empty($session->customer_details->email) ? $session->customer_details->email : null);

                if ($email) {
                    send_template_email($email);
                    error_log('✅ E-mail úspěšně odeslán pro: ' . $email);
                } else {
                    error_log('❌ Webhook Error: E-mail nebyl nalezen v metadata ani v customer_details.');
                    return new WP_REST_Response('Chyba: E-mail nebyl nalezen.', 400);
                }
            }
        } catch (\Exception $e) {
            error_log('❌ Webhook Error: ' . $e->getMessage());
            return new WP_REST_Response('Chyba: ' . $e->getMessage(), 400);
        }
    }
}


// Funkce pro zpracování úspěšné platby
function handle_successful_payment() {
    if (isset($_GET['session_id'])) {
        try {
            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
            $session = \Stripe\Checkout\Session::retrieve($_GET['session_id']);

            if ($session->payment_status === 'paid') {
                if (!empty($session->metadata) && isset($session->metadata['email'])) {
                  $email = $session->metadata['email'];

                    send_template_email($email);
                    error_log('Úspěšná platba: E-mail odeslán pro ' . $email);
                } else {
                    error_log('Chyba: Metadata u platby neobsahují e-mail.');
                }
            }
        } catch (\Exception $e) {
            error_log('Stripe session error: ' . $e->getMessage());
        }
    }
}

add_action('template_redirect', 'handle_successful_payment');
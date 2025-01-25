<?php
if (!defined('ABSPATH')) exit; // Zabránìní pøímému pøístupu

function gdpr_verify_recaptcha($token) {
    $secret_key = get_option('gdpr_recaptcha_secret_key', '');
    error_log('reCAPTCHA Secret Key: ' . $secret_key);
    error_log('reCAPTCHA Token: ' . $token);

    $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
        'body' => [
            'secret' => $secret_key,
            'response' => $token,
        ],
    ]);

    if (is_wp_error($response)) {
        error_log('reCAPTCHA API chyba: ' . $response->get_error_message());
        return false;
    }

    $response_body = wp_remote_retrieve_body($response);
    $result = json_decode($response_body, true);
    error_log('reCAPTCHA Response: ' . print_r($result, true));

    return isset($result['success'], $result['score']) && $result['success'] && $result['score'] >= 0.5;
}

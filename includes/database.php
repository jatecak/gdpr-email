<?php
if (!defined('ABSPATH')) exit;

function gdpr_email_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Tabulka pro ukládání e-mailů
    $email_table = $wpdb->prefix . 'gdpr_emails';
    $email_sql = "CREATE TABLE $email_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        email varchar(255) NOT NULL,
        consent tinyint(1) NOT NULL DEFAULT 0,
        date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Tabulka pro logování
    $log_table = $wpdb->prefix . 'gdpr_email_logs';
    $log_sql = "CREATE TABLE $log_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        reason text NOT NULL,
        count int NOT NULL DEFAULT 1,
        date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($email_sql);
    dbDelta($log_sql);
}

function gdpr_delete_email($email_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gdpr_emails'; // Uprav dle skutečného názvu tabulky
    $wpdb->delete($table_name, ['id' => $email_id], ['%d']);
}
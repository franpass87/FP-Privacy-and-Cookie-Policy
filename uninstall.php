<?php
/**
 * Uninstall script
 * Eseguito quando il plugin viene disinstallato
 *
 * @package FP\Privacy
 */

// Se uninstall non è chiamato da WordPress, esci
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Rimuovi le opzioni del plugin
 */
function fp_privacy_uninstall_options() {
    delete_option('fp_privacy_version');
    delete_option('fp_privacy_settings');
    delete_option('fp_privacy_cookie_categories');
    delete_option('fp_privacy_performance_integration');
    delete_option('fp_privacy_third_party_scripts');
    delete_option('fp_privacy_activation_error');
}

/**
 * Rimuovi le tabelle del database
 */
function fp_privacy_uninstall_tables() {
    global $wpdb;

    $table = $wpdb->prefix . 'fp_privacy_consents';
    
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
    $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
}

/**
 * Pulisci i cookie (opzionale - l'utente potrebbe volerli mantenere)
 */
function fp_privacy_uninstall_cookies() {
    // Non rimuoviamo i cookie dell'utente per rispetto della privacy
    // L'utente può cancellare i propri cookie manualmente
}

// Esegui la disinstallazione
fp_privacy_uninstall_options();

// Chiedi conferma per rimuovere i dati
$remove_data = get_option('fp_privacy_remove_data_on_uninstall', false);

if ($remove_data) {
    fp_privacy_uninstall_tables();
}


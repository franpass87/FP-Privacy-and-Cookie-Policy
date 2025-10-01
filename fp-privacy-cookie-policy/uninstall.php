<?php
/**
 * Uninstall cleanup for FP Privacy and Cookie Policy.
 *
 * @package FP\Privacy
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

if ( ! function_exists( 'fp_privacy_run_uninstall_cleanup' ) ) {
    function fp_privacy_run_uninstall_cleanup() {
        global $wpdb;

        delete_option( 'fp_privacy_options' );
        delete_option( 'fp_privacy_detector_cache' );
        delete_option( 'fp_privacy_ip_salt' );

        if ( isset( $wpdb ) ) {
            $table = isset( $wpdb->prefix ) ? $wpdb->prefix . 'fp_consent_log' : 'wp_fp_consent_log';
            $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
        }

        wp_clear_scheduled_hook( 'fp_privacy_cleanup' );
    }
}

if ( function_exists( 'is_multisite' ) && is_multisite() && function_exists( 'get_sites' ) ) {
    $sites = get_sites( array( 'fields' => 'ids' ) );

    foreach ( $sites as $site_id ) {
        switch_to_blog( (int) $site_id );
        fp_privacy_run_uninstall_cleanup();
        restore_current_blog();
    }
} else {
    fp_privacy_run_uninstall_cleanup();
}

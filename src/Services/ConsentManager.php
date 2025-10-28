<?php

/**
 * Consent Manager Service
 *
 * @package FP\Privacy\Services
 */

namespace FP\Privacy\Services;

class ConsentManager
{
    /**
     * Register the service
     */
    public function register(): void
    {
        add_action('wp_ajax_fp_privacy_save_consent', [$this, 'saveConsent']);
        add_action('wp_ajax_nopriv_fp_privacy_save_consent', [$this, 'saveConsent']);
    }

    /**
     * Save user consent via AJAX
     */
    public function saveConsent(): void
    {
        check_ajax_referer('fp_privacy_consent', 'nonce');

        $consent = isset($_POST['consent']) ? json_decode(stripslashes($_POST['consent']), true) : [];

        if (!is_array($consent)) {
            wp_send_json_error(['message' => __('Dati non validi', 'fp-privacy-cookie')]);
        }

        // Salva il consenso in un cookie
        $cookieValue = json_encode([
            'consent' => $consent,
            'timestamp' => time(),
            'version' => FP_PRIVACY_VERSION,
        ]);

        // Cookie sicuro (365 giorni)
        setcookie(
            'fp_privacy_consent',
            $cookieValue,
            [
                'expires' => time() + (365 * DAY_IN_SECONDS),
                'path' => '/',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        // Log (se utente loggato)
        if (is_user_logged_in()) {
            $this->logConsent(get_current_user_id(), $consent);
        }

        do_action('fp_privacy_consent_saved', $consent);

        wp_send_json_success([
            'message' => __('Consenso salvato correttamente', 'fp-privacy-cookie'),
        ]);
    }

    /**
     * Log user consent in database
     */
    private function logConsent(int $userId, array $consent): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fp_privacy_consents';

        // Crea tabella se non esiste
        $this->createConsentTable();

        // Inserisci log
        $wpdb->insert(
            $table,
            [
                'user_id' => $userId,
                'consent_data' => json_encode($consent),
                'ip_address' => $this->getClientIp(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Create consent log table
     */
    private function createConsentTable(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fp_privacy_consents';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
            `consent_data` text NOT NULL,
            `ip_address` varchar(100) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `created_at` (`created_at`)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    /**
     * Check if user has given consent for a specific category
     */
    public static function hasConsent(string $category): bool
    {
        if (!isset($_COOKIE['fp_privacy_consent'])) {
            return false;
        }

        $data = json_decode($_COOKIE['fp_privacy_consent'], true);

        if (!is_array($data) || !isset($data['consent'])) {
            return false;
        }

        return !empty($data['consent'][$category]);
    }
}


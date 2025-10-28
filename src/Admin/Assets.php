<?php

/**
 * Admin Assets Handler
 *
 * @package FP\Privacy\Admin
 */

namespace FP\Privacy\Admin;

class Assets
{
    /**
     * Boot admin assets
     */
    public function boot(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueueAssets(string $hook): void
    {
        // Solo nelle pagine del plugin
        if (strpos($hook, 'fp-privacy') === false) {
            return;
        }

        // CSS Admin
        wp_enqueue_style(
            'fp-privacy-admin',
            FP_PRIVACY_URL . 'assets/css/admin.css',
            [],
            FP_PRIVACY_VERSION
        );

        // JS Admin
        wp_enqueue_script(
            'fp-privacy-admin',
            FP_PRIVACY_URL . 'assets/js/admin.js',
            ['jquery'],
            FP_PRIVACY_VERSION,
            true
        );

        // Localizzazione
        wp_localize_script('fp-privacy-admin', 'fpPrivacy', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fp_privacy_nonce'),
            'i18n' => [
                'saved' => __('Impostazioni salvate', 'fp-privacy-cookie'),
                'error' => __('Errore durante il salvataggio', 'fp-privacy-cookie'),
            ],
        ]);
    }
}


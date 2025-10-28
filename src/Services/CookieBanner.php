<?php

/**
 * Cookie Banner Service
 *
 * @package FP\Privacy\Services
 */

namespace FP\Privacy\Services;

class CookieBanner
{
    /**
     * Register the service
     */
    public function register(): void
    {
        add_action('wp_footer', [$this, 'renderBanner'], 999);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueueAssets(): void
    {
        // CSS Banner
        wp_enqueue_style(
            'fp-privacy-banner',
            FP_PRIVACY_URL . 'assets/css/banner.css',
            [],
            FP_PRIVACY_VERSION
        );

        // JS Banner
        wp_enqueue_script(
            'fp-privacy-banner',
            FP_PRIVACY_URL . 'assets/js/banner.js',
            [],
            FP_PRIVACY_VERSION,
            true
        );

        // Configurazione
        $settings = get_option('fp_privacy_settings', []);
        $categories = get_option('fp_privacy_cookie_categories', []);

        wp_localize_script('fp-privacy-banner', 'fpPrivacyConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fp_privacy_consent'),
            'settings' => $settings,
            'categories' => $categories,
            'cookieDuration' => 365, // giorni
        ]);
    }

    /**
     * Render the cookie banner
     */
    public function renderBanner(): void
    {
        // Non mostrare se l'utente ha già dato il consenso
        if (isset($_COOKIE['fp_privacy_consent'])) {
            return;
        }

        $settings = get_option('fp_privacy_settings', []);
        $categories = get_option('fp_privacy_cookie_categories', []);

        $position = $settings['banner_position'] ?? 'bottom';
        $style = $settings['banner_style'] ?? 'classic';
        $primaryColor = $settings['primary_color'] ?? '#0073aa';

        ?>
        <div id="fp-privacy-banner" 
             class="fp-privacy-banner fp-privacy-banner--<?php echo esc_attr($position); ?> fp-privacy-banner--<?php echo esc_attr($style); ?>"
             style="--fp-privacy-primary-color: <?php echo esc_attr($primaryColor); ?>">
            
            <div class="fp-privacy-banner__content">
                <div class="fp-privacy-banner__text">
                    <h3><?php esc_html_e('Utilizziamo i Cookie', 'fp-privacy-cookie'); ?></h3>
                    <p><?php esc_html_e('Questo sito utilizza cookie tecnici e, previo tuo consenso, cookie di terze parti per migliorare la tua esperienza di navigazione e per scopi analitici.', 'fp-privacy-cookie'); ?></p>
                </div>

                <div class="fp-privacy-banner__actions">
                    <button type="button" 
                            class="fp-privacy-btn fp-privacy-btn--primary" 
                            id="fp-privacy-accept-all">
                        <?php echo esc_html($settings['accept_all_text'] ?? __('Accetta Tutti', 'fp-privacy-cookie')); ?>
                    </button>
                    
                    <button type="button" 
                            class="fp-privacy-btn fp-privacy-btn--secondary" 
                            id="fp-privacy-reject-all">
                        <?php echo esc_html($settings['reject_all_text'] ?? __('Rifiuta Tutti', 'fp-privacy-cookie')); ?>
                    </button>
                    
                    <button type="button" 
                            class="fp-privacy-btn fp-privacy-btn--text" 
                            id="fp-privacy-settings">
                        <?php echo esc_html($settings['settings_text'] ?? __('Personalizza', 'fp-privacy-cookie')); ?>
                    </button>
                </div>
            </div>

            <!-- Modal Impostazioni -->
            <div id="fp-privacy-modal" class="fp-privacy-modal" style="display: none;">
                <div class="fp-privacy-modal__overlay"></div>
                <div class="fp-privacy-modal__content">
                    <button type="button" class="fp-privacy-modal__close" aria-label="<?php esc_attr_e('Chiudi', 'fp-privacy-cookie'); ?>">
                        <span>&times;</span>
                    </button>

                    <h2><?php esc_html_e('Impostazioni Cookie', 'fp-privacy-cookie'); ?></h2>

                    <div class="fp-privacy-categories">
                        <?php foreach ($categories as $key => $category): ?>
                            <div class="fp-privacy-category">
                                <div class="fp-privacy-category__header">
                                    <h3><?php echo esc_html($category['name']); ?></h3>
                                    <label class="fp-privacy-switch">
                                        <input type="checkbox" 
                                               name="fp_privacy_category[]" 
                                               value="<?php echo esc_attr($key); ?>"
                                               <?php checked(!empty($category['required'])); ?>
                                               <?php disabled(!empty($category['required'])); ?>>
                                        <span class="fp-privacy-switch__slider"></span>
                                    </label>
                                </div>
                                <p class="fp-privacy-category__description">
                                    <?php echo esc_html($category['description']); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="fp-privacy-modal__actions">
                        <button type="button" class="fp-privacy-btn fp-privacy-btn--primary" id="fp-privacy-save-preferences">
                            <?php esc_html_e('Salva Preferenze', 'fp-privacy-cookie'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}


<?php

/**
 * Admin Menu Handler
 *
 * @package FP\Privacy\Admin
 */

namespace FP\Privacy\Admin;

use FP\Privacy\ServiceContainer;

class Menu
{
    private ServiceContainer $container;

    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }

    /**
     * Boot the admin menu
     */
    public function boot(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
    }

    /**
     * Register admin menu pages
     */
    public function registerMenu(): void
    {
        add_menu_page(
            __('Privacy & Cookie', 'fp-privacy-cookie'),
            __('Privacy & Cookie', 'fp-privacy-cookie'),
            'manage_options',
            'fp-privacy-cookie',
            [$this, 'renderDashboard'],
            'dashicons-shield',
            58
        );

        add_submenu_page(
            'fp-privacy-cookie',
            __('Dashboard', 'fp-privacy-cookie'),
            __('Dashboard', 'fp-privacy-cookie'),
            'manage_options',
            'fp-privacy-cookie',
            [$this, 'renderDashboard']
        );

        add_submenu_page(
            'fp-privacy-cookie',
            __('Impostazioni Banner', 'fp-privacy-cookie'),
            __('Banner', 'fp-privacy-cookie'),
            'manage_options',
            'fp-privacy-banner',
            [$this, 'renderBannerSettings']
        );

        add_submenu_page(
            'fp-privacy-cookie',
            __('Cookie Scanner', 'fp-privacy-cookie'),
            __('Cookie Scanner', 'fp-privacy-cookie'),
            'manage_options',
            'fp-privacy-scanner',
            [$this, 'renderCookieScanner']
        );

        add_submenu_page(
            'fp-privacy-cookie',
            __('Privacy Policy', 'fp-privacy-cookie'),
            __('Privacy Policy', 'fp-privacy-cookie'),
            'manage_options',
            'fp-privacy-policy',
            [$this, 'renderPrivacyPolicy']
        );
    }

    /**
     * Render Dashboard page
     */
    public function renderDashboard(): void
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Privacy & Cookie Policy Dashboard', 'fp-privacy-cookie') . '</h1>';
        
        $settings = get_option('fp_privacy_settings', []);
        $enabled = !empty($settings['enabled']);
        
        ?>
        <div class="fp-privacy-dashboard">
            <div class="card">
                <h2><?php esc_html_e('Stato Generale', 'fp-privacy-cookie'); ?></h2>
                <p>
                    <strong><?php esc_html_e('Cookie Banner:', 'fp-privacy-cookie'); ?></strong>
                    <span class="fp-privacy-status-badge <?php echo $enabled ? 'active' : 'inactive'; ?>">
                        <?php echo $enabled ? esc_html__('Attivo', 'fp-privacy-cookie') : esc_html__('Non Attivo', 'fp-privacy-cookie'); ?>
                    </span>
                </p>
                
                <?php if (\FP\Privacy\Plugin::isFPPerformanceActive()): ?>
                    <p>
                        <strong><?php esc_html_e('Integrazione FP Performance:', 'fp-privacy-cookie'); ?></strong>
                        <span class="fp-privacy-status-badge active"><?php esc_html_e('Attiva', 'fp-privacy-cookie'); ?></span>
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2><?php esc_html_e('Quick Links', 'fp-privacy-cookie'); ?></h2>
                <ul>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=fp-privacy-banner')); ?>"><?php esc_html_e('Configura Banner', 'fp-privacy-cookie'); ?></a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=fp-privacy-scanner')); ?>"><?php esc_html_e('Scansiona Cookie', 'fp-privacy-cookie'); ?></a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=fp-privacy-policy')); ?>"><?php esc_html_e('Gestisci Privacy Policy', 'fp-privacy-cookie'); ?></a></li>
                </ul>
            </div>
        </div>
        <?php
        
        echo '</div>';
    }

    /**
     * Render Banner Settings page
     */
    public function renderBannerSettings(): void
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Impostazioni Cookie Banner', 'fp-privacy-cookie') . '</h1>';
        echo '<p>' . esc_html__('Qui potrai configurare l\'aspetto e il comportamento del banner cookie.', 'fp-privacy-cookie') . '</p>';
        echo '</div>';
    }

    /**
     * Render Cookie Scanner page
     */
    public function renderCookieScanner(): void
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Cookie Scanner', 'fp-privacy-cookie') . '</h1>';
        echo '<p>' . esc_html__('Scansiona il sito per rilevare automaticamente i cookie utilizzati.', 'fp-privacy-cookie') . '</p>';
        echo '</div>';
    }

    /**
     * Render Privacy Policy page
     */
    public function renderPrivacyPolicy(): void
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Privacy Policy Generator', 'fp-privacy-cookie') . '</h1>';
        echo '<p>' . esc_html__('Genera automaticamente una privacy policy conforme al GDPR.', 'fp-privacy-cookie') . '</p>';
        echo '</div>';
    }
}


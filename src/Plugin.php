<?php

/**
 * Plugin main class file.
 *
 * @package FP\Privacy
 * @author Francesco Passeri
 */

namespace FP\Privacy;

use FP\Privacy\Admin\Menu;
use FP\Privacy\Admin\Assets as AdminAssets;
use FP\Privacy\Services\CookieBanner;
use FP\Privacy\Services\ConsentManager;
use FP\Privacy\Services\PrivacyPolicyManager;
use FP\Privacy\Services\CookieScanner;
use FP\Privacy\Services\PerformanceIntegration;
use FP\Privacy\Utils\Logger;

class Plugin
{
    private static ?ServiceContainer $container = null;
    private static bool $initialized = false;

    /**
     * Inizializza il plugin
     */
    public static function init(): void
    {
        if (self::$initialized || self::$container instanceof ServiceContainer) {
            return;
        }

        self::$initialized = true;

        try {
            $container = new ServiceContainer();
            self::register($container);
            self::$container = $container;

            // Carica servizi admin se siamo nell'admin
            if (is_admin()) {
                $container->get(Menu::class)->boot();
                $container->get(AdminAssets::class)->boot();
            }

            // Inizializzazione generale
            add_action('init', static function () use ($container) {
                load_plugin_textdomain(
                    'fp-privacy-cookie',
                    false,
                    dirname(plugin_basename(FP_PRIVACY_FILE)) . '/languages'
                );

                // Registra servizi frontend
                if (!is_admin()) {
                    $settings = get_option('fp_privacy_settings', []);
                    
                    if (!empty($settings['enabled'])) {
                        $container->get(CookieBanner::class)->register();
                        $container->get(ConsentManager::class)->register();
                    }
                }

                // Integrazione con FP Performance Suite
                if (self::isFPPerformanceActive()) {
                    $container->get(PerformanceIntegration::class)->register();
                }
            });

        } catch (\Throwable $e) {
            Logger::error('Plugin initialization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registra i servizi nel container
     */
    private static function register(ServiceContainer $container): void
    {
        // Container stesso
        $container->set(ServiceContainer::class, static fn() => $container);

        // Admin
        $container->set(Menu::class, static fn(ServiceContainer $c) => new Menu($c));
        $container->set(AdminAssets::class, static fn() => new AdminAssets());

        // Services
        $container->set(CookieBanner::class, static fn() => new CookieBanner());
        $container->set(ConsentManager::class, static fn() => new ConsentManager());
        $container->set(PrivacyPolicyManager::class, static fn() => new PrivacyPolicyManager());
        $container->set(CookieScanner::class, static fn() => new CookieScanner());
        $container->set(PerformanceIntegration::class, static fn(ServiceContainer $c) => new PerformanceIntegration($c));
    }

    /**
     * Ottiene il container
     */
    public static function container(): ServiceContainer
    {
        if (!self::$container instanceof ServiceContainer) {
            self::init();
        }

        return self::$container;
    }

    /**
     * Verifica se il plugin è stato inizializzato
     */
    public static function isInitialized(): bool
    {
        return self::$initialized && self::$container instanceof ServiceContainer;
    }

    /**
     * Verifica se FP Performance Suite è attivo
     */
    public static function isFPPerformanceActive(): bool
    {
        return class_exists('FP\\PerfSuite\\Plugin') && 
               defined('FP_PERF_SUITE_VERSION');
    }

    /**
     * Hook di attivazione
     */
    public static function onActivate(): void
    {
        try {
            // Salva versione
            update_option('fp_privacy_version', FP_PRIVACY_VERSION, false);

            // Inizializza opzioni di default
            self::initializeDefaultOptions();

            // Trigger hook
            do_action('fp_privacy_activated', FP_PRIVACY_VERSION);

            Logger::info('Plugin activated successfully');

        } catch (\Throwable $e) {
            update_option('fp_privacy_activation_error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'time' => time(),
            ], false);

            Logger::error('Plugin activation failed: ' . $e->getMessage());
        }
    }

    /**
     * Hook di disattivazione
     */
    public static function onDeactivate(): void
    {
        Logger::info('Plugin deactivated');
        do_action('fp_privacy_deactivated');
    }

    /**
     * Inizializza le opzioni di default
     */
    private static function initializeDefaultOptions(): void
    {
        // Impostazioni generali
        if (!get_option('fp_privacy_settings')) {
            update_option('fp_privacy_settings', [
                'enabled' => false,
                'banner_position' => 'bottom',
                'banner_style' => 'classic',
                'primary_color' => '#0073aa',
                'accept_all_text' => 'Accetta Tutti',
                'reject_all_text' => 'Rifiuta Tutti',
                'settings_text' => 'Personalizza',
                'privacy_policy_page' => 0,
                'cookie_policy_page' => 0,
            ], false);
        }

        // Categorie di cookie
        if (!get_option('fp_privacy_cookie_categories')) {
            update_option('fp_privacy_cookie_categories', [
                'necessary' => [
                    'name' => 'Cookie Necessari',
                    'description' => 'Cookie tecnici necessari per il funzionamento del sito',
                    'required' => true,
                    'enabled' => true,
                ],
                'analytics' => [
                    'name' => 'Cookie Analitici',
                    'description' => 'Cookie per analizzare l\'utilizzo del sito',
                    'required' => false,
                    'enabled' => false,
                ],
                'marketing' => [
                    'name' => 'Cookie Marketing',
                    'description' => 'Cookie per pubblicità personalizzata',
                    'required' => false,
                    'enabled' => false,
                ],
            ], false);
        }

        // Integrazione FP Performance
        if (!get_option('fp_privacy_performance_integration')) {
            update_option('fp_privacy_performance_integration', [
                'enabled' => true,
                'exclude_banner_from_cache' => true,
                'exclude_banner_from_minification' => true,
                'lazy_load_scripts' => true,
            ], false);
        }
    }
}


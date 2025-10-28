<?php

/**
 * Performance Integration Service
 * Integrazione con FP Performance Suite
 *
 * @package FP\Privacy\Services
 */

namespace FP\Privacy\Services;

use FP\Privacy\ServiceContainer;

class PerformanceIntegration
{
    private ServiceContainer $container;

    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }

    /**
     * Register the service
     */
    public function register(): void
    {
        // Verifica che FP Performance sia attivo
        if (!class_exists('FP\\PerfSuite\\Plugin')) {
            return;
        }

        $settings = get_option('fp_privacy_performance_integration', []);

        if (empty($settings['enabled'])) {
            return;
        }

        // Escludi banner dalla cache
        if (!empty($settings['exclude_banner_from_cache'])) {
            $this->excludeBannerFromCache();
        }

        // Escludi dalla minificazione
        if (!empty($settings['exclude_banner_from_minification'])) {
            $this->excludeBannerFromMinification();
        }

        // Lazy load scripts
        if (!empty($settings['lazy_load_scripts'])) {
            $this->setupLazyLoadScripts();
        }

        // Integrazione con Asset Optimizer
        $this->integrateWithAssetOptimizer();
    }

    /**
     * Escludi il banner cookie dalla page cache
     */
    private function excludeBannerFromCache(): void
    {
        // Aggiungi exclusion per Page Cache
        add_filter('fp_ps_page_cache_exclude_patterns', function($patterns) {
            $patterns[] = '#fp-privacy-banner';
            $patterns[] = 'fp_privacy_consent';
            return $patterns;
        });

        // Escludi dalla cache se non c'è il cookie di consenso
        add_filter('fp_ps_should_cache_page', function($should_cache) {
            // Non cachare la pagina se l'utente non ha ancora dato il consenso
            if (!isset($_COOKIE['fp_privacy_consent'])) {
                return false;
            }
            return $should_cache;
        });
    }

    /**
     * Escludi il banner dalla minificazione HTML/CSS/JS
     */
    private function excludeBannerFromMinification(): void
    {
        // Escludi HTML del banner dalla minificazione
        add_filter('fp_ps_html_minify_exclude_selectors', function($selectors) {
            $selectors[] = '#fp-privacy-banner';
            $selectors[] = '.fp-privacy-modal';
            return $selectors;
        });

        // Escludi CSS del banner dalla minificazione
        add_filter('fp_ps_css_minify_exclude_handles', function($handles) {
            $handles[] = 'fp-privacy-banner';
            return $handles;
        });

        // Escludi JS del banner dalla minificazione
        add_filter('fp_ps_js_minify_exclude_handles', function($handles) {
            $handles[] = 'fp-privacy-banner';
            return $handles;
        });
    }

    /**
     * Setup lazy loading per gli script che richiedono consenso
     */
    private function setupLazyLoadScripts(): void
    {
        add_action('wp_footer', function() {
            ?>
            <script>
            (function() {
                // Attendi il consenso prima di caricare script di terze parti
                document.addEventListener('fpPrivacyConsentGiven', function(e) {
                    var consent = e.detail;

                    // Google Analytics
                    if (consent.analytics && typeof gtag === 'undefined') {
                        fpPrivacyLoadScript('https://www.googletagmanager.com/gtag/js?id=UA-XXXXXXX-X');
                    }

                    // Facebook Pixel
                    if (consent.marketing && typeof fbq === 'undefined') {
                        fpPrivacyLoadScript('https://connect.facebook.net/en_US/fbevents.js');
                    }
                });

                function fpPrivacyLoadScript(src) {
                    var script = document.createElement('script');
                    script.src = src;
                    script.async = true;
                    document.head.appendChild(script);
                }
            })();
            </script>
            <?php
        }, 999);
    }

    /**
     * Integrazione con Asset Optimizer di FP Performance
     */
    private function integrateWithAssetOptimizer(): void
    {
        // Marca gli script cookie come critici (non differire)
        add_filter('fp_ps_defer_js_exclude_handles', function($handles) {
            $handles[] = 'fp-privacy-banner';
            return $handles;
        });

        // Preload CSS del banner per performance
        add_filter('fp_ps_resource_hints_preload', function($preloads) {
            $preloads[] = [
                'href' => FP_PRIVACY_URL . 'assets/css/banner.css',
                'as' => 'style',
            ];
            return $preloads;
        });

        // DNS Prefetch per domini di terze parti (solo se consenso dato)
        add_filter('fp_ps_resource_hints_dns_prefetch', function($domains) {
            if (isset($_COOKIE['fp_privacy_consent'])) {
                $consent = json_decode($_COOKIE['fp_privacy_consent'], true);
                
                if (!empty($consent['consent']['analytics'])) {
                    $domains[] = 'https://www.google-analytics.com';
                    $domains[] = 'https://www.googletagmanager.com';
                }

                if (!empty($consent['consent']['marketing'])) {
                    $domains[] = 'https://connect.facebook.net';
                }
            }
            return $domains;
        });
    }

    /**
     * Ottimizza il caricamento del banner con Critical CSS
     */
    public function getCriticalCss(): string
    {
        return <<<CSS
.fp-privacy-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    z-index: 9999;
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.fp-privacy-banner__content {
    flex: 1;
    display: flex;
    gap: 20px;
    align-items: center;
}
.fp-privacy-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}
.fp-privacy-btn--primary {
    background: var(--fp-privacy-primary-color, #0073aa);
    color: #fff;
}
CSS;
    }

    /**
     * Registra script di terze parti per gestione consensi
     */
    public function registerThirdPartyScript(string $name, array $config): void
    {
        $scripts = get_option('fp_privacy_third_party_scripts', []);
        $scripts[$name] = $config;
        update_option('fp_privacy_third_party_scripts', $scripts, false);
    }

    /**
     * Verifica se uno script di terze parti può essere caricato
     */
    public static function canLoadScript(string $category): bool
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

    /**
     * Hook per bloccare script di terze parti se non c'è consenso
     */
    public function blockScriptIfNoConsent(string $tag, string $handle, string $src): string
    {
        $categories = get_option('fp_privacy_cookie_categories', []);
        $scripts = get_option('fp_privacy_third_party_scripts', []);

        // Verifica se lo script richiede consenso
        foreach ($scripts as $scriptName => $config) {
            if (strpos($src, $config['domain']) !== false) {
                $category = $config['category'] ?? 'necessary';

                // Se la categoria richiede consenso e non ce l'abbiamo, blocca
                if (!empty($categories[$category]['required']) === false) {
                    if (!self::canLoadScript($category)) {
                        // Cambia il tipo a "text/plain" per bloccare l'esecuzione
                        $tag = str_replace('<script', '<script type="text/plain" data-fp-privacy-category="' . esc_attr($category) . '"', $tag);
                    }
                }
                break;
            }
        }

        return $tag;
    }
}


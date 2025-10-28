<?php
/**
 * Plugin Name: FP Privacy and Cookie Policy
 * Plugin URI: https://francescopasseri.com
 * Description: GDPR-compliant cookie consent manager with integration for FP Performance Suite
 * Version: 1.0.0
 * Author: Francesco Passeri
 * Author URI: https://francescopasseri.com
 * Text Domain: fp-privacy-cookie
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

defined('ABSPATH') || exit;

// Costanti del plugin
defined('FP_PRIVACY_VERSION') || define('FP_PRIVACY_VERSION', '1.0.0');
defined('FP_PRIVACY_DIR') || define('FP_PRIVACY_DIR', __DIR__);
defined('FP_PRIVACY_FILE') || define('FP_PRIVACY_FILE', __FILE__);
defined('FP_PRIVACY_URL') || define('FP_PRIVACY_URL', plugin_dir_url(__FILE__));

/**
 * Log sicuro senza dipendenze dal database
 */
function fp_privacy_safe_log(string $message, string $level = 'INFO'): void {
    $timestamp = gmdate('Y-m-d H:i:s');
    $logMessage = sprintf(
        '[%s] [FP-Privacy] [%s] %s',
        $timestamp,
        $level,
        $message
    );
    
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log($logMessage);
    }
}

// Autoload Composer con gestione errori
$autoload = __DIR__ . '/vendor/autoload.php';
if (is_readable($autoload)) {
    try {
        require_once $autoload;
    } catch (\Throwable $e) {
        fp_privacy_safe_log('Errore caricamento autoloader Composer: ' . $e->getMessage(), 'ERROR');
    }
}

// Autoloader PSR-4 per FP\Privacy\
spl_autoload_register(static function ($class) {
    if (strpos($class, 'FP\\Privacy\\') !== 0) {
        return;
    }
    
    try {
        $relativePath = substr($class, strlen('FP\\Privacy\\'));
        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $relativePath . '.php';
        
        if (is_readable($path)) {
            require_once $path;
        }
    } catch (\Throwable $e) {
        fp_privacy_safe_log('Errore autoloader per classe ' . $class . ': ' . $e->getMessage(), 'ERROR');
    }
});

// Activation/Deactivation hooks
if (!function_exists('fp_privacy_activation_handler')) {
    function fp_privacy_activation_handler() {
        try {
            if (!class_exists('FP\\Privacy\\Plugin')) {
                $pluginFile = __DIR__ . '/src/Plugin.php';
                if (!file_exists($pluginFile)) {
                    wp_die('Errore critico: File Plugin.php non trovato in ' . esc_html($pluginFile));
                }
                require_once $pluginFile;
            }
            
            FP\Privacy\Plugin::onActivate();
        } catch (\Throwable $e) {
            fp_privacy_safe_log('Errore attivazione: ' . $e->getMessage(), 'ERROR');
            wp_die(sprintf(
                '<h1>Errore di Attivazione Plugin</h1><p><strong>Messaggio:</strong> %s</p><p><strong>File:</strong> %s:%d</p>',
                esc_html($e->getMessage()),
                esc_html($e->getFile()),
                $e->getLine()
            ));
        }
    }
    
    function fp_privacy_deactivation_handler() {
        try {
            if (!class_exists('FP\\Privacy\\Plugin')) {
                $pluginFile = __DIR__ . '/src/Plugin.php';
                if (file_exists($pluginFile)) {
                    require_once $pluginFile;
                }
            }
            
            if (class_exists('FP\\Privacy\\Plugin')) {
                FP\Privacy\Plugin::onDeactivate();
            }
        } catch (\Throwable $e) {
            fp_privacy_safe_log('Errore disattivazione: ' . $e->getMessage(), 'ERROR');
        }
    }
    
    register_activation_hook(__FILE__, 'fp_privacy_activation_handler');
    register_deactivation_hook(__FILE__, 'fp_privacy_deactivation_handler');
}

// Prevenzione caricamento multiplo
if (defined('FP_PRIVACY_LOADED')) {
    return;
}
define('FP_PRIVACY_LOADED', true);

// Inizializzazione del plugin
if (function_exists('add_action')) {
    global $fp_privacy_initialized;
    if (!isset($fp_privacy_initialized)) {
        $fp_privacy_initialized = false;
    }
    
    add_action('plugins_loaded', static function () {
        global $fp_privacy_initialized;
        
        if ($fp_privacy_initialized) {
            return;
        }
        
        $fp_privacy_initialized = true;
        
        try {
            fp_privacy_initialize_plugin();
        } catch (\Throwable $e) {
            fp_privacy_safe_log('Errore inizializzazione plugin: ' . $e->getMessage(), 'ERROR');
            
            if (is_admin() && current_user_can('manage_options')) {
                add_action('admin_notices', static function () use ($e) {
                    printf(
                        '<div class="notice notice-error"><p><strong>FP Privacy &amp; Cookie Policy:</strong> Errore di inizializzazione: %s</p></div>',
                        esc_html($e->getMessage())
                    );
                });
            }
        }
    }, 20); // Carica dopo FP Performance (priority 10)
}

/**
 * Funzione di inizializzazione del plugin
 */
function fp_privacy_initialize_plugin(): void {
    $pluginFile = __DIR__ . '/src/Plugin.php';
    
    if (!file_exists($pluginFile)) {
        fp_privacy_safe_log('ERRORE CRITICO: File Plugin.php non trovato in ' . $pluginFile, 'ERROR');
        return;
    }
    
    try {
        if (!class_exists('FP\\Privacy\\Plugin')) {
            require_once $pluginFile;
        }
        
        if (!class_exists('FP\\Privacy\\Plugin')) {
            throw new \RuntimeException('Classe Plugin non trovata dopo require_once');
        }
        
        \FP\Privacy\Plugin::init();
        fp_privacy_safe_log('Plugin initialized successfully', 'INFO');
        
    } catch (\Throwable $e) {
        fp_privacy_safe_log('Plugin initialization error: ' . $e->getMessage(), 'ERROR');
        
        if (is_admin() && current_user_can('manage_options')) {
            add_action('admin_notices', static function () use ($e) {
                printf(
                    '<div class="notice notice-error"><p><strong>FP Privacy &amp; Cookie Policy:</strong> %s<br><small>File: %s:%d</small></p></div>',
                    esc_html($e->getMessage()),
                    esc_html($e->getFile()),
                    $e->getLine()
                );
            });
        }
    }
}


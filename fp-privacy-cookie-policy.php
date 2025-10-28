<?php
/**
 * Plugin Name: FP Privacy and Cookie Policy
 * Description: Provides a GDPR-ready consent banner, consent logging, and automated privacy/cookie policies with Google Consent Mode v2 for WordPress. Includes REST, WP-CLI, and Gutenberg tooling for privacy workflows.
 * Version: 0.1.2
 * Author: Francesco Passeri
 * Author URI: https://francescopasseri.com
 * Text Domain: fp-privacy
 * Domain Path: /languages
 * GitHub Plugin URI: franpass87/FP-Privacy-and-Cookie-Policy
 * Primary Branch: main
 * Release Asset: true
 * Requires PHP: 7.4
 * Requires at least: 5.8
 *
 * @package FP\Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

define( 'FP_PRIVACY_PLUGIN_FILE', __FILE__ );

define( 'FP_PRIVACY_PLUGIN_VERSION', '0.1.2' );

// Alias per integrazione con FP Performance Suite
define( 'FP_PRIVACY_VERSION', '0.1.2' );

define( 'FP_PRIVACY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

define( 'FP_PRIVACY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

$autoload = __DIR__ . '/vendor/autoload.php';

if ( is_readable( $autoload ) ) {
    require $autoload;
}


if ( ! function_exists( 'fp_privacy_get_ip_salt' ) ) {
    function fp_privacy_get_ip_salt() {
        static $salt = null;

        if ( null !== $salt ) {
            return $salt;
        }

        $option_key = 'fp_privacy_ip_salt';

        if ( function_exists( 'get_option' ) ) {
            $stored = get_option( $option_key );

            if ( is_string( $stored ) && '' !== $stored ) {
                $salt = $stored;

                return $salt;
            }
        }

        if ( function_exists( 'wp_generate_password' ) ) {
            $salt = wp_generate_password( 64, false, false );
        } elseif ( function_exists( 'wp_salt' ) ) {
            $salt = wp_salt( 'fp-privacy-ip' );
        } else {
            try {
                $salt = bin2hex( random_bytes( 32 ) );
            } catch ( \Exception $e ) {
                $salt = md5( uniqid( 'fp-privacy', true ) );
            }
        }

        if ( function_exists( 'update_option' ) ) {
            update_option( $option_key, $salt, false );
        }

        return $salt;
    }
}

spl_autoload_register(
    static function ( $class ) {
        $prefix      = 'FP\\Privacy\\';
        $prefix_len  = strlen( $prefix );

        if ( 0 !== strncmp( $class, $prefix, $prefix_len ) ) {
            return;
        }

        $relative = substr( $class, $prefix_len );
        $relative = str_replace( '\\', DIRECTORY_SEPARATOR, $relative );

        $source_root = FP_PRIVACY_PLUGIN_PATH . 'src' . DIRECTORY_SEPARATOR;
        $path        = $source_root . $relative . '.php';

        if ( ! is_readable( $path ) ) {
            return;
        }

        static $resolved_root = null;

        if ( null === $resolved_root ) {
            $resolved_root = realpath( $source_root );

            if ( false === $resolved_root ) {
                // Fall back to the non-resolved path so autoloading can still operate.
                $resolved_root = rtrim( $source_root, DIRECTORY_SEPARATOR );
            }

            $resolved_root = rtrim( $resolved_root, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
        }

        $resolved_path = realpath( $path );

        if ( false === $resolved_path ) {
            return;
        }

        if ( strncmp( $resolved_path, $resolved_root, strlen( $resolved_root ) ) !== 0 ) {
            return;
        }

        require_once $resolved_path;
    }
);

if ( ! class_exists( '\\FP\\Privacy\\Plugin' ) ) {
return;
}

register_activation_hook( __FILE__, array( '\\FP\\Privacy\\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\\FP\\Privacy\\Plugin', 'deactivate' ) );

add_action(
    'plugins_loaded',
    static function () {
        \FP\Privacy\Plugin::instance()->boot();
    }
);

add_action(
    'wpmu_new_blog',
    static function ( $blog_id ) {
        if ( ! is_multisite() ) {
            return;
        }

        $plugin = \FP\Privacy\Plugin::instance();
        $plugin->provision_new_site( (int) $blog_id );
    }
);

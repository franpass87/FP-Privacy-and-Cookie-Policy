<?php
/**
 * Plugin Name: FP Privacy and Cookie Policy
 * Description: Gestione privacy/cookie policy, banner e consenso (GDPR) + Google Consent Mode v2.
 * Version: 0.1.1
 * Author: Francesco Passeri
 * Author URI: https://francescopasseri.com
 * Text Domain: fp-privacy
 * Domain Path: /languages
 *
 * @package FP\Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

define( 'FP_PRIVACY_PLUGIN_FILE', __FILE__ );

define( 'FP_PRIVACY_PLUGIN_VERSION', '0.1.1' );

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
function ( $class ) {
if ( 0 !== strpos( $class, 'FP\\\\Privacy\\\\' ) ) {
return;
}

$relative = str_replace( 'FP\\\\Privacy\\\\', '', $class );
$relative = str_replace( '\\\\', DIRECTORY_SEPARATOR, $relative );
$path     = FP_PRIVACY_PLUGIN_PATH . 'src/' . $relative . '.php';

if ( file_exists( $path ) ) {
require_once $path;
}
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

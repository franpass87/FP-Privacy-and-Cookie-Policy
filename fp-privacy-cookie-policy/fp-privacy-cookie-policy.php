<?php
/**
 * Plugin Name: FP Privacy and Cookie Policy
 * Description: Gestione privacy/cookie policy, banner e consenso (GDPR) + Google Consent Mode v2.
 * Version: 0.1.0
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

define( 'FP_PRIVACY_PLUGIN_VERSION', '0.1.0' );

define( 'FP_PRIVACY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

define( 'FP_PRIVACY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


define( 'FP_PRIVACY_IP_SALT', 'fp-privacy-cookie-policy-salt' );
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

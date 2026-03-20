<?php
/**
 * Costanti WordPress/plugin per analisi PHPStan fuori da WP (path assoluti fittizi).
 *
 * @package FP\Privacy\Tools
 */

declare(strict_types=1);

$plugin_root = dirname( __DIR__ ) . '/';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $plugin_root );
}

if ( ! defined( 'FP_PRIVACY_PLUGIN_FILE' ) ) {
	define( 'FP_PRIVACY_PLUGIN_FILE', $plugin_root . 'fp-privacy-cookie-policy.php' );
}

if ( ! defined( 'FP_PRIVACY_PLUGIN_PATH' ) ) {
	define( 'FP_PRIVACY_PLUGIN_PATH', $plugin_root );
}

if ( ! defined( 'FP_PRIVACY_PLUGIN_URL' ) ) {
	define( 'FP_PRIVACY_PLUGIN_URL', 'https://example.test/wp-content/plugins/fp-privacy/' );
}

if ( ! defined( 'FP_PRIVACY_PLUGIN_VERSION' ) ) {
	define( 'FP_PRIVACY_PLUGIN_VERSION', '0.0.0' );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

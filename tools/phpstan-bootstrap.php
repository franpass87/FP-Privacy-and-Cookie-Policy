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

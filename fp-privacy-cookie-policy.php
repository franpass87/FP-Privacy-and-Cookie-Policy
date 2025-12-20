<?php
/**
 * Plugin Name: FP Privacy and Cookie Policy
 * Description: Provides a GDPR-ready consent banner, consent logging, and automated privacy/cookie policies with Google Consent Mode v2 for WordPress. Includes REST, WP-CLI, and Gutenberg tooling for privacy workflows.
 * Version: 0.2.0
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

define( 'FP_PRIVACY_PLUGIN_VERSION', '0.2.0' );

// Alias per integrazione con FP Performance Suite
define( 'FP_PRIVACY_VERSION', '0.2.0' );

define( 'FP_PRIVACY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

define( 'FP_PRIVACY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Autoloader.
$autoload = __DIR__ . '/vendor/autoload.php';

if ( is_readable( $autoload ) ) {
	require $autoload;
}

// Global function for backward compatibility - DEPRECATED.
// Use IpSaltService via container instead.
// This will be removed in a future version.
if ( ! function_exists( 'fp_privacy_get_ip_salt' ) ) {
	/**
	 * Get IP salt (deprecated - use IpSaltService instead).
	 *
	 * @deprecated Use IpSaltService via service container instead.
	 * @return string Salt value.
	 */
	function fp_privacy_get_ip_salt() {
		// Try to use service if available.
		if ( class_exists( '\\FP\\Privacy\\Core\\Kernel' ) ) {
			try {
				$kernel = \FP\Privacy\Core\Kernel::make();
				$container = $kernel->getContainer();
				if ( $container->has( \FP\Privacy\Services\Security\IpSaltService::class ) ) {
					$service = $container->get( \FP\Privacy\Services\Security\IpSaltService::class );
					return $service->getSalt();
				}
			} catch ( \Exception $e ) {
				// Fall through to legacy implementation.
			}
		}

		// Legacy implementation (backward compatibility).
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

// Bootstrap plugin.
if ( class_exists( '\\FP\\Privacy\\Core\\Bootstrap' ) ) {
	\FP\Privacy\Core\Bootstrap::init();
} elseif ( class_exists( '\\FP\\Privacy\\Core\\Kernel' ) ) {
	// Fallback to direct Kernel usage if Bootstrap not available.
	add_action(
		'plugins_loaded',
		static function () {
			$kernel = \FP\Privacy\Core\Kernel::make();
			$kernel->boot();
		},
		5
	);

	register_activation_hook(
		__FILE__,
		static function ( $network_wide ) {
			$kernel = \FP\Privacy\Core\Kernel::make();
			$kernel->activate( $network_wide );
		}
	);

	register_deactivation_hook(
		__FILE__,
		static function () {
			$kernel = \FP\Privacy\Core\Kernel::make();
			$kernel->deactivate();
		}
	);

	if ( is_multisite() ) {
		add_action(
			'wpmu_new_blog',
			static function ( $blog_id ) {
				$kernel = \FP\Privacy\Core\Kernel::make();
				$kernel->provisionSite( (int) $blog_id );
			}
		);
	}
} elseif ( class_exists( '\\FP\\Privacy\\Plugin' ) ) {
	// Final fallback to old Plugin class (for backward compatibility during migration).
	register_activation_hook( __FILE__, array( '\\FP\\Privacy\\Plugin', 'activate' ) );
	register_deactivation_hook( __FILE__, array( '\\FP\\Privacy\\Plugin', 'deactivate' ) );

	add_action(
		'plugins_loaded',
		static function () {
			\FP\Privacy\Plugin::instance()->boot();
		}
	);

	if ( is_multisite() ) {
		add_action(
			'wpmu_new_blog',
			static function ( $blog_id ) {
				$plugin = \FP\Privacy\Plugin::instance();
				$plugin->provision_new_site( (int) $blog_id );
			}
		);
	}
}

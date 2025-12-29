<?php
/**
 * Plugin bootstrap - minimal entry point logic.
 *
 * @package FP\Privacy\Core
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Core;

/**
 * Bootstrap class for plugin initialization.
 */
class Bootstrap {
	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Compatibility checks.
		if ( ! self::checkRequirements() ) {
			return;
		}

		// Register lifecycle hooks.
		register_activation_hook( FP_PRIVACY_PLUGIN_FILE, array( self::class, 'activate' ) );
		register_deactivation_hook( FP_PRIVACY_PLUGIN_FILE, array( self::class, 'deactivate' ) );

		// Boot kernel.
		add_action(
			'plugins_loaded',
			function () {
				try {
					Kernel::make()->boot();
				} catch ( \Throwable $e ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( sprintf( 'FP Privacy: Fatal error during kernel boot: %s', $e->getMessage() ) );
					}
					// Don't let the error crash the site.
				}
			},
			5
		);

		// Multisite support.
		if ( is_multisite() ) {
			add_action(
				'wpmu_new_blog',
				function ( $blog_id ) {
					Kernel::make()->provisionSite( (int) $blog_id );
				}
			);
		}
	}

	/**
	 * Check plugin requirements.
	 *
	 * @return bool True if requirements are met.
	 */
	private static function checkRequirements(): bool {
		// Check PHP version.
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			add_action(
				'admin_notices',
				function () {
					?>
					<div class="notice notice-error">
						<p>
							<strong><?php esc_html_e( 'FP Privacy and Cookie Policy', 'fp-privacy' ); ?>:</strong>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %1$s: PHP version required, %2$s: Current PHP version */
									__( 'This plugin requires PHP %1$s or higher. You are running PHP %2$s. Please upgrade PHP to use this plugin.', 'fp-privacy' ),
									'7.4',
									PHP_VERSION
								)
							);
							?>
						</p>
					</div>
					<?php
				}
			);
			return false;
		}

		// Check WordPress version.
		global $wp_version;
		if ( version_compare( $wp_version, '5.8', '<' ) ) {
			add_action(
				'admin_notices',
				function () {
					?>
					<div class="notice notice-error">
						<p>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: WordPress version */
									__( 'FP Privacy requires WordPress 5.8 or higher. You are running WordPress %s.', 'fp-privacy' ),
									get_bloginfo( 'version' )
								)
							);
							?>
						</p>
					</div>
					<?php
				}
			);
			return false;
		}

		return true;
	}

	/**
	 * Handle plugin activation.
	 *
	 * @param bool $network_wide Whether network-wide activation.
	 * @return void
	 */
	public static function activate( bool $network_wide ): void {
		try {
			Kernel::make()->activate( $network_wide );
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'FP Privacy: Fatal error during activation: %s', $e->getMessage() ) );
			}
			// Don't let the error crash the activation.
		}
	}

	/**
	 * Handle plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		Kernel::make()->deactivate();
	}
}





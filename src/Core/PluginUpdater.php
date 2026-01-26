<?php
/**
 * Plugin updater - handles plugin update hooks.
 *
 * @package FP\Privacy\Core
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Core;

use FP\Privacy\Services\Policy\PolicyAutoUpdater;

/**
 * Handles plugin update hooks.
 */
class PluginUpdater {
	/**
	 * Policy auto-updater.
	 *
	 * @var PolicyAutoUpdater
	 */
	private $auto_updater;

	/**
	 * Constructor.
	 *
	 * @param PolicyAutoUpdater $auto_updater Policy auto-updater.
	 */
	public function __construct( PolicyAutoUpdater $auto_updater ) {
		$this->auto_updater = $auto_updater;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		// Hook into plugin update completion.
		\add_action( 'upgrader_process_complete', array( $this, 'handle_plugin_update' ), 10, 2 );
	}

	/**
	 * Handle plugin update completion.
	 *
	 * @param \WP_Upgrader $upgrader Upgrader instance.
	 * @param array<string, mixed> $hook_extra Extra arguments.
	 * @return void
	 */
	public function handle_plugin_update( $upgrader, $hook_extra ): void {
		// Check if this is a plugin update.
		if ( ! isset( $hook_extra['action'] ) || 'update' !== $hook_extra['action'] ) {
			return;
		}

		if ( ! isset( $hook_extra['type'] ) || 'plugin' !== $hook_extra['type'] ) {
			return;
		}

		// Check if our plugin was updated.
		$plugin_file = plugin_basename( FP_PRIVACY_PLUGIN_FILE );
		
		if ( ! isset( $hook_extra['plugins'] ) || ! is_array( $hook_extra['plugins'] ) ) {
			return;
		}

		$updated = false;
		foreach ( $hook_extra['plugins'] as $updated_plugin ) {
			if ( $updated_plugin === $plugin_file ) {
				$updated = true;
				break;
			}
		}

		if ( ! $updated ) {
			return;
		}

		// Auto-update policies if enabled.
		if ( $this->auto_updater->should_update() ) {
			// Use a scheduled action to avoid blocking the update process.
			\add_action( 'admin_init', function() {
				$this->auto_updater->update_all_policies( true );
			}, 20 );
		}
	}
}

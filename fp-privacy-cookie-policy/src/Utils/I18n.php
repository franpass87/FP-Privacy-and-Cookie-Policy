<?php
/**
 * Internationalization helper.
 *
 * @package FP\Privacy\Utils
 */

namespace FP\Privacy\Utils;

use function add_action;
use function dirname;
use function load_plugin_textdomain;
use function plugin_basename;

/**
 * Handles text domain loading.
 */
class I18n {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ), 5 );
	}

	/**
	 * Load the plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'fp-privacy',
			false,
			dirname( plugin_basename( FP_PRIVACY_PLUGIN_FILE ) ) . '/languages'
		);
	}
}

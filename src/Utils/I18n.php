<?php
/**
 * Internationalization helper.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
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
		// CRITICAL FIX: Use absolute path for junction/symlink compatibility
		// load_plugin_textdomain() with relative path doesn't work with junctions
		$locale = \apply_filters( 'plugin_locale', \determine_locale(), 'fp-privacy' );
		$mofile = FP_PRIVACY_PLUGIN_PATH . 'languages/fp-privacy-' . $locale . '.mo';
		
		// Try absolute path first (works with junctions)
		if ( file_exists( $mofile ) ) {
			\load_textdomain( 'fp-privacy', $mofile );
		}
		
		// Fallback to standard method for compatibility
		if ( ! \is_textdomain_loaded( 'fp-privacy' ) ) {
			\load_plugin_textdomain(
				'fp-privacy',
				false,
				dirname( plugin_basename( FP_PRIVACY_PLUGIN_FILE ) ) . '/languages'
			);
		}
	}
}

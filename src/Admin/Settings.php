<?php
/**
 * Settings page.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Utils\Options;

/**
 * Settings facade - delegates to SettingsController.
 */
class Settings {
	/**
	 * Settings controller.
	 *
	 * @var SettingsController
	 */
	private $controller;

	/**
	 * Constructor.
	 *
	 * @param Options          $options   Options handler.
	 * @param DetectorRegistry $detector  Detector.
	 * @param PolicyGenerator  $generator Generator.
	 */
	public function __construct( Options $options, DetectorRegistry $detector, PolicyGenerator $generator ) {
		$this->controller = new SettingsController( $options, $detector, $generator );
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		\add_action( 'fp_privacy_admin_page_settings', array( $this->controller, 'render_settings_page' ) );
		\add_action( 'fp_privacy_admin_page_tools', array( $this->controller, 'render_tools_page' ) );
		\add_action( 'fp_privacy_admin_page_guide', array( $this->controller, 'render_guide_page' ) );
		\add_action( 'admin_post_fp_privacy_save_settings', array( $this->controller, 'handle_save' ) );
		\add_action( 'admin_post_fp_privacy_bump_revision', array( $this->controller, 'handle_bump_revision' ) );
		\add_action( 'admin_post_fp_privacy_export_settings', array( $this->controller, 'handle_export_settings' ) );
		\add_action( 'admin_post_fp_privacy_import_settings', array( $this->controller, 'handle_import_settings' ) );
		\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Hook.
	 *
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, Menu::MENU_SLUG ) ) {
			return;
		}

		// QUICK WIN #1: WordPress Color Picker
		\wp_enqueue_style( 'wp-color-picker' );
		
		\wp_enqueue_style( 'fp-privacy-admin', FP_PRIVACY_PLUGIN_URL . 'assets/css/admin.css', array( 'wp-color-picker' ), FP_PRIVACY_PLUGIN_VERSION . '-' . time() );
		\wp_enqueue_style( 'fp-privacy-banner-preview', FP_PRIVACY_PLUGIN_URL . 'assets/css/banner.css', array(), FP_PRIVACY_PLUGIN_VERSION );
		
		\wp_enqueue_script( 'fp-privacy-admin', FP_PRIVACY_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'wp-color-picker' ), FP_PRIVACY_PLUGIN_VERSION . '-' . time(), true );
		
		\wp_localize_script(
			'fp-privacy-admin',
			'fpPrivacyL10n',
			array(
				'lowContrast'     => \__( 'The contrast ratio between background and text is below 4.5:1. Please adjust your palette.', 'fp-privacy' ),
				'previewLanguage' => \__( 'Preview language', 'fp-privacy' ),
				'previewEmpty'    => \__( 'Update the banner texts above to preview the banner.', 'fp-privacy' ),
			)
		);
	}
}
<?php
/**
 * Settings page.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Admin\Menu;
use FP\Privacy\Frontend\BannerPaletteBuilder;
use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Utils\Options;

/**
 * Settings facade - delegates to SettingsController.
 */
class Settings {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Settings controller.
	 *
	 * @var SettingsController
	 */
	private $controller;

	/**
	 * Constructor.
	 *
	 * @param Options          $options  Options handler.
	 * @param DetectorRegistry $detector Detector.
	 */
	public function __construct( Options $options, DetectorRegistry $detector ) {
		$this->options    = $options;
		$this->controller = new SettingsController( $options, $detector );
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
		\add_action( 'admin_post_fp_privacy_reset_settings', array( $this->controller, 'handle_reset_settings' ) );
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

		\wp_enqueue_style( 'fp-privacy-admin', FP_PRIVACY_PLUGIN_URL . 'assets/css/admin.css', array(), FP_PRIVACY_PLUGIN_VERSION );
		\wp_enqueue_style( 'fp-privacy-banner-preview', FP_PRIVACY_PLUGIN_URL . 'assets/css/banner.css', array(), FP_PRIVACY_PLUGIN_VERSION );

		$banner_layout = $this->options->get_banner_layout();
		$palette_css   = ( new BannerPaletteBuilder() )->build_palette_css(
			$banner_layout->get_palette(),
			$banner_layout->is_sync_modal_and_button()
		);
		\wp_add_inline_style( 'fp-privacy-banner-preview', $palette_css );

		\wp_enqueue_script( 'fp-privacy-admin', FP_PRIVACY_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), FP_PRIVACY_PLUGIN_VERSION, true );
		
		// Get policy URLs for preview
		$languages = $this->options->get_languages();
		$primary_lang = $languages[0] ?? 'en_US';
		$normalized = $this->options->normalize_language( $primary_lang );
		
		$privacy_page_id = $this->options->get_page_id( 'privacy_policy', $normalized );
		$cookie_page_id  = $this->options->get_page_id( 'cookie_policy', $normalized );
		
		$privacy_url = '';
		$cookie_url = '';
		
		if ( $privacy_page_id && $privacy_page_id > 0 ) {
			$privacy_permalink = \get_permalink( $privacy_page_id );
			if ( \is_string( $privacy_permalink ) && $privacy_permalink !== '' ) {
				$privacy_url = $privacy_permalink;
			}
		}
		
		if ( $cookie_page_id && $cookie_page_id > 0 && $cookie_page_id !== $privacy_page_id ) {
			$cookie_permalink = \get_permalink( $cookie_page_id );
			if ( \is_string( $cookie_permalink ) && $cookie_permalink !== '' ) {
				$cookie_url = $cookie_permalink;
			}
		}
		
		\wp_localize_script(
			'fp-privacy-admin',
			'fpPrivacyL10n',
			array(
				'lowContrast'         => \__( 'The contrast ratio between background and text is below 4.5:1. Please adjust your palette.', 'fp-privacy' ),
				'previewLanguage'     => \__( 'Preview language', 'fp-privacy' ),
				'previewEmpty'        => \__( 'Update the banner texts above to preview the banner.', 'fp-privacy' ),
				'shortcutsHelpTitle'  => \__( 'Keyboard Shortcuts', 'fp-privacy' ),
				'shortcutTabBanner'   => \__( 'Switch to Banner tab', 'fp-privacy' ),
				'shortcutTabCookies'  => \__( 'Switch to Cookies tab', 'fp-privacy' ),
				'shortcutTabPrivacy'  => \__( 'Switch to Privacy tab', 'fp-privacy' ),
				'shortcutTabAdvanced' => \__( 'Switch to Advanced tab', 'fp-privacy' ),
				'shortcutSave'        => \__( 'Save settings', 'fp-privacy' ),
				'shortcutClose'       => \__( 'Close modals/tooltips', 'fp-privacy' ),
				'shortcutHelp'        => \__( 'Show this help', 'fp-privacy' ),
				'close'               => \__( 'Close', 'fp-privacy' ),
				'resetConfirmTitle'   => \__( 'Reset to Default', 'fp-privacy' ),
				'resetConfirmMessage' => \__( 'This will restore all settings to their default values. This action cannot be undone.', 'fp-privacy' ),
				'resetConfirmQuestion' => \__( 'Are you sure you want to continue?', 'fp-privacy' ),
				'resetConfirm'        => \__( 'Yes, reset', 'fp-privacy' ),
				'resetSettingsMissing' => \__( 'Reset is not available (missing security data). Reload the page and try again.', 'fp-privacy' ),
				'cancel'              => \__( 'Cancel', 'fp-privacy' ),
				'skipToContent'       => \__( 'Skip to main content', 'fp-privacy' ),
				'policyUrls'          => array(
					'privacy' => $privacy_url,
					'cookie'  => $cookie_url,
				),
				'adminPostUrl'        => \admin_url( 'admin-post.php' ),
				'resetSettingsNonce'  => \wp_create_nonce( 'fp_privacy_reset_settings' ),
			)
		);
	}
}
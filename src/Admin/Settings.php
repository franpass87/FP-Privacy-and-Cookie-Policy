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
use FP\Privacy\Support\PalettePresetRegistry;
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
		$page    = isset( $_GET['page'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['page'] ) ) : '';
		$is_ours = ( false !== strpos( (string) $hook, Menu::MENU_SLUG ) )
			|| \in_array( $page, Menu::ADMIN_PAGE_SLUGS, true );
		if ( ! $is_ours ) {
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
				'lowContrast'         => \__( 'Il contrasto tra sfondo e testo è sotto 4,5:1. Regola la palette.', 'fp-privacy' ),
				'previewLanguage'     => \__( 'Lingua anteprima', 'fp-privacy' ),
				'previewEmpty'        => \__( 'Aggiorna i testi del banner sopra per vedere l’anteprima.', 'fp-privacy' ),
				'shortcutsHelpTitle'  => \__( 'Scorciatoie da tastiera', 'fp-privacy' ),
				'shortcutTabBanner'   => \__( 'Vai al tab Banner', 'fp-privacy' ),
				'shortcutTabCookies'  => \__( 'Vai al tab Cookie', 'fp-privacy' ),
				'shortcutTabPrivacy'  => \__( 'Vai al tab Privacy', 'fp-privacy' ),
				'shortcutTabAdvanced' => \__( 'Vai al tab Avanzate', 'fp-privacy' ),
				'shortcutSave'        => \__( 'Salva impostazioni', 'fp-privacy' ),
				'shortcutClose'       => \__( 'Chiudi modali e tooltip', 'fp-privacy' ),
				'shortcutHelp'        => \__( 'Mostra questo aiuto', 'fp-privacy' ),
				'close'               => \__( 'Chiudi', 'fp-privacy' ),
				'savingInProgress'    => \__( 'Salvataggio in corso…', 'fp-privacy' ),
				'saveSuccessMessage'  => \__( 'Impostazioni salvate.', 'fp-privacy' ),
				'saveSuccessTitle'    => \__( 'Salvataggio completato', 'fp-privacy' ),
				'leaveWithoutSaving' => \__( 'Ci sono modifiche non salvate. Uscire dalla pagina?', 'fp-privacy' ),
				'bannerPreviewFullscreenTitle' => \__( 'Anteprima banner — schermo intero', 'fp-privacy' ),
				'detectedSearchPlaceholder' => \__( 'Cerca servizio…', 'fp-privacy' ),
				'detectedCategoryAll' => \__( 'Tutte le categorie', 'fp-privacy' ),
				'detectedStatusAll'   => \__( 'Tutti gli stati', 'fp-privacy' ),
				'categoryMarketing'   => \__( 'Marketing', 'fp-privacy' ),
				'categoryAnalytics'   => \__( 'Analytics', 'fp-privacy' ),
				'categoryNecessary'   => \__( 'Necessari', 'fp-privacy' ),
				'categoryPreferences' => \__( 'Preferenze', 'fp-privacy' ),
				'statusDetected'      => \__( 'Rilevato', 'fp-privacy' ),
				'statusNotDetected'   => \__( 'Non rilevato', 'fp-privacy' ),
				'policyUrls'          => array(
					'privacy' => $privacy_url,
					'cookie'  => $cookie_url,
				),
				'palettePresets'      => PalettePresetRegistry::get_js_preset_map(),
			)
		);
	}
}
<?php
/**
 * Admin menu — struttura unificata (operatività → contenuti → sistema → supporto).
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

/**
 * Registers admin menu structure.
 */
class Menu {
	public const MENU_SLUG = 'fp-privacy';

	/**
	 * Tutte le pagine admin del plugin (enqueue, body class, subnav).
	 */
	public const ADMIN_PAGE_SLUGS = array(
		self::MENU_SLUG,
		'fp-privacy-consent-log',
		'fp-privacy-analytics',
		'fp-privacy-policy-editor',
		'fp-privacy-tools',
		'fp-privacy-diagnostics',
		'fp-privacy-guide',
	);

	/**
	 * Diagnostica (azioni rapide, stato consenso).
	 *
	 * @var DiagnosticTools|null
	 */
	private $diagnostic_tools;

	/**
	 * @param DiagnosticTools|null $diagnostic_tools Registrato come sottovoce "Diagnostics".
	 */
	public function __construct( ?DiagnosticTools $diagnostic_tools = null ) {
		$this->diagnostic_tools = $diagnostic_tools;
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		\add_action( 'admin_menu', array( $this, 'register_menu' ) );
		\add_action( 'admin_menu', array( $this, 'maybe_collapse_wp_submenus' ), 999 );
		\add_filter( 'admin_body_class', array( $this, 'filter_admin_body_class' ) );
	}

	/**
	 * Body class per scope CSS design system FP.
	 *
	 * @param string $classes Classi esistenti.
	 *
	 * @return string
	 */
	public function filter_admin_body_class( $classes ) {
		$page = isset( $_GET['page'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['page'] ) ) : '';
		if ( \in_array( $page, self::ADMIN_PAGE_SLUGS, true ) ) {
			$classes .= ' fp-privacy-admin-shell';
		}
		return $classes;
	}

	/**
	 * Register menu and subpages.
	 *
	 * @return void
	 */
	public function register_menu() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		\add_menu_page(
			\__( 'FP Privacy & Cookie', 'fp-privacy' ),
			\__( 'FP Privacy & Cookie', 'fp-privacy' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_settings_page' ),
			'dashicons-shield-alt',
			56.8
		);

		\add_submenu_page(
			self::MENU_SLUG,
			\__( 'Impostazioni', 'fp-privacy' ),
			\__( 'Impostazioni', 'fp-privacy' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_settings_page' )
		);

		\add_submenu_page(
			self::MENU_SLUG,
			\__( 'Registro consensi', 'fp-privacy' ),
			\__( 'Registro consensi', 'fp-privacy' ),
			'manage_options',
			'fp-privacy-consent-log',
			array( $this, 'render_consent_log' )
		);

		\add_submenu_page(
			self::MENU_SLUG,
			\__( 'Analytics', 'fp-privacy' ),
			\__( 'Analytics', 'fp-privacy' ),
			'manage_options',
			'fp-privacy-analytics',
			array( $this, 'render_analytics' )
		);

		\add_submenu_page(
			self::MENU_SLUG,
			\__( 'Editor policy', 'fp-privacy' ),
			\__( 'Editor policy', 'fp-privacy' ),
			'manage_options',
			'fp-privacy-policy-editor',
			array( $this, 'render_policy_editor' )
		);

		\add_submenu_page(
			self::MENU_SLUG,
			\__( 'Strumenti', 'fp-privacy' ),
			\__( 'Strumenti', 'fp-privacy' ),
			'manage_options',
			'fp-privacy-tools',
			array( $this, 'render_tools' )
		);

		if ( null !== $this->diagnostic_tools ) {
			\add_submenu_page(
				self::MENU_SLUG,
				\__( 'Diagnostica', 'fp-privacy' ),
				\__( 'Diagnostica', 'fp-privacy' ),
				'manage_options',
				'fp-privacy-diagnostics',
				array( $this->diagnostic_tools, 'render_page' )
			);
		}

		\add_submenu_page(
			self::MENU_SLUG,
			\__( 'Guida rapida', 'fp-privacy' ),
			\__( 'Guida rapida', 'fp-privacy' ),
			'manage_options',
			'fp-privacy-guide',
			array( $this, 'render_guide' )
		);
	}

	/**
	 * Opzionale: nasconde le sottovoci dal menu laterale e lascia solo la subnav orizzontale in pagina (comportamento legacy).
	 *
	 * Default: **false** — le sezioni sono **sottopagine nel menu WordPress** (come richiesto dal design operativo).
	 * Per il menu “snello” + barra orizzontale: `add_filter( 'fp_privacy_collapse_admin_submenus', '__return_true' );`
	 *
	 * @return void
	 */
	public function maybe_collapse_wp_submenus(): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		/**
		 * Se true, rimuove le sottovoci dal sidebar; la navigazione è solo tramite `AdminSubnav` in contenuto.
		 *
		 * @param bool $collapse Default false.
		 */
		if ( ! \apply_filters( 'fp_privacy_collapse_admin_submenus', false ) ) {
			return;
		}

		foreach ( self::ADMIN_PAGE_SLUGS as $slug ) {
			\remove_submenu_page( self::MENU_SLUG, $slug );
		}
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		\do_action( 'fp_privacy_admin_page_settings' );
	}

	/**
	 * Render policy editor page.
	 *
	 * @return void
	 */
	public function render_policy_editor() {
		\do_action( 'fp_privacy_admin_page_policy_editor' );
	}

	/**
	 * Render consent log page.
	 *
	 * @return void
	 */
	public function render_consent_log() {
		\do_action( 'fp_privacy_admin_page_consent_log' );
	}

	/**
	 * Render tools page.
	 *
	 * @return void
	 */
	public function render_tools() {
		\do_action( 'fp_privacy_admin_page_tools' );
	}

	/**
	 * Render analytics page.
	 *
	 * @return void
	 */
	public function render_analytics() {
		\do_action( 'fp_privacy_admin_page_analytics' );
	}

	/**
	 * Render guide page.
	 *
	 * @return void
	 */
	public function render_guide() {
		\do_action( 'fp_privacy_admin_page_guide' );
	}
}

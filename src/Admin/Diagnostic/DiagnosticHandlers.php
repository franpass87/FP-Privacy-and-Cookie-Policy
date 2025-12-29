<?php
/**
 * Diagnostic action handlers.
 *
 * @package FP\Privacy\Admin\Diagnostic
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin\Diagnostic;

use FP\Privacy\Consent\ConsentState;
use FP\Privacy\Utils\Options;

/**
 * Handles diagnostic tool actions.
 */
class DiagnosticHandlers {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options handler.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Handle setup defaults action.
	 *
	 * @return void
	 */
	public function handle_setup_defaults() {
		\check_admin_referer( 'fp_privacy_setup_defaults' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \__( 'Permessi insufficienti.', 'fp-privacy' ) );
		}

		$all = $this->options->all();

		// Setup categories
		if ( empty( $all['categories'] ) ) {
			$all['categories'] = array(
				'necessary'   => array(
					'label'       => array(
						'it_IT' => 'Necessari',
						'en_US' => 'Necessary',
					),
					'description' => array(
						'it_IT' => 'Cookie necessari per il funzionamento base del sito. Sempre attivi.',
						'en_US' => 'Cookies necessary for the basic functioning of the site. Always active.',
					),
					'locked'      => true,
				),
				'preferences' => array(
					'label'       => array(
						'it_IT' => 'Preferenze',
						'en_US' => 'Preferences',
					),
					'description' => array(
						'it_IT' => 'Cookie che memorizzano le tue preferenze sul sito.',
						'en_US' => 'Cookies that store your preferences on the site.',
					),
					'locked'      => false,
				),
				'statistics'  => array(
					'label'       => array(
						'it_IT' => 'Statistiche',
						'en_US' => 'Statistics',
					),
					'description' => array(
						'it_IT' => 'Cookie che aiutano a capire come i visitatori interagiscono con il sito.',
						'en_US' => 'Cookies that help understand how visitors interact with the site.',
					),
					'locked'      => false,
				),
				'marketing'   => array(
					'label'       => array(
						'it_IT' => 'Marketing',
						'en_US' => 'Marketing',
					),
					'description' => array(
						'it_IT' => 'Cookie utilizzati per tracciare i visitatori e mostrare annunci personalizzati.',
						'en_US' => 'Cookies used to track visitors and display personalized ads.',
					),
					'locked'      => false,
				),
			);
		}

		// Setup banner texts
		if ( empty( $all['banner_texts'] ) ) {
			$all['banner_texts'] = array(
				'it_IT' => array(
					'title'            => 'Rispettiamo la tua privacy',
					'message'          => 'Utilizziamo i cookie per migliorare la tua esperienza. Puoi accettare tutti i cookie o gestire le tue preferenze.',
					'btn_accept'       => 'Accetta tutti',
					'btn_reject'       => 'Rifiuta tutti',
					'btn_prefs'        => 'Gestisci preferenze',
					'modal_title'      => 'Preferenze cookie',
					'modal_close'      => 'Chiudi preferenze',
					'modal_save'       => 'Salva preferenze',
					'revision_notice'  => 'Abbiamo aggiornato la nostra policy. Rivedi le tue preferenze.',
					'toggle_locked'    => 'Obbligatorio',
					'toggle_enabled'   => 'Abilitato',
					'link_privacy_policy' => 'Informativa sulla Privacy',
					'link_cookie_policy'  => 'Cookie Policy',
				),
				'en_US' => array(
					'title'            => 'We respect your privacy',
					'message'          => 'We use cookies to improve your experience. You can accept all cookies or manage your preferences.',
					'btn_accept'       => 'Accept all',
					'btn_reject'       => 'Reject all',
					'btn_prefs'        => 'Manage preferences',
					'modal_title'      => 'Cookie preferences',
					'modal_close'      => 'Close preferences',
					'modal_save'       => 'Save preferences',
					'revision_notice'  => 'We have updated our policy. Please review your preferences.',
					'toggle_locked'    => 'Required',
					'toggle_enabled'   => 'Enabled',
					'link_privacy_policy' => 'Privacy Policy',
					'link_cookie_policy'  => 'Cookie Policy',
				),
			);
		}

		// Setup banner layout
		if ( empty( $all['banner_layout'] ) ) {
			$all['banner_layout'] = array(
				'type'                   => 'floating',
				'position'               => 'bottom',
				'sync_modal_and_button'  => false,
				'enable_dark_mode'       => false,
				'palette'                => array(
					'surface_bg'          => '#F9FAFB',
					'surface_text'        => '#1F2937',
					'button_primary_bg'   => '#2563EB',
					'button_primary_tx'   => '#FFFFFF',
					'button_secondary_bg' => '#FFFFFF',
					'button_secondary_tx' => '#1F2937',
					'link'                => '#1D4ED8',
					'border'              => '#D1D5DB',
					'focus'               => '#2563EB',
				),
			);
		}

		$this->options->set( $all );

		\wp_safe_redirect( \add_query_arg( 'fp_privacy_success', 'setup_defaults', \admin_url( 'admin.php?page=fp-privacy-diagnostics' ) ) );
		exit;
	}

	/**
	 * Handle force banner action.
	 *
	 * @return void
	 */
	public function handle_force_banner() {
		\check_admin_referer( 'fp_privacy_force_banner' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \__( 'Permessi insufficienti.', 'fp-privacy' ) );
		}

		$all                   = $this->options->all();
		$all['preview_mode']   = true;
		$this->options->set( $all );

		$cookie_name = ConsentState::COOKIE_NAME;
		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			\setcookie( $cookie_name, '', time() - 3600, '/', '', false, true );
			unset( $_COOKIE[ $cookie_name ] );
		}

		\wp_safe_redirect( \add_query_arg( 'fp_privacy_success', 'force_banner', \admin_url( 'admin.php?page=fp-privacy-diagnostics' ) ) );
		exit;
	}

	/**
	 * Handle disable preview action.
	 *
	 * @return void
	 */
	public function handle_disable_preview() {
		\check_admin_referer( 'fp_privacy_disable_preview' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \__( 'Permessi insufficienti.', 'fp-privacy' ) );
		}

		$all                   = $this->options->all();
		$all['preview_mode']   = false;
		$this->options->set( $all );

		\wp_safe_redirect( \add_query_arg( 'fp_privacy_success', 'disable_preview', \admin_url( 'admin.php?page=fp-privacy-diagnostics' ) ) );
		exit;
	}

	/**
	 * Handle clear consent action.
	 *
	 * @return void
	 */
	public function handle_clear_consent() {
		\check_admin_referer( 'fp_privacy_clear_consent' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \__( 'Permessi insufficienti.', 'fp-privacy' ) );
		}

		$cookie_name = ConsentState::COOKIE_NAME;
		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			\setcookie( $cookie_name, '', time() - 3600, '/', '', false, true );
			unset( $_COOKIE[ $cookie_name ] );
		}

		\wp_safe_redirect( \add_query_arg( 'fp_privacy_success', 'clear_consent', \admin_url( 'admin.php?page=fp-privacy-diagnostics' ) ) );
		exit;
	}
}

















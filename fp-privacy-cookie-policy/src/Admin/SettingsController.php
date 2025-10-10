<?php
/**
 * Settings controller.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Utils\Options;

/**
 * Handles settings business logic and request processing.
 */
class SettingsController {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Detector registry.
	 *
	 * @var DetectorRegistry
	 */
	private $detector;

	/**
	 * Policy generator.
	 *
	 * @var PolicyGenerator
	 */
	private $generator;

	/**
	 * Settings renderer.
	 *
	 * @var SettingsRenderer
	 */
	private $renderer;

	/**
	 * Constructor.
	 *
	 * @param Options          $options   Options handler.
	 * @param DetectorRegistry $detector  Detector.
	 * @param PolicyGenerator  $generator Generator.
	 */
	public function __construct( Options $options, DetectorRegistry $detector, PolicyGenerator $generator ) {
		$this->options   = $options;
		$this->detector  = $detector;
		$this->generator = $generator;
		$this->renderer  = new SettingsRenderer( $options );
	}

	/**
	 * Prepare data for settings page.
	 *
	 * @return array<string, mixed>
	 */
	public function prepare_settings_data() {
		$options      = $this->options->all();
		$languages    = $this->options->get_languages();
		$primary_lang = $languages[0] ?? $this->options->normalize_language( \function_exists( '\get_locale' ) ? \get_locale() : 'en_US' );

		$default_options   = $this->options->get_default_options();
		$default_locale    = $this->options->normalize_language( $default_options['languages_active'][0] ?? $primary_lang );
		$default_texts_raw = isset( $default_options['banner_texts'][ $default_locale ] ) && \is_array( $default_options['banner_texts'][ $default_locale ] )
			? $default_options['banner_texts'][ $default_locale ]
			: array();

		$script_rules      = array();
		$script_categories = array();

		foreach ( $languages as $script_lang ) {
			$normalized                       = $this->options->normalize_language( $script_lang );
			$script_rules[ $normalized ]      = $this->options->get_script_rules_for_language( $normalized );
			$script_categories[ $normalized ] = $this->options->get_categories_for_language( $normalized );
		}

		$notifications           = $this->options->get_detector_notifications();
		$notification_recipients = isset( $notifications['recipients'] ) && \is_array( $notifications['recipients'] )
			? implode( ', ', $notifications['recipients'] )
			: '';

		return array(
			'options'                 => $options,
			'languages'               => $languages,
			'primary_lang'            => $primary_lang,
			'detected'                => $this->detector->detect_services(),
			'snapshot_notice'         => $this->get_snapshot_notice( $options['snapshots'] ),
			'default_texts_raw'       => $default_texts_raw,
			'script_rules'            => $script_rules,
			'script_categories'       => $script_categories,
			'notifications'           => $notifications,
			'notification_recipients' => $notification_recipients,
		);
	}

	/**
	 * Determine whether stored snapshots are stale.
	 *
	 * @param array<string, mixed> $snapshots Snapshots payload.
	 *
	 * @return array{timestamp:int}|null
	 */
	public function get_snapshot_notice( $snapshots ) {
		if ( ! \is_array( $snapshots ) ) {
			return array( 'timestamp' => 0 );
		}

		$now        = time();
		$threshold  = DAY_IN_SECONDS * 14;
		$stale      = false;
		$oldest     = PHP_INT_MAX;
		$has_policy = false;

		$services_generated = isset( $snapshots['services']['generated_at'] ) ? (int) $snapshots['services']['generated_at'] : 0;
		if ( $services_generated <= 0 || ( $now - $services_generated ) > $threshold ) {
			$stale = true;
		}

		if ( $services_generated > 0 ) {
			$oldest = min( $oldest, $services_generated );
		}

		if ( isset( $snapshots['policies'] ) && \is_array( $snapshots['policies'] ) ) {
			foreach ( $snapshots['policies'] as $entries ) {
				if ( ! \is_array( $entries ) ) {
					continue;
				}

				foreach ( $entries as $data ) {
					$generated = isset( $data['generated_at'] ) ? (int) $data['generated_at'] : 0;
					if ( $generated > 0 ) {
						$has_policy = true;
						$oldest     = min( $oldest, $generated );
						if ( ( $now - $generated ) > $threshold ) {
							$stale = true;
						}
					} else {
						$stale = true;
					}
				}
			}
		} else {
			$stale = true;
		}

		if ( ! $stale ) {
			return null;
		}

		if ( PHP_INT_MAX === $oldest ) {
			$oldest = $has_policy ? 0 : $services_generated;
		}

		return array(
			'timestamp' => $oldest > 0 ? $oldest : 0,
		);
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		$data = $this->prepare_settings_data();
		$this->renderer->render_settings_page( $data );
	}

	/**
	 * Render tools page.
	 *
	 * @return void
	 */
	public function render_tools_page() {
		$this->renderer->render_tools_page();
	}

	/**
	 * Render guide page.
	 *
	 * @return void
	 */
	public function render_guide_page() {
		$this->renderer->render_guide_page();
	}

	/**
	 * Handle settings save.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
		}

		\check_admin_referer( 'fp_privacy_save_settings', 'fp_privacy_nonce' );

		$languages = isset( $_POST['languages_active'] ) ? array_filter( array_map( 'trim', explode( ',', \wp_unslash( $_POST['languages_active'] ) ) ) ) : array();
		if ( empty( $languages ) ) {
			$languages = array( \get_locale() );
		}

	// Gestione banner_layout con enable_dark_mode
	$banner_layout = isset( $_POST['banner_layout'] ) ? \wp_unslash( $_POST['banner_layout'] ) : array();
	// Assicura che enable_dark_mode sia presente e booleano
	if ( isset( $_POST['banner_layout']['enable_dark_mode'] ) ) {
		$banner_layout['enable_dark_mode'] = true;
	} else {
		$banner_layout['enable_dark_mode'] = false;
	}

	$payload = array(
		'languages_active'       => $languages,
		'banner_texts'           => isset( $_POST['banner_texts'] ) ? \wp_unslash( $_POST['banner_texts'] ) : array(),
		'banner_layout'          => $banner_layout,
		'consent_mode_defaults'  => isset( $_POST['consent_mode_defaults'] ) ? \wp_unslash( $_POST['consent_mode_defaults'] ) : array(),
		'gpc_enabled'            => isset( $_POST['gpc_enabled'] ),
		'preview_mode'           => isset( $_POST['preview_mode'] ),
		'org_name'               => isset( $_POST['org_name'] ) ? \wp_unslash( $_POST['org_name'] ) : '',
		'vat'                    => isset( $_POST['vat'] ) ? \wp_unslash( $_POST['vat'] ) : '',
		'address'                => isset( $_POST['address'] ) ? \wp_unslash( $_POST['address'] ) : '',
		'dpo_name'               => isset( $_POST['dpo_name'] ) ? \wp_unslash( $_POST['dpo_name'] ) : '',
		'dpo_email'              => isset( $_POST['dpo_email'] ) ? \wp_unslash( $_POST['dpo_email'] ) : '',
		'privacy_email'          => isset( $_POST['privacy_email'] ) ? \wp_unslash( $_POST['privacy_email'] ) : '',
		'categories'             => $this->options->get( 'categories' ),
		'retention_days'         => isset( $_POST['retention_days'] ) ? (int) $_POST['retention_days'] : $this->options->get( 'retention_days' ),
		'scripts'                => isset( $_POST['scripts'] ) ? \wp_unslash( $_POST['scripts'] ) : array(),
		'detector_notifications' => array(
			'email'      => isset( $_POST['detector_notifications']['email'] ),
			'recipients' => isset( $_POST['detector_notifications']['recipients'] ) ? \wp_unslash( $_POST['detector_notifications']['recipients'] ) : '',
		),
	);

		$this->options->set( $payload );

		\wp_safe_redirect( \add_query_arg( 'updated', 'true', \wp_get_referer() ) );
		exit;
	}

	/**
	 * Handle revision bump.
	 *
	 * @return void
	 */
	public function handle_bump_revision() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
		}

		\check_admin_referer( 'fp_privacy_bump_revision' );

		$this->options->bump_revision();
		$this->options->set( $this->options->all() );

		\wp_safe_redirect( \add_query_arg( 'revision-bumped', '1', \wp_get_referer() ) );
		exit;
	}

	/**
	 * Handle settings export.
	 *
	 * @return void
	 */
	public function handle_export_settings() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
		}

		\check_admin_referer( 'fp_privacy_export_settings', 'fp_privacy_export_nonce' );

		$settings = $this->options->all();
		$filename = 'fp-privacy-settings-' . \gmdate( 'Ymd-His' ) . '.json';

		\nocache_headers();
		\header( 'Content-Type: application/json; charset=utf-8' );
		\header( 'Content-Disposition: attachment; filename="' . \sanitize_file_name( $filename ) . '"' );

		echo \wp_json_encode( $settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	/**
	 * Handle settings import.
	 *
	 * @return void
	 */
	public function handle_import_settings() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
		}

		\check_admin_referer( 'fp_privacy_import_settings', 'fp_privacy_import_nonce' );

		$redirect = \wp_get_referer() ? \wp_get_referer() : \admin_url( 'admin.php?page=fp-privacy-tools' );

	if ( empty( $_FILES['settings_file']['tmp_name'] ) || ! \is_uploaded_file( $_FILES['settings_file']['tmp_name'] ) ) {
		\wp_safe_redirect( \add_query_arg( 'fp-privacy-import', 'missing', $redirect ) );
		exit;
	}

	// Check file size to prevent memory exhaustion (limit to 5MB)
	$max_size = 5 * 1024 * 1024; // 5MB
	if ( ! empty( $_FILES['settings_file']['size'] ) && $_FILES['settings_file']['size'] > $max_size ) {
		\wp_safe_redirect( \add_query_arg( 'fp-privacy-import', 'too-large', $redirect ) );
		exit;
	}

	$content = \file_get_contents( $_FILES['settings_file']['tmp_name'] );
	if ( false === $content ) {
		\wp_safe_redirect( \add_query_arg( 'fp-privacy-import', 'error', $redirect ) );
		exit;
	}

		$data = \json_decode( $content, true );
		if ( ! is_array( $data ) ) {
			\wp_safe_redirect( \add_query_arg( 'fp-privacy-import', 'invalid', $redirect ) );
			exit;
		}

		$this->options->set( $data );
		\do_action( 'fp_privacy_settings_imported', $this->options->all() );

		\wp_safe_redirect( \add_query_arg( 'fp-privacy-import', 'success', $redirect ) );
		exit;
	}
}
<?php
/**
 * Settings export/import handler.
 *
 * @package FP\Privacy\Admin\Handler
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin\Handler;

use FP\Privacy\Utils\Options;

/**
 * Handles settings export and import operations.
 */
class SettingsExportImportHandler {
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
	 * Handle settings export.
	 *
	 * @return void
	 */
	public function handle_export() {
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
	public function handle_import() {
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

		$known_keys = \array_keys( $this->options->all() );
		$data       = \array_intersect_key( $data, \array_flip( $known_keys ) );

		if ( empty( $data ) ) {
			\wp_safe_redirect( \add_query_arg( 'fp-privacy-import', 'invalid', $redirect ) );
			exit;
		}

		$this->options->set( $data );
		\do_action( 'fp_privacy_settings_imported', $this->options->all() );

		\wp_safe_redirect( \add_query_arg( 'fp-privacy-import', 'success', $redirect ) );
		exit;
	}
}

















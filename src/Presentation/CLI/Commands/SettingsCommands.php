<?php
/**
 * WP-CLI commands for settings management.
 *
 * @package FP\Privacy\CLI
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\CLI\Commands;

use FP\Privacy\Utils\Options;
use WP_CLI;

/**
 * Handles WP-CLI commands related to settings management.
 */
class SettingsCommands {
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
	 * Export settings to JSON.
	 *
	 * ## OPTIONS
	 *
	 * --file=<path>
	 * : Destination file path.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function settings_export( $args, $assoc_args ) {
		if ( empty( $assoc_args['file'] ) ) {
			WP_CLI::error( 'Missing --file parameter.' );
		}

		$file    = $assoc_args['file'];
		$written = file_put_contents( $file, \wp_json_encode( $this->options->all(), JSON_PRETTY_PRINT ) );
		if ( ! $written ) {
			WP_CLI::error( 'Unable to write file.' );
		}

		WP_CLI::success( 'Settings exported to ' . $file );
	}

	/**
	 * Import settings from JSON.
	 *
	 * ## OPTIONS
	 *
	 * --file=<path>
	 * : Source file path.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function settings_import( $args, $assoc_args ) {
		if ( empty( $assoc_args['file'] ) || ! file_exists( $assoc_args['file'] ) ) {
			WP_CLI::error( 'File not found.' );
		}

		$data = json_decode( file_get_contents( $assoc_args['file'] ), true );
		if ( ! \is_array( $data ) ) {
			WP_CLI::error( 'Invalid JSON file.' );
		}

		$this->options->set( $data );
		\do_action( 'fp_privacy_settings_imported', $this->options->all() );

		WP_CLI::success( 'Settings imported.' );
	}
}

















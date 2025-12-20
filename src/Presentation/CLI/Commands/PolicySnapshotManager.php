<?php
/**
 * Policy snapshot manager.
 *
 * @package FP\Privacy\CLI
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\CLI\Commands;

use FP\Privacy\Utils\Options;
use WP_CLI;

/**
 * Handles saving and management of policy snapshots.
 */
class PolicySnapshotManager {
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
	 * Save snapshot of services and policies.
	 *
	 * @param array<int, array<string, mixed>> $services         Detected services.
	 * @param array<string, array<string, string>> $generated_privacy Generated privacy policies.
	 * @param array<string, array<string, string>> $generated_cookie  Generated cookie policies.
	 * @param int                                $timestamp       Timestamp.
	 *
	 * @return void
	 */
	public function save_snapshot( array $services, array $generated_privacy, array $generated_cookie, $timestamp ) {
		WP_CLI::log( '' );
		WP_CLI::log( '💾 Salvataggio snapshot servizi...' );

		$this->options->prime_script_rules_from_services( $services );

		$snapshots = array(
			'services' => array(
				'detected'     => array_values(
					array_filter(
						$services,
						static function( $s ) {
							return ! empty( $s['detected'] );
						}
					)
				),
				'generated_at' => $timestamp,
			),
			'policies' => array(
				'privacy' => array(),
				'cookie'  => array(),
			),
		);

		foreach ( $generated_privacy as $lang => $content ) {
			$snapshots['policies']['privacy'][ $lang ] = array(
				'content'      => $content,
				'generated_at' => $timestamp,
			);
		}

		foreach ( $generated_cookie as $lang => $content ) {
			$snapshots['policies']['cookie'][ $lang ] = array(
				'content'      => $content,
				'generated_at' => $timestamp,
			);
		}

		$payload                 = $this->options->all();
		$payload['snapshots']    = $snapshots;
		$payload['detector_alert'] = array_merge(
			$this->options->get_default_detector_alert(),
			array( 'last_checked' => $timestamp )
		);

		$this->options->set( $payload );

		\do_action( 'fp_privacy_snapshots_refreshed', $snapshots );

		WP_CLI::success( 'Snapshot salvato' );
	}
}
















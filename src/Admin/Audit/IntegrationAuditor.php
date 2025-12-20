<?php
/**
 * Integration auditor.
 *
 * @package FP\Privacy\Admin\Audit
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin\Audit;

use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Utils\Options;
use FP\Privacy\Utils\DetectorAlertManager;

/**
 * Performs integration audits and detects service changes.
 */
class IntegrationAuditor {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Policy generator.
	 *
	 * @var PolicyGenerator
	 */
	private $generator;

	/**
	 * Detector alert manager.
	 *
	 * @var DetectorAlertManager
	 */
	private $alert_manager;

	/**
	 * Constructor.
	 *
	 * @param Options            $options      Options handler.
	 * @param PolicyGenerator    $generator    Policy generator.
	 * @param DetectorAlertManager $alert_manager Alert manager.
	 */
	public function __construct( Options $options, PolicyGenerator $generator, DetectorAlertManager $alert_manager ) {
		$this->options       = $options;
		$this->generator     = $generator;
		$this->alert_manager = $alert_manager;
	}

	/**
	 * Execute scheduled audit and persist alert metadata.
	 *
	 * @return void
	 */
	public function run_audit() {
		$current   = $this->generator->snapshot( true );
		$snapshots = $this->options->get( 'snapshots', array() );
		$previous  = array();

		if ( is_array( $snapshots ) && isset( $snapshots['services']['detected'] ) && is_array( $snapshots['services']['detected'] ) ) {
			$previous = $snapshots['services']['detected'];
		}

		$this->options->prime_script_rules_from_services( $current );
		$diff      = $this->diff_services( $previous, $current );
		$timestamp = time();
		$alert     = $this->alert_manager->get_detector_alert();

		if ( empty( $diff['added'] ) && empty( $diff['removed'] ) ) {
			$alert['active']       = false;
			$alert['added']        = array();
			$alert['removed']      = array();
			$alert['detected_at']  = 0;
			$alert['last_checked'] = $timestamp;
			$this->alert_manager->set_detector_alert( $alert );

			return;
		}

		$alert['active']       = true;
		$alert['detected_at']  = $timestamp;
		$alert['last_checked'] = $timestamp;
		$alert['added']        = $this->summarize_services( $diff['added'] );
		$alert['removed']      = $this->summarize_services( $diff['removed'] );

		$this->alert_manager->set_detector_alert( $alert );

		// Auto-update services if enabled
		if ( $this->options->get( 'auto_update_services', false ) ) {
			$this->auto_update_services( $current, $timestamp );
		}
	}

	/**
	 * Compare previous and current detector output.
	 *
	 * @param array<int, array<string, mixed>> $previous Previous snapshot.
	 * @param array<int, array<string, mixed>> $current  Current snapshot.
	 *
	 * @return array{added:array<int, array<string, mixed>>, removed:array<int, array<string, mixed>>}
	 */
	public function diff_services( array $previous, array $current ) {
		$previous_indexed = $this->index_services( $previous );
		$current_indexed  = $this->index_services( $current );

		$added = array();
		foreach ( $current_indexed as $key => $service ) {
			if ( ! isset( $previous_indexed[ $key ] ) ) {
				$added[] = $service;
			}
		}

		$removed = array();
		foreach ( $previous_indexed as $key => $service ) {
			if ( ! isset( $current_indexed[ $key ] ) ) {
				$removed[] = $service;
			}
		}

		return array(
			'added'   => $added,
			'removed' => $removed,
		);
	}

	/**
	 * Index services by a stable key for diffing.
	 *
	 * @param array<int, array<string, mixed>> $services Services list.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function index_services( array $services ) {
		$indexed = array();

		foreach ( $services as $service ) {
			if ( ! is_array( $service ) ) {
				continue;
			}

			$slug = isset( $service['slug'] ) ? \sanitize_key( $service['slug'] ) : '';
			$name = isset( $service['name'] ) ? \sanitize_text_field( $service['name'] ) : '';
			$provider = isset( $service['provider'] ) ? \sanitize_text_field( $service['provider'] ) : '';

			$key = $slug;

			if ( '' === $key && '' !== $name ) {
				$key = \sanitize_key( $name . '-' . $provider );
			}

			if ( '' === $key ) {
				$encoded = \wp_json_encode( $service );
				$key = \md5( false !== $encoded ? $encoded : serialize( $service ) );
			}

			$indexed[ $key ] = $service;
		}

		return $indexed;
	}

	/**
	 * Normalize services for alert summaries.
	 *
	 * @param array<int, array<string, mixed>> $services Services list.
	 *
	 * @return array<int, array<string, string>>
	 */
	private function summarize_services( array $services ) {
		$summaries = array();

		foreach ( $services as $service ) {
			if ( ! is_array( $service ) ) {
				continue;
			}

			$summaries[] = array(
				'slug'     => \sanitize_key( $service['slug'] ?? '' ),
				'name'     => \sanitize_text_field( $service['name'] ?? '' ),
				'category' => \sanitize_key( $service['category'] ?? '' ),
				'provider' => \sanitize_text_field( $service['provider'] ?? '' ),
			);
		}

		return $summaries;
	}

	/**
	 * Automatically update services snapshot and optionally regenerate policies.
	 *
	 * @param array<int, array<string, mixed>> $services   Current detected services.
	 * @param int                              $timestamp  Current timestamp.
	 *
	 * @return void
	 */
	private function auto_update_services( array $services, $timestamp ) {
		$snapshots = $this->options->get( 'snapshots', array() );
		
		// Update services snapshot
		$snapshots['services'] = array(
			'detected'     => $services,
			'generated_at' => $timestamp,
		);

		// Auto-update policies if enabled
		if ( $this->options->get( 'auto_update_policies', false ) ) {
			$languages = $this->options->get_languages();
			
			if ( ! isset( $snapshots['policies'] ) || ! is_array( $snapshots['policies'] ) ) {
				$snapshots['policies'] = array(
					'privacy' => array(),
					'cookie'  => array(),
				);
			}

			// Generate policies for each language
			foreach ( $languages as $lang ) {
				$lang = $this->options->normalize_language( $lang );

				// Generate privacy policy
				$privacy_content = $this->generator->generate_privacy_policy( $lang );
				$snapshots['policies']['privacy'][ $lang ] = array(
					'content'      => $privacy_content,
					'generated_at' => $timestamp,
				);

				// Generate cookie policy
				$cookie_content = $this->generator->generate_cookie_policy( $lang );
				$snapshots['policies']['cookie'][ $lang ] = array(
					'content'      => $cookie_content,
					'generated_at' => $timestamp,
				);

				// Update the actual pages
				$privacy_page_id = $this->options->get_page_id( 'privacy_policy', $lang );
				if ( $privacy_page_id ) {
					\wp_update_post(
						array(
							'ID'           => $privacy_page_id,
							'post_content' => $privacy_content,
						)
					);
				}

				$cookie_page_id = $this->options->get_page_id( 'cookie_policy', $lang );
				if ( $cookie_page_id ) {
					\wp_update_post(
						array(
							'ID'           => $cookie_page_id,
							'post_content' => $cookie_content,
						)
					);
				}
			}

			// Bump revision number
			$this->options->bump_revision();
		}

		// Save updated snapshots
		$payload             = $this->options->all();
		$payload['snapshots'] = $snapshots;

		$this->options->set( $payload );

		// Trigger action for other integrations
		\do_action( 'fp_privacy_auto_update_completed', $snapshots, $services );
	}
}

















<?php
/**
 * Integration auditor.
 *
 * @package FP\Privacy\Admin\Audit
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

declare(strict_types=1);

namespace FP\Privacy\Admin\Audit;

use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Utils\Options;
use FP\Privacy\Utils\DetectorAlertManager;
use FP\Privacy\Services\Policy\PolicyAutoUpdater;

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
	 * Policy auto-updater.
	 *
	 * @var PolicyAutoUpdater|null
	 */
	private $auto_updater;

	/**
	 * Constructor.
	 *
	 * @param Options                  $options       Options handler.
	 * @param PolicyGenerator          $generator     Policy generator.
	 * @param DetectorAlertManager     $alert_manager Alert manager.
	 * @param PolicyAutoUpdater|null   $auto_updater  Policy auto-updater (optional for backward compatibility).
	 */
	public function __construct( Options $options, PolicyGenerator $generator, DetectorAlertManager $alert_manager, ?PolicyAutoUpdater $auto_updater = null ) {
		$this->options       = $options;
		$this->generator     = $generator;
		$this->alert_manager = $alert_manager;
		$this->auto_updater  = $auto_updater;
	}

	/**
	 * Execute scheduled audit and persist alert metadata.
	 *
	 * @return void
	 */
	public function run_audit(): void {
		$current   = $this->generator->snapshot( true );
		$snapshots = $this->options->get( 'snapshots', array() );
		$previous  = array();

		if ( is_array( $snapshots ) && isset( $snapshots['services']['detected'] ) && is_array( $snapshots['services']['detected'] ) ) {
			$previous = $snapshots['services']['detected'];
		}

		$alert         = $this->alert_manager->get_detector_alert();
		$prior_streaks = isset( $alert['absence_streaks'] ) && is_array( $alert['absence_streaks'] ) ? $alert['absence_streaks'] : array();

		$previous_indexed = $this->index_services( $previous );
		$current_indexed  = $this->index_services( $current );

		$new_streaks = $this->compute_absence_streaks( $previous_indexed, $current_indexed, $prior_streaks );
		$threshold   = $this->removal_confirmation_threshold();

		$confirmed_removed = $this->services_confirmed_absent( $previous_indexed, $current_indexed, $new_streaks, $threshold );
		$added             = $this->list_added_services( $previous_indexed, $current_indexed );

		$effective = $this->build_effective_detected_list( $current_indexed, $previous_indexed, $new_streaks, $threshold );

		$this->options->prime_script_rules_from_services( $effective );

		$timestamp = time();

		$this->persist_service_snapshot_if_needed( $effective, $timestamp );

		$alert['absence_streaks'] = $new_streaks;
		$alert['last_checked']    = $timestamp;

		if ( empty( $added ) && empty( $confirmed_removed ) ) {
			$alert['active']      = false;
			$alert['added']       = array();
			$alert['removed']     = array();
			$alert['detected_at'] = 0;
			$this->alert_manager->set_detector_alert( $alert );

			return;
		}

		$alert['active']      = true;
		$alert['detected_at'] = $timestamp;
		$alert['added']       = $this->summarize_services( $added );
		$alert['removed']     = $this->summarize_services( $confirmed_removed );

		$this->alert_manager->set_detector_alert( $alert );

		if ( $this->options->get( 'auto_update_services', false ) ) {
			$this->auto_update_services( $effective, $timestamp );
		}
	}

	/**
	 * Compare previous and current detector output (single-scan diff, no absence confirmation).
	 *
	 * @param array<int, array<string, mixed>> $previous Previous snapshot.
	 * @param array<int, array<string, mixed>> $current  Current snapshot.
	 *
	 * @return array{added: array<int, array<string, mixed>>, removed: array<int, array<string, mixed>>}
	 */
	public function diff_services( array $previous, array $current ): array {
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
	 * Consecutive audits without detection required before a service is treated as removed.
	 *
	 * Default 2 mitigates false negatives (e.g. pattern scan limits). Filter `fp_privacy_integration_absence_confirm_scans`
	 * accepts 1–10; use 1 to restore immediate removal behaviour.
	 *
	 * @return int
	 */
	private function removal_confirmation_threshold(): int {
		$n = (int) \apply_filters( 'fp_privacy_integration_absence_confirm_scans', 2 );

		return max( 1, min( 10, $n ) );
	}

	/**
	 * Update per-key absence streaks from the last snapshot vs the current scan.
	 *
	 * @param array<string, array<string, mixed>> $previous_indexed Previous services keyed by stable id.
	 * @param array<string, array<string, mixed>> $current_indexed  Current scan keyed by stable id.
	 * @param array<string, int>                   $prior_streaks     Streaks from the previous audit.
	 *
	 * @return array<string, int>
	 */
	private function compute_absence_streaks( array $previous_indexed, array $current_indexed, array $prior_streaks ): array {
		$new = array();

		foreach ( $previous_indexed as $key => $_service ) {
			if ( isset( $current_indexed[ $key ] ) ) {
				continue;
			}

			$prev = isset( $prior_streaks[ $key ] ) ? (int) $prior_streaks[ $key ] : 0;
			$new[ $key ] = $prev + 1;
		}

		return $new;
	}

	/**
	 * Services that were in the snapshot but are still missing after enough consecutive misses.
	 *
	 * @param array<string, array<string, mixed>> $previous_indexed Previous services.
	 * @param array<string, array<string, mixed>> $current_indexed  Current scan.
	 * @param array<string, int>                   $streaks          Absence streaks.
	 * @param int                                  $threshold        Required consecutive misses.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function services_confirmed_absent( array $previous_indexed, array $current_indexed, array $streaks, int $threshold ): array {
		$out = array();

		foreach ( $previous_indexed as $key => $service ) {
			if ( isset( $current_indexed[ $key ] ) ) {
				continue;
			}

			$streak = isset( $streaks[ $key ] ) ? (int) $streaks[ $key ] : 0;

			if ( $streak >= $threshold ) {
				$out[] = $service;
			}
		}

		return $out;
	}

	/**
	 * Newly seen services (present in current scan, absent from previous snapshot).
	 *
	 * @param array<string, array<string, mixed>> $previous_indexed Previous services.
	 * @param array<string, array<string, mixed>> $current_indexed  Current scan.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function list_added_services( array $previous_indexed, array $current_indexed ): array {
		$added = array();

		foreach ( $current_indexed as $key => $service ) {
			if ( ! isset( $previous_indexed[ $key ] ) ) {
				$added[] = $service;
			}
		}

		return $added;
	}

	/**
	 * Build the service list used for script rules, snapshot alignment, and auto-update: current scan plus
	 * previous rows retained until removal is confirmed.
	 *
	 * @param array<string, array<string, mixed>> $current_indexed  Current scan.
	 * @param array<string, array<string, mixed>> $previous_indexed Previous snapshot.
	 * @param array<string, int>                   $streaks          Absence streaks.
	 * @param int                                  $threshold        Confirmation threshold.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function build_effective_detected_list( array $current_indexed, array $previous_indexed, array $streaks, int $threshold ): array {
		$merged = $current_indexed;

		foreach ( $previous_indexed as $key => $service ) {
			if ( isset( $merged[ $key ] ) ) {
				continue;
			}

			$streak = isset( $streaks[ $key ] ) ? (int) $streaks[ $key ] : 0;

			if ( $streak < $threshold ) {
				$row            = $service;
				$row['detected'] = true;
				$merged[ $key ] = $row;
			}
		}

		return array_values( $merged );
	}

	/**
	 * Persist detected services snapshot when the effective key set changed (keeps DB aligned with conservative detection).
	 *
	 * @param array<int, array<string, mixed>> $effective   Effective detected services.
	 * @param int                              $timestamp   Unix time.
	 *
	 * @return void
	 */
	private function persist_service_snapshot_if_needed( array $effective, int $timestamp ): void {
		$snapshots = $this->options->get( 'snapshots', array() );

		if ( ! is_array( $snapshots ) ) {
			$snapshots = array();
		}

		$old = array();
		if ( isset( $snapshots['services']['detected'] ) && is_array( $snapshots['services']['detected'] ) ) {
			$old = $snapshots['services']['detected'];
		}

		if ( $this->same_detected_service_keys( $old, $effective ) ) {
			return;
		}

		$snapshots['services'] = array(
			'detected'     => $effective,
			'generated_at' => $timestamp,
		);

		$payload              = $this->options->all();
		$payload['snapshots'] = $snapshots;

		$this->options->set( $payload );
	}

	/**
	 * Whether two detected-service lists contain the same stable keys (order-insensitive).
	 *
	 * @param array<int, array<string, mixed>> $a List A.
	 * @param array<int, array<string, mixed>> $b List B.
	 *
	 * @return bool
	 */
	private function same_detected_service_keys( array $a, array $b ): bool {
		$ka = array_keys( $this->index_services( $a ) );
		$kb = array_keys( $this->index_services( $b ) );
		sort( $ka, SORT_STRING );
		sort( $kb, SORT_STRING );

		return $ka === $kb;
	}

	/**
	 * Index services by a stable key for diffing.
	 *
	 * @param array<int, array<string, mixed>> $services Services list.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function index_services( array $services ): array {
		$indexed = array();

		foreach ( $services as $service ) {
			if ( ! is_array( $service ) ) {
				continue;
			}

			$slug = isset( $service['slug'] ) ? \sanitize_key( (string) $service['slug'] ) : '';
			$name = isset( $service['name'] ) ? \sanitize_text_field( (string) $service['name'] ) : '';
			$provider = isset( $service['provider'] ) ? \sanitize_text_field( (string) $service['provider'] ) : '';

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
	private function summarize_services( array $services ): array {
		$summaries = array();

		foreach ( $services as $service ) {
			if ( ! is_array( $service ) ) {
				continue;
			}

			$summaries[] = array(
				'slug'     => \sanitize_key( (string) ( $service['slug'] ?? '' ) ),
				'name'     => \sanitize_text_field( (string) ( $service['name'] ?? '' ) ),
				'category' => \sanitize_key( (string) ( $service['category'] ?? '' ) ),
				'provider' => \sanitize_text_field( (string) ( $service['provider'] ?? '' ) ),
			);
		}

		return $summaries;
	}

	/**
	 * Automatically update services snapshot and optionally regenerate policies.
	 *
	 * @param array<int, array<string, mixed>> $services   Effective detected services (already persisted unless identical keys).
	 * @param int                              $timestamp  Current timestamp.
	 *
	 * @return void
	 */
	private function auto_update_services( array $services, int $timestamp ): void {
		$snapshots = $this->options->get( 'snapshots', array() );

		if ( ! is_array( $snapshots ) ) {
			$snapshots = array();
		}

		// Update services snapshot.
		$snapshots['services'] = array(
			'detected'     => $services,
			'generated_at' => $timestamp,
		);

		// Auto-update policies if enabled.
		if ( $this->options->get( 'auto_update_policies', false ) ) {
			// Use PolicyAutoUpdater if available, otherwise fallback to inline logic.
			if ( $this->auto_updater && $this->auto_updater->should_update() ) {
				$this->auto_updater->update_all_policies( false );
			} else {
				// Fallback to inline logic for backward compatibility.
				$languages = $this->options->get_languages();

				if ( ! isset( $snapshots['policies'] ) || ! is_array( $snapshots['policies'] ) ) {
					$snapshots['policies'] = array(
						'privacy' => array(),
						'cookie'  => array(),
					);
				}

				// Generate policies for each language.
				foreach ( $languages as $lang ) {
					$lang = $this->options->normalize_language( $lang );

					// Generate privacy policy.
					$privacy_content = $this->generator->generate_privacy_policy( $lang );
					$snapshots['policies']['privacy'][ $lang ] = array(
						'content'      => $privacy_content,
						'generated_at' => $timestamp,
					);

					// Generate cookie policy.
					$cookie_content = $this->generator->generate_cookie_policy( $lang );
					$snapshots['policies']['cookie'][ $lang ] = array(
						'content'      => $cookie_content,
						'generated_at' => $timestamp,
					);

					// Update the actual pages.
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
			}

			// Bump revision number.
			$this->options->bump_revision();
		}

		// Save updated snapshots.
		$payload              = $this->options->all();
		$payload['snapshots'] = $snapshots;

		$this->options->set( $payload );

		// Trigger action for other integrations.
		\do_action( 'fp_privacy_auto_update_completed', $snapshots, $services );
	}
}

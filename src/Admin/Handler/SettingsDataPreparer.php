<?php
/**
 * Settings data preparer.
 *
 * @package FP\Privacy\Admin\Handler
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin\Handler;

use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Utils\Options;

/**
 * Prepares data for settings page rendering.
 */
class SettingsDataPreparer {
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
	 * Policy links auto-populator.
	 *
	 * @var PolicyLinksAutoPopulator
	 */
	private $policy_links_populator;

	/**
	 * Constructor.
	 *
	 * @param Options                  $options              Options handler.
	 * @param DetectorRegistry          $detector             Detector registry.
	 * @param PolicyLinksAutoPopulator $policy_links_populator Policy links auto-populator.
	 */
	public function __construct( Options $options, DetectorRegistry $detector, PolicyLinksAutoPopulator $policy_links_populator ) {
		$this->options                = $options;
		$this->detector               = $detector;
		$this->policy_links_populator = $policy_links_populator;
	}

	/**
	 * Prepare data for settings page.
	 *
	 * @return array<string, mixed>
	 */
	public function prepare() {
		$options      = $this->options->all();
		$languages    = $this->options->get_languages();
		$primary_lang = $languages[0] ?? $this->options->normalize_language( \function_exists( '\get_locale' ) ? \get_locale() : 'en_US' );

		$script_rules      = array();
		$script_categories = array();

		foreach ( $languages as $script_lang ) {
			$normalized                       = $this->options->normalize_language( $script_lang );
			$script_rules[ $normalized ]      = $this->options->get_script_rules_for_language( $normalized );
			$script_categories[ $normalized ] = $this->options->get_categories_for_language( $normalized );
		}

		// Auto-populate link_policy with Privacy Policy page URL if empty
		$this->policy_links_populator->auto_populate( $options, $languages );

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
	private function get_snapshot_notice( $snapshots ) {
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
}


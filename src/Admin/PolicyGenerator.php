<?php
/**
 * Policy generator.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Utils\Options;
use FP\Privacy\Utils\View;

/**
 * Generates policy contents based on detected services and options.
 */
class PolicyGenerator {
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
	 * Service grouper.
	 *
	 * @var PolicyServiceGrouper
	 */
	private $service_grouper;

	/**
	 * View renderer.
	 *
	 * @var View
	 */
	private $view;

	/**
	 * Constructor.
	 *
	 * @param Options          $options  Options.
	 * @param DetectorRegistry $detector Detector.
	 * @param View             $view     View renderer.
	 */
	public function __construct( Options $options, DetectorRegistry $detector, View $view ) {
		$this->options        = $options;
		$this->detector       = $detector;
		$this->service_grouper = new PolicyServiceGrouper( $options, $detector );
		$this->view            = $view;
	}

/**
 * Generate privacy policy HTML.
 *
 * @param string $lang Language.
 *
 * @return string
 */
	public function generate_privacy_policy( $lang ) {
		try {
			$groups = $this->service_grouper->get_grouped_services( false, $lang );
			if ( ! is_array( $groups ) ) {
				$groups = array();
			}

			$categories_meta = $this->options->get_categories_for_language( $lang );
			if ( ! is_array( $categories_meta ) ) {
				$categories_meta = array();
			}

			$options = $this->options->all();
			if ( ! is_array( $options ) ) {
				$options = array();
			}

			return $this->view->render(
				'privacy-policy.php',
				array(
					'lang'            => $lang,
					'options'        => $options,
					'groups'          => $groups,
					'generated_at'    => $this->get_policy_generated_at( 'privacy', $lang ),
					'categories_meta' => $categories_meta,
				)
			);
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'FP Privacy: Error generating privacy policy for %s: %s', $lang, $e->getMessage() ) );
			}
			return '';
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'FP Privacy: Error generating privacy policy for %s: %s', $lang, $e->getMessage() ) );
			}
			return '';
		}
	}

	/**
	 * Generate cookie policy HTML.
	 *
	 * @param string $lang Language.
	 *
	 * @return string
	 */
	public function generate_cookie_policy( $lang ) {
		try {
			$groups = $this->service_grouper->get_grouped_services( false, $lang );
			if ( ! is_array( $groups ) ) {
				$groups = array();
			}

			$categories_meta = $this->options->get_categories_for_language( $lang );
			if ( ! is_array( $categories_meta ) ) {
				$categories_meta = array();
			}

			$options = $this->options->all();
			if ( ! is_array( $options ) ) {
				$options = array();
			}

			return $this->view->render(
				'cookie-policy.php',
				array(
					'lang'            => $lang,
					'options'        => $options,
					'groups'          => $groups,
					'generated_at'    => $this->get_policy_generated_at( 'cookie', $lang ),
					'categories_meta' => $categories_meta,
				)
			);
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'FP Privacy: Error generating cookie policy for %s: %s', $lang, $e->getMessage() ) );
			}
			return '';
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'FP Privacy: Error generating cookie policy for %s: %s', $lang, $e->getMessage() ) );
			}
			return '';
		}
	}

	/**
	 * Get grouped services.
	 *
	 * @param bool   $force Force cache refresh.
	 * @param string $lang  Language override.
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function group_services( $force = false, $lang = '' ) {
		return $this->service_grouper->get_grouped_services( $force, $lang );
	}

	/**
	 * Export snapshot of services.
	 *
	 * @param bool $force Force a fresh detection.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function snapshot( $force = false ) {
		$services = $this->detector->detect_services( (bool) $force );

		return array_values(
			array_filter(
				$services,
				static function ( $service ) {
					return ! empty( $service['detected'] );
				}
			)
		);
	}

    /**
     * Retrieve the stored generation timestamp for a policy.
     *
     * @param string $type Policy type (privacy|cookie).
     * @param string $lang Language code.
     *
     * @return int
     */
    private function get_policy_generated_at( $type, $lang ) {
        $lang      = $this->options->normalize_language( $lang );
        $snapshots = $this->options->get( 'snapshots', array() );

        if ( ! is_array( $snapshots ) || empty( $snapshots['policies'][ $type ][ $lang ] ) ) {
            return 0;
        }

        $value = $snapshots['policies'][ $type ][ $lang ];

        return isset( $value['generated_at'] ) ? (int) $value['generated_at'] : 0;
    }
}

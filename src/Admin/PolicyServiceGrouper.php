<?php
/**
 * Policy service grouper.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Utils\Options;
use function is_array;
use function sanitize_key;

/**
 * Handles grouping of services by category for policy generation.
 */
class PolicyServiceGrouper {
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
	 * Cached grouped services for the current request.
	 *
	 * @var array<string, array<int, array<string, mixed>>>|null
	 */
	private $grouped_services = array();

	/**
	 * Tracks whether grouped services have been hydrated during the request.
	 *
	 * @var array<string, bool>
	 */
	private $groups_refreshed = array();

	/**
	 * Constructor.
	 *
	 * @param Options          $options  Options handler.
	 * @param DetectorRegistry $detector Detector registry.
	 */
	public function __construct( Options $options, DetectorRegistry $detector ) {
		$this->options  = $options;
		$this->detector = $detector;
	}

	/**
	 * Get grouped services.
	 *
	 * @param bool   $force Force cache refresh.
	 * @param string $lang  Language override.
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function get_grouped_services( $force = false, $lang = '' ) {
		$lang = $this->options->normalize_language( $lang ?: ( $this->options->get_languages()[0] ?? ( function_exists( '\get_locale' ) ? \get_locale() : 'en_US' ) ) );

		$this->ensure_services_cache( $force, $lang );

		return isset( $this->grouped_services[ $lang ] ) ? $this->grouped_services[ $lang ] : array();
	}

	/**
	 * Ensure grouped services are hydrated for the current request.
	 *
	 * @param bool   $force Force cache refresh.
	 * @param string $lang  Language override.
	 *
	 * @return void
	 */
	private function ensure_services_cache( $force = false, $lang = '' ) {
		$lang = $this->options->normalize_language( $lang ?: ( $this->options->get_languages()[0] ?? ( function_exists( '\get_locale' ) ? \get_locale() : 'en_US' ) ) );

		if ( isset( $this->groups_refreshed[ $lang ] ) && $this->groups_refreshed[ $lang ] && ! $force && isset( $this->grouped_services[ $lang ] ) ) {
			return;
		}

		$services = $this->detector->detect_services( $force );
		$groups   = array();
		$seen     = array();

		// Group detected services
		foreach ( $services as $service ) {
			$detected_flag = isset( $service['detected'] ) ? (bool) $service['detected'] : true;

			if ( ! $detected_flag ) {
				continue;
			}

			$service['detected'] = $detected_flag;
			$category            = isset( $service['category'] ) ? $service['category'] : 'uncategorized';

			if ( ! isset( $groups[ $category ] ) ) {
				$groups[ $category ] = array();
				$seen[ $category ]    = array();
			} elseif ( ! isset( $seen[ $category ] ) ) {
				$seen[ $category ] = array();
			}

			$key = '';
			if ( isset( $service['slug'] ) && '' !== $service['slug'] ) {
				$key = sanitize_key( $service['slug'] );
			} elseif ( isset( $service['name'] ) ) {
				$key = sanitize_key( $service['name'] );
			}

			if ( '' !== $key && isset( $seen[ $category ][ $key ] ) ) {
				continue;
			}

			if ( '' !== $key ) {
				$seen[ $category ][ $key ] = true;
			}

			$service['category'] = $category;
			$groups[ $category ][] = $service;
		}

		// Add manually configured services
		$configured = $this->options->get_categories_for_language( $lang );

		foreach ( $configured as $category => $meta ) {
			if ( ! isset( $groups[ $category ] ) ) {
				$groups[ $category ] = array();
			}

			if ( ! isset( $seen[ $category ] ) ) {
				$seen[ $category ] = array();
			}

			if ( empty( $meta['services'] ) || ! is_array( $meta['services'] ) ) {
				continue;
			}

			foreach ( $meta['services'] as $entry ) {
				$normalized = PolicyServiceNormalizer::normalize( $entry, $category );

				if ( empty( $normalized ) ) {
					continue;
				}

				$key = $normalized['slug'];

				if ( '' !== $key && isset( $seen[ $category ][ $key ] ) ) {
					continue;
				}

				if ( '' !== $key ) {
					$seen[ $category ][ $key ] = true;
				}

				$groups[ $category ][] = $normalized;
			}
		}

		// Normalize array indices
		foreach ( $groups as $category => $items ) {
			$groups[ $category ] = array_values( $items );
		}

		$this->grouped_services[ $lang ] = $groups;
		$this->groups_refreshed[ $lang ] = true;
	}
}
















<?php
/**
 * Policy links auto-populator.
 *
 * @package FP\Privacy\Admin\Handler
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\Admin\Controllers;

use FP\Privacy\Utils\Options;

/**
 * Auto-populates policy links in banner texts.
 */
class PolicyLinksAutoPopulator {
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
	 * Auto-populate policy links in options array.
	 *
	 * @param array<string, mixed> &$options  Options array to update.
	 * @param array<int, string>   $languages Active languages.
	 *
	 * @return void
	 */
	public function auto_populate( array &$options, array $languages ) {
		if ( ! isset( $options['pages'] ) || ! \is_array( $options['pages'] ) ) {
			return;
		}

		$pages = $options['pages'];

		foreach ( $languages as $language ) {
			$normalized = $this->options->normalize_language( $language );

			// Get Privacy Policy page ID for this language
			$privacy_page_id = 0;
			if ( isset( $pages['privacy_policy_page_id'][ $normalized ] ) ) {
				$privacy_page_id = (int) $pages['privacy_policy_page_id'][ $normalized ];
			}

			// If we have a Privacy Policy page, auto-populate link_policy if empty
			if ( $privacy_page_id > 0 ) {
				$permalink = \get_permalink( $privacy_page_id );

				if ( $permalink && ! \is_wp_error( $permalink ) ) {
					// Initialize banner_texts for this language if needed
					if ( ! isset( $options['banner_texts'][ $normalized ] ) || ! \is_array( $options['banner_texts'][ $normalized ] ) ) {
						$options['banner_texts'][ $normalized ] = array();
					}

					// Auto-populate only if link_policy is empty
					if ( empty( $options['banner_texts'][ $normalized ]['link_policy'] ) ) {
						$options['banner_texts'][ $normalized ]['link_policy'] = $permalink;
					}
				}
			}
		}
	}

	/**
	 * Auto-populate policy links in payload before saving.
	 *
	 * @param array<string, mixed> &$payload   Payload to update.
	 * @param array<int, string>   $languages  Active languages.
	 *
	 * @return void
	 */
	public function auto_populate_before_save( array &$payload, array $languages ) {
		// Get current pages configuration
		$pages = $this->options->get( 'pages' );
		if ( ! \is_array( $pages ) ) {
			return;
		}

		foreach ( $languages as $language ) {
			$normalized = $this->options->normalize_language( $language );

			// Get Privacy Policy page ID for this language
			$privacy_page_id = 0;
			if ( isset( $pages['privacy_policy_page_id'][ $normalized ] ) ) {
				$privacy_page_id = (int) $pages['privacy_policy_page_id'][ $normalized ];
			}

			// If we have a Privacy Policy page, auto-populate link_policy if empty
			if ( $privacy_page_id > 0 ) {
				$permalink = \get_permalink( $privacy_page_id );

				if ( $permalink && ! \is_wp_error( $permalink ) ) {
					// Initialize banner_texts for this language if needed
					if ( ! isset( $payload['banner_texts'][ $normalized ] ) || ! \is_array( $payload['banner_texts'][ $normalized ] ) ) {
						$payload['banner_texts'][ $normalized ] = array();
					}

					// Auto-populate only if link_policy is empty
					if ( empty( $payload['banner_texts'][ $normalized ]['link_policy'] ) ) {
						$payload['banner_texts'][ $normalized ]['link_policy'] = $permalink;
					}
				}
			}
		}
	}
}

















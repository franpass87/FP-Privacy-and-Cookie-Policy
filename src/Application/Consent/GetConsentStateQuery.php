<?php
/**
 * Get consent state query handler.
 *
 * @package FP\Privacy\Application\Consent
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Application\Consent;

use FP\Privacy\Domain\Consent\ConsentRepositoryInterface;
use FP\Privacy\Infrastructure\Options\OptionsRepositoryInterface;
use FP\Privacy\Utils\Options as LegacyOptions;

/**
 * Query handler for retrieving consent state.
 * 
 * This handler retrieves the current consent state for a user,
 * including cookie data, last event, and categories.
 */
class GetConsentStateQuery {
	/**
	 * Consent repository.
	 *
	 * @var ConsentRepositoryInterface
	 */
	private $repository;

	/**
	 * Options repository.
	 *
	 * @var OptionsRepositoryInterface
	 */
	private $options;

	/**
	 * Legacy options instance (for methods not in interface).
	 *
	 * @var LegacyOptions
	 */
	private $legacy_options;

	/**
	 * Constructor.
	 *
	 * @param ConsentRepositoryInterface $repository Consent repository.
	 * @param OptionsRepositoryInterface $options Options repository.
	 * @param LegacyOptions|null          $legacy_options Legacy options (for backward compatibility).
	 */
	public function __construct(
		ConsentRepositoryInterface $repository,
		OptionsRepositoryInterface $options,
		?LegacyOptions $legacy_options = null
	) {
		$this->repository    = $repository;
		$this->options       = $options;
		$this->legacy_options = $legacy_options;
	}

	/**
	 * Get consent state for a consent ID.
	 *
	 * @param string $consent_id Consent ID.
	 * @param string $lang Language code.
	 * @return array<string, mixed> Consent state data.
	 */
	public function handle( string $consent_id, string $lang = 'en' ): array {
		// Find latest record for this consent ID.
		$record = $this->repository->find_latest_by_consent_id( $consent_id );

		$states = array(
			'categories'    => array(),
			'last_event'    => '',
			'last_revision' => 0,
		);

		if ( $record ) {
			$states['categories']    = isset( $record['states'] ) && is_array( $record['states'] ) ? $record['states'] : array();
			$states['last_event']    = isset( $record['created_at'] ) ? $record['created_at'] : '';
			$states['last_revision'] = isset( $record['rev'] ) ? (int) $record['rev'] : 0;
		}

		// Get banner texts and categories for the language.
		$normalized_lang = $this->normalizeLanguage( $lang );
		$text            = $this->getBannerText( $normalized_lang );
		$categories      = $this->getCategories( $normalized_lang );

		// Get policy page URLs.
		$privacy_page_id = $this->getPageId( 'privacy_policy', $normalized_lang );
		$cookie_page_id  = $this->getPageId( 'cookie_policy', $normalized_lang );

		$privacy_url = '';
		$cookie_url  = '';

		if ( $privacy_page_id && $privacy_page_id > 0 ) {
			$privacy_permalink = get_permalink( $privacy_page_id );
			if ( $privacy_permalink && ! is_wp_error( $privacy_permalink ) ) {
				$privacy_url = $privacy_permalink;
			}
		}

		if ( $cookie_page_id && $cookie_page_id > 0 && $cookie_page_id !== $privacy_page_id ) {
			$cookie_permalink = get_permalink( $cookie_page_id );
			if ( $cookie_permalink && ! is_wp_error( $cookie_permalink ) ) {
				$cookie_url = $cookie_permalink;
			}
		}

		return array(
			'texts'       => $text,
			'layout'      => $this->options->get( 'banner_layout' ),
			'categories'  => $categories,
			'state'       => $states,
			'mode'        => $this->options->get( 'consent_mode_defaults' ),
			'policy_urls' => array(
				'privacy' => $privacy_url,
				'cookie'  => $cookie_url,
			),
		);
	}

	/**
	 * Get legacy options instance (with fallback to singleton for backward compatibility).
	 *
	 * @return LegacyOptions Legacy options instance.
	 */
	private function getLegacyOptions(): LegacyOptions {
		if ( $this->legacy_options ) {
			return $this->legacy_options;
		}
		// Fallback to singleton for backward compatibility.
		return LegacyOptions::instance();
	}

	/**
	 * Normalize language code.
	 *
	 * @param string $lang Language code.
	 * @return string Normalized language code.
	 */
	private function normalizeLanguage( string $lang ): string {
		$options = $this->getLegacyOptions();
		if ( method_exists( $options, 'normalize_language' ) ) {
			return $options->normalize_language( $lang );
		}
		return substr( $lang, 0, 2 );
	}

	/**
	 * Get banner text for language.
	 *
	 * @param string $lang Language code.
	 * @return array<string, string> Banner texts.
	 */
	private function getBannerText( string $lang ): array {
		$options = $this->getLegacyOptions();
		if ( method_exists( $options, 'get_banner_text' ) ) {
			return $options->get_banner_text( $lang );
		}
		return array();
	}

	/**
	 * Get categories for language.
	 *
	 * @param string $lang Language code.
	 * @return array<string, mixed> Categories.
	 */
	private function getCategories( string $lang ): array {
		$options = $this->getLegacyOptions();
		if ( method_exists( $options, 'get_categories_for_language' ) ) {
			return $options->get_categories_for_language( $lang );
		}
		return array();
	}

	/**
	 * Get page ID for policy type and language.
	 *
	 * @param string $type Policy type ('privacy_policy' or 'cookie_policy').
	 * @param string $lang Language code.
	 * @return int Page ID.
	 */
	private function getPageId( string $type, string $lang ): int {
		$options = $this->getLegacyOptions();
		if ( method_exists( $options, 'get_page_id' ) ) {
			return (int) $options->get_page_id( $type, $lang );
		}
		return 0;
	}
}








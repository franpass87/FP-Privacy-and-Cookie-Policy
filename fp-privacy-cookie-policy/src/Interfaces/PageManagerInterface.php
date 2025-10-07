<?php
/**
 * Page manager interface.
 *
 * @package FP\Privacy\Interfaces
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Interfaces;

/**
 * Interface for WordPress pages management.
 */
interface PageManagerInterface {
	/**
	 * Ensure required pages exist for all languages.
	 *
	 * @param array<string, mixed> $pages     Current pages configuration.
	 * @param array<int, string>   $languages Active languages.
	 *
	 * @return array<string, mixed>|null Updated pages configuration or null if no changes.
	 */
	public function ensure_pages_exist( array $pages, array $languages );

	/**
	 * Retrieve a policy page id for type and language.
	 *
	 * @param string               $type  privacy_policy|cookie_policy.
	 * @param string               $lang  Locale.
	 * @param array<string, mixed> $pages Pages configuration.
	 *
	 * @return int
	 */
	public function get_page_id( $type, $lang, array $pages );
}
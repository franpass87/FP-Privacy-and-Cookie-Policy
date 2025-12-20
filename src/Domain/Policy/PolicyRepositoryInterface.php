<?php
/**
 * Policy repository interface.
 *
 * @package FP\Privacy\Domain\Policy
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Domain\Policy;

/**
 * Interface for policy repository implementations.
 */
interface PolicyRepositoryInterface {
	/**
	 * Get privacy policy page ID.
	 *
	 * @return int|null Page ID or null if not set.
	 */
	public function getPrivacyPolicyPageId(): ?int;

	/**
	 * Get cookie policy page ID.
	 *
	 * @return int|null Page ID or null if not set.
	 */
	public function getCookiePolicyPageId(): ?int;

	/**
	 * Set privacy policy page ID.
	 *
	 * @param int $page_id Page ID.
	 * @return bool True on success.
	 */
	public function setPrivacyPolicyPageId( int $page_id ): bool;

	/**
	 * Set cookie policy page ID.
	 *
	 * @param int $page_id Page ID.
	 * @return bool True on success.
	 */
	public function setCookiePolicyPageId( int $page_id ): bool;

	/**
	 * Get policy content.
	 *
	 * @param string $type Policy type ('privacy' or 'cookie').
	 * @param string $lang Language code.
	 * @return string Policy content.
	 */
	public function getContent( string $type, string $lang = 'en' ): string;

	/**
	 * Save policy content.
	 *
	 * @param string $type Policy type ('privacy' or 'cookie').
	 * @param string $content Policy content.
	 * @param string $lang Language code.
	 * @return bool True on success.
	 */
	public function saveContent( string $type, string $content, string $lang = 'en' ): bool;
}











<?php
/**
 * Policy repository implementation using WordPress options.
 *
 * @package FP\Privacy\Infrastructure\Options
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Infrastructure\Options;

use FP\Privacy\Domain\Policy\PolicyRepositoryInterface;

/**
 * Policy repository implementation using WordPress options and post meta.
 */
class PolicyRepository implements PolicyRepositoryInterface {
	/**
	 * Options repository.
	 *
	 * @var OptionsRepositoryInterface
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param OptionsRepositoryInterface $options Options repository.
	 */
	public function __construct( OptionsRepositoryInterface $options ) {
		$this->options = $options;
	}

	/**
	 * Get privacy policy page ID.
	 *
	 * @return int|null Page ID or null if not set.
	 */
	public function getPrivacyPolicyPageId(): ?int {
		$page_id = $this->options->get( 'privacy_policy_page_id' );
		return $page_id ? (int) $page_id : null;
	}

	/**
	 * Get cookie policy page ID.
	 *
	 * @return int|null Page ID or null if not set.
	 */
	public function getCookiePolicyPageId(): ?int {
		$page_id = $this->options->get( 'cookie_policy_page_id' );
		return $page_id ? (int) $page_id : null;
	}

	/**
	 * Set privacy policy page ID.
	 *
	 * @param int $page_id Page ID.
	 * @return bool True on success.
	 */
	public function setPrivacyPolicyPageId( int $page_id ): bool {
		return $this->options->set( 'privacy_policy_page_id', $page_id );
	}

	/**
	 * Set cookie policy page ID.
	 *
	 * @param int $page_id Page ID.
	 * @return bool True on success.
	 */
	public function setCookiePolicyPageId( int $page_id ): bool {
		return $this->options->set( 'cookie_policy_page_id', $page_id );
	}

	/**
	 * Get policy content.
	 *
	 * @param string $type Policy type ('privacy' or 'cookie').
	 * @param string $lang Language code.
	 * @return string Policy content.
	 */
	public function getContent( string $type, string $lang = 'en' ): string {
		$page_id = null;
		if ( 'privacy' === $type ) {
			$page_id = $this->getPrivacyPolicyPageId();
		} elseif ( 'cookie' === $type ) {
			$page_id = $this->getCookiePolicyPageId();
		}

		if ( ! $page_id ) {
			return '';
		}

		// Get post content.
		$post = get_post( $page_id );
		if ( ! $post ) {
			return '';
		}

		return $post->post_content ?? '';
	}

	/**
	 * Save policy content.
	 *
	 * @param string $type Policy type ('privacy' or 'cookie').
	 * @param string $content Policy content.
	 * @param string $lang Language code.
	 * @return bool True on success.
	 */
	public function saveContent( string $type, string $content, string $lang = 'en' ): bool {
		$page_id = null;
		if ( 'privacy' === $type ) {
			$page_id = $this->getPrivacyPolicyPageId();
		} elseif ( 'cookie' === $type ) {
			$page_id = $this->getCookiePolicyPageId();
		}

		if ( ! $page_id ) {
			return false;
		}

		// Update post content.
		$result = wp_update_post(
			array(
				'ID'           => $page_id,
				'post_content' => $content,
			),
			true
		);

		return ! is_wp_error( $result );
	}
}















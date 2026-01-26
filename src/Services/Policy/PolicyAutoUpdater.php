<?php
/**
 * Policy auto-updater service.
 *
 * @package FP\Privacy\Services\Policy
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Services\Policy;

use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Utils\Options;
use FP\Privacy\Utils\Logger;

/**
 * Handles automatic policy updates.
 */
class PolicyAutoUpdater {
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
	 * Constructor.
	 *
	 * @param Options         $options  Options handler.
	 * @param PolicyGenerator $generator Policy generator.
	 */
	public function __construct( Options $options, PolicyGenerator $generator ) {
		$this->options  = $options;
		$this->generator = $generator;
	}

	/**
	 * Check if auto-update is enabled.
	 *
	 * @return bool
	 */
	public function should_update(): bool {
		return (bool) $this->options->get( 'auto_update_policies', false );
	}

	/**
	 * Update all policies for all active languages.
	 *
	 * @param bool $force Force update even if pages were manually modified.
	 * @return array<string, array<string, bool>> Results by language and type.
	 */
	public function update_all_policies( $force = false ): array {
		if ( ! $this->should_update() ) {
			return array();
		}

		$languages = $this->options->get_languages();
		if ( empty( $languages ) ) {
			$languages = array( function_exists( '\\get_locale' ) ? (string) \get_locale() : 'en_US' );
		}

		$results = array();
		$timestamp = time();

		foreach ( $languages as $lang ) {
			$lang = $this->options->normalize_language( $lang );
			$results[ $lang ] = $this->update_policies_for_language( $lang, $force, $timestamp );
		}

		return $results;
	}

	/**
	 * Update policies for a specific language.
	 *
	 * @param string $lang      Language code.
	 * @param bool   $force     Force update.
	 * @param int    $timestamp Optional timestamp (uses current time if not provided).
	 * @return array<string, bool> Results by type.
	 */
	public function update_policies_for_language( string $lang, bool $force = false, ?int $timestamp = null ): array {
		$lang = $this->options->normalize_language( $lang );
		$timestamp = $timestamp ?? time();

		$results = array(
			'privacy' => false,
			'cookie'  => false,
		);

		// Get page IDs
		$privacy_id = $this->options->get_page_id( 'privacy_policy', $lang );
		$cookie_id  = $this->options->get_page_id( 'cookie_policy', $lang );

		if ( ! $privacy_id || ! $cookie_id ) {
			Logger::warning(
				'Policy pages not found for language',
				array(
					'lang'       => $lang,
					'privacy_id' => $privacy_id,
					'cookie_id'  => $cookie_id,
				)
			);
			return $results;
		}

		// Check if pages were manually modified (unless force is true)
		if ( ! $force && ! $this->can_update_pages( $privacy_id, $cookie_id ) ) {
			Logger::info(
				'Policy pages were manually modified, skipping auto-update',
				array(
					'lang'       => $lang,
					'privacy_id' => $privacy_id,
					'cookie_id'  => $cookie_id,
				)
			);
			return $results;
		}

		// Generate policy content
		try {
			$privacy_content = $this->generator->generate_privacy_policy( $lang );
			$cookie_content  = $this->generator->generate_cookie_policy( $lang );

			if ( empty( $privacy_content ) || empty( $cookie_content ) ) {
				Logger::warning(
					'Failed to generate policy content',
					array(
						'lang'            => $lang,
						'privacy_empty'   => empty( $privacy_content ),
						'cookie_empty'    => empty( $cookie_content ),
					)
				);
				return $results;
			}

			// Update privacy policy page
			$privacy_result = \wp_update_post(
				array(
					'ID'           => $privacy_id,
					'post_content' => $privacy_content,
					'post_status'  => 'publish',
				),
				true
			);

			if ( ! \is_wp_error( $privacy_result ) ) {
				$results['privacy'] = true;
				// Remove managed meta key to mark as auto-managed
				\delete_post_meta( $privacy_id, Options::PAGE_MANAGED_META_KEY );
			} else {
				Logger::error(
					'Failed to update privacy policy page',
					array(
						'lang'     => $lang,
						'page_id'  => $privacy_id,
						'error'    => $privacy_result->get_error_message(),
					)
				);
			}

			// Update cookie policy page
			$cookie_result = \wp_update_post(
				array(
					'ID'           => $cookie_id,
					'post_content' => $cookie_content,
					'post_status'  => 'publish',
				),
				true
			);

			if ( ! \is_wp_error( $cookie_result ) ) {
				$results['cookie'] = true;
				// Remove managed meta key to mark as auto-managed
				\delete_post_meta( $cookie_id, Options::PAGE_MANAGED_META_KEY );
			} else {
				Logger::error(
					'Failed to update cookie policy page',
					array(
						'lang'     => $lang,
						'page_id'  => $cookie_id,
						'error'    => $cookie_result->get_error_message(),
					)
				);
			}

			// Update snapshots with generation timestamp
			if ( $results['privacy'] || $results['cookie'] ) {
				$this->update_policy_snapshots( $lang, $timestamp, $results );
			}

		} catch ( \Throwable $e ) {
			Logger::error(
				'Exception while updating policies',
				array(
					'lang'  => $lang,
					'error' => $e->getMessage(),
				)
			);
		}

		return $results;
	}

	/**
	 * Check if pages can be updated (not manually modified).
	 *
	 * @param int $privacy_id Privacy policy page ID.
	 * @param int $cookie_id  Cookie policy page ID.
	 * @return bool
	 */
	private function can_update_pages( int $privacy_id, int $cookie_id ): bool {
		$privacy_post = \get_post( $privacy_id );
		$cookie_post  = \get_post( $cookie_id );

		if ( ! $privacy_post || ! $cookie_post ) {
			return false;
		}

		// Check if pages have managed meta key (were auto-generated)
		$privacy_managed = \get_post_meta( $privacy_id, Options::PAGE_MANAGED_META_KEY, true );
		$cookie_managed  = \get_post_meta( $cookie_id, Options::PAGE_MANAGED_META_KEY, true );

		// If both pages are managed, we can update
		if ( ! empty( $privacy_managed ) && ! empty( $cookie_managed ) ) {
			return true;
		}

		// If pages don't have managed meta key, check if content matches expected shortcode pattern
		$privacy_content = trim( (string) $privacy_post->post_content );
		$cookie_content  = trim( (string) $cookie_post->post_content );

		// Check if content starts with expected shortcode
		$privacy_expected = '[fp_privacy_policy lang="' . \esc_attr( $this->options->normalize_language( \get_locale() ) ) . '"]';
		$cookie_expected  = '[fp_cookie_policy lang="' . \esc_attr( $this->options->normalize_language( \get_locale() ) ) . '"]';

		// If content matches shortcode pattern, it's likely auto-generated
		if ( strpos( $privacy_content, '[fp_privacy_policy' ) === 0 && strpos( $cookie_content, '[fp_cookie_policy' ) === 0 ) {
			return true;
		}

		// Otherwise, assume pages were manually modified
		return false;
	}

	/**
	 * Update policy snapshots with generation timestamp.
	 *
	 * @param string $lang      Language code.
	 * @param int    $timestamp Generation timestamp.
	 * @param array<string, bool> $results Update results.
	 * @return void
	 */
	private function update_policy_snapshots( string $lang, int $timestamp, array $results ): void {
		$options = $this->options->all();
		$snapshots = isset( $options['snapshots'] ) && \is_array( $options['snapshots'] ) ? $options['snapshots'] : array();

		if ( ! isset( $snapshots['policies'] ) || ! \is_array( $snapshots['policies'] ) ) {
			$snapshots['policies'] = array(
				'privacy' => array(),
				'cookie'  => array(),
			);
		}

		if ( $results['privacy'] ) {
			$snapshots['policies']['privacy'][ $lang ] = array(
				'generated_at' => $timestamp,
			);
		}

		if ( $results['cookie'] ) {
			$snapshots['policies']['cookie'][ $lang ] = array(
				'generated_at' => $timestamp,
			);
		}

		$options['snapshots'] = $snapshots;
		$this->options->set( $options );
	}
}

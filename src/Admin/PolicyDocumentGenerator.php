<?php
/**
 * Policy document generator.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Utils\Options;

/**
 * Handles generation of policy documents.
 */
class PolicyDocumentGenerator {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Generator.
	 *
	 * @var PolicyGenerator
	 */
	private $generator;

	/**
	 * Constructor.
	 *
	 * @param Options         $options   Options handler.
	 * @param PolicyGenerator $generator Generator.
	 */
	public function __construct( Options $options, PolicyGenerator $generator ) {
		$this->options   = $options;
		$this->generator = $generator;
	}

	/**
	 * Ensure generated documents exist for each language.
	 *
	 * @param array<int, string> $languages Languages configured for the site.
	 *
	 * @return void
	 */
	public function maybe_generate_documents( array $languages ) {
		foreach ( $languages as $language ) {
			$language   = $this->options->normalize_language( $language );
			$privacy_id = $this->options->get_page_id( 'privacy_policy', $language );
			$cookie_id  = $this->options->get_page_id( 'cookie_policy', $language );

			if ( $privacy_id ) {
				$this->maybe_generate_document( $privacy_id, 'privacy', $language );
			}

			if ( $cookie_id ) {
				$this->maybe_generate_document( $cookie_id, 'cookie', $language );
			}
		}
	}

	/**
	 * Generate policy content when the stored page is empty or still using the shortcode placeholder.
	 *
	 * @param int    $post_id  Page identifier.
	 * @param string $type     Document type (privacy|cookie).
	 * @param string $language Normalized language code.
	 *
	 * @return void
	 */
	public function maybe_generate_document( $post_id, $type, $language ) {
		try {
			$post = \get_post( $post_id );

			if ( ! ( $post instanceof \WP_Post ) ) {
				return;
			}

			$current_content = trim( (string) $post->post_content );
			$shortcode       = 'privacy' === $type ? 'fp_privacy_policy' : 'fp_cookie_policy';
			$placeholder     = \sprintf( '[%1$s lang="%2$s"]', $shortcode, $language );

			if ( '' !== $current_content && $current_content !== $placeholder ) {
				return;
			}

			$generated = 'privacy' === $type
				? $this->generator->generate_privacy_policy( $language )
				: $this->generator->generate_cookie_policy( $language );

			// Only update if we have valid content.
			if ( '' === $generated ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( sprintf( 'FP Privacy: Generated content is empty for %s policy in %s', $type, $language ) );
				}
				return;
			}

			$updated = \wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_content' => $generated,
				),
				true
			);

			if ( ! \is_wp_error( $updated ) ) {
				\delete_post_meta( $post->ID, Options::PAGE_MANAGED_META_KEY );
			} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'FP Privacy: Error updating %s policy page %d: %s', $type, $post_id, $updated->get_error_message() ) );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'FP Privacy: Error generating %s document for page %d: %s', $type, $post_id, $e->getMessage() ) );
			}
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'FP Privacy: Error generating %s document for page %d: %s', $type, $post_id, $e->getMessage() ) );
			}
		}
	}
}
















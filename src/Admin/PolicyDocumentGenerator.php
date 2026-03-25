<?php
/**
 * Policy document generator.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

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
	 * Constructor.
	 *
	 * @param Options $options Options handler.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Contenuto da salvare nella pagina dedicata: solo shortcode, così tabelle e servizi
	 * riflettono il detector e le opzioni ad ogni visualizzazione frontend.
	 *
	 * @param string $type     `privacy` o `cookie`.
	 * @param string $language Codice lingua normalizzato (es. it_IT).
	 *
	 * @return string Shortcode completo, es. `[fp_privacy_policy lang="it_IT"]`.
	 */
	public function get_page_placeholder( string $type, string $language ): string {
		$language  = $this->options->normalize_language( $language );
		$shortcode = ( 'privacy' === $type ) ? 'fp_privacy_policy' : 'fp_cookie_policy';

		return \sprintf( '[%1$s lang="%2$s"]', $shortcode, \esc_attr( $language ) );
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
	 * Se la pagina è vuota, imposta lo shortcode dedicato (mai HTML statico: le tabelle restano aggiornate col detector).
	 * Se contiene già lo shortcode corretto, non modifica nulla. HTML o contenuto personalizzato restano intatti.
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
			$placeholder     = $this->get_page_placeholder( (string) $type, (string) $language );

			if ( $current_content === $placeholder ) {
				return;
			}

			if ( '' !== $current_content ) {
				return;
			}

			$updated = \wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_content' => $placeholder,
				),
				true
			);

			if ( ! \is_wp_error( $updated ) ) {
				\update_post_meta( $post->ID, Options::PAGE_MANAGED_META_KEY, \hash( 'sha256', $placeholder ) );
			} elseif ( \defined( 'WP_DEBUG' ) && \WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				\error_log( \sprintf( 'FP Privacy: Error seeding %s policy page %d: %s', $type, $post_id, $updated->get_error_message() ) );
			}
		} catch ( \Throwable $e ) {
			if ( \defined( 'WP_DEBUG' ) && \WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				\error_log( \sprintf( 'FP Privacy: Error preparing %s document for page %d: %s', $type, $post_id, $e->getMessage() ) );
			}
		}
	}
}
















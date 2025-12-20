<?php
/**
 * Policy page generator.
 *
 * @package FP\Privacy\CLI
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\CLI;

use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Utils\Options;
use WP_CLI;

/**
 * Handles generation and update of policy pages.
 */
class PolicyPageGenerator {
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
	 * Generate and update policy pages for a language.
	 *
	 * @param string $language Language code.
	 * @param bool   $dry_run  Dry run mode.
	 *
	 * @return array{privacy: string, cookie: string}|null
	 */
	public function generate_for_language( $language, $dry_run = false ) {
		$language = $this->options->normalize_language( $language );

		// Ottieni gli ID delle pagine
		$privacy_id = $this->options->get_page_id( 'privacy_policy', $language );
		$cookie_id  = $this->options->get_page_id( 'cookie_policy', $language );

		if ( ! $privacy_id || ! $cookie_id ) {
			return null;
		}

		WP_CLI::log( '' );
		WP_CLI::log( sprintf( '🌐 Elaborazione lingua: %s', $language ) );
		WP_CLI::log( sprintf( '  Privacy Policy ID: %d', $privacy_id ) );
		WP_CLI::log( sprintf( '  Cookie Policy ID: %d', $cookie_id ) );

		// Genera il contenuto
		WP_CLI::log( '  📝 Generazione Privacy Policy...' );
		$privacy = $this->generator->generate_privacy_policy( $language );

		WP_CLI::log( '  📝 Generazione Cookie Policy...' );
		$cookie = $this->generator->generate_cookie_policy( $language );

		WP_CLI::log( sprintf( '  📊 Privacy Policy: %d caratteri', strlen( $privacy ) ) );
		WP_CLI::log( sprintf( '  📊 Cookie Policy: %d caratteri', strlen( $cookie ) ) );

		// Aggiorna le pagine
		if ( ! $dry_run ) {
			WP_CLI::log( '  💾 Aggiornamento pagine...' );

			$privacy_result = \wp_update_post(
				array(
					'ID'           => $privacy_id,
					'post_content' => $privacy,
					'post_status'  => 'publish',
				)
			);

			$cookie_result = \wp_update_post(
				array(
					'ID'           => $cookie_id,
					'post_content' => $cookie,
					'post_status'  => 'publish',
				)
			);

			if ( ! \is_wp_error( $privacy_result ) && ! \is_wp_error( $cookie_result ) ) {
				\delete_post_meta( $privacy_id, Options::PAGE_MANAGED_META_KEY );
				\delete_post_meta( $cookie_id, Options::PAGE_MANAGED_META_KEY );
				WP_CLI::success( '  Pagine aggiornate con successo!' );
			} else {
				WP_CLI::error( '  Errore nell\'aggiornamento delle pagine' );
			}
		}

		return array(
			'privacy' => $privacy,
			'cookie'  => $cookie,
		);
	}
}


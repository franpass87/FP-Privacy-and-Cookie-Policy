<?php
/**
 * Policy pages orchestrator.
 *
 * @package FP\Privacy\CLI
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\CLI\Commands;

use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Utils\Options;
use WP_CLI;

/**
 * Handles orchestration of policy pages generation process.
 */
class PolicyPagesOrchestrator {
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
	 * Page generator.
	 *
	 * @var PolicyPageGenerator
	 */
	private $page_generator;

	/**
	 * Page validator.
	 *
	 * @var PolicyPageValidator
	 */
	private $page_validator;

	/**
	 * Snapshot manager.
	 *
	 * @var PolicySnapshotManager
	 */
	private $snapshot_manager;

	/**
	 * Constructor.
	 *
	 * @param Options               $options         Options handler.
	 * @param DetectorRegistry      $detector        Detector registry.
	 * @param PolicyPageGenerator   $page_generator  Page generator.
	 * @param PolicyPageValidator   $page_validator  Page validator.
	 * @param PolicySnapshotManager $snapshot_manager Snapshot manager.
	 */
	public function __construct( Options $options, DetectorRegistry $detector, PolicyPageGenerator $page_generator, PolicyPageValidator $page_validator, PolicySnapshotManager $snapshot_manager ) {
		$this->options         = $options;
		$this->detector        = $detector;
		$this->page_generator  = $page_generator;
		$this->page_validator  = $page_validator;
		$this->snapshot_manager = $snapshot_manager;
	}

	/**
	 * Determine languages to process.
	 *
	 * @param string|null $single_lang   Single language code.
	 * @param bool         $all_languages Process all languages.
	 *
	 * @return array<int, string>
	 */
	public function determine_languages( $single_lang, $all_languages ) {
		if ( $single_lang ) {
			$languages = array( $single_lang );
			WP_CLI::log( sprintf( '📝 Lingua selezionata: %s', $single_lang ) );
		} elseif ( $all_languages ) {
			$languages = $this->options->get_languages();
			if ( empty( $languages ) ) {
				$languages = array( \get_locale() );
			}
			WP_CLI::log( sprintf( '🌍 Generazione per tutte le lingue: %s', implode( ', ', $languages ) ) );
		} else {
			$languages = array( \get_locale() );
			WP_CLI::log( sprintf( '📝 Lingua predefinita: %s', $languages[0] ) );
		}

		return $languages;
	}

	/**
	 * Detect services and log results.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function detect_and_log_services() {
		WP_CLI::log( '🔍 Rilevamento servizi integrati...' );
		$services       = $this->detector->detect_services( true );
		$detected_count = 0;
		foreach ( $services as $service ) {
			if ( ! empty( $service['detected'] ) ) {
				$detected_count++;
				WP_CLI::log( sprintf( '  ✓ %s [%s]', $service['name'], $service['category'] ) );
			}
		}
		WP_CLI::success( sprintf( 'Rilevati %d servizi', $detected_count ) );

		return array( $services, $detected_count );
	}

	/**
	 * Generate pages for all languages.
	 *
	 * @param array<int, string> $languages Languages to process.
	 * @param bool               $force     Force update.
	 * @param bool               $dry_run   Dry run mode.
	 *
	 * @return array{privacy: array<string, string>, cookie: array<string, string>}
	 */
	public function generate_for_all_languages( array $languages, $force, $dry_run ) {
		$generated_privacy = array();
		$generated_cookie  = array();

		foreach ( $languages as $language ) {
			$language = $this->options->normalize_language( $language );

			// Ottieni gli ID delle pagine
			$privacy_id = $this->options->get_page_id( 'privacy_policy', $language );
			$cookie_id  = $this->options->get_page_id( 'cookie_policy', $language );

			if ( ! $privacy_id || ! $cookie_id ) {
				WP_CLI::warning( sprintf( 'Pagine non trovate per la lingua %s', $language ) );
				continue;
			}

			// Verifica se le pagine sono state modificate manualmente
			if ( ! $this->page_validator->can_update_pages( $privacy_id, $cookie_id, $force ) ) {
				continue;
			}

			// Genera e aggiorna le pagine
			$result = $this->page_generator->generate_for_language( $language, $dry_run );

			if ( $result ) {
				$generated_privacy[ $language ] = $result['privacy'];
				$generated_cookie[ $language ]  = $result['cookie'];
			}
		}

		return array(
			'privacy' => $generated_privacy,
			'cookie'  => $generated_cookie,
		);
	}

	/**
	 * Render final summary.
	 *
	 * @param array<int, string> $languages      Languages processed.
	 * @param int                $detected_count Detected services count.
	 * @param int                $timestamp      Timestamp.
	 * @param bool               $dry_run        Dry run mode.
	 *
	 * @return void
	 */
	public function render_summary( array $languages, $detected_count, $timestamp, $dry_run ) {
		WP_CLI::log( '' );
		WP_CLI::log( '═══════════════════════════════════════════' );
		WP_CLI::success( '✅ Generazione completata!' );
		WP_CLI::log( sprintf( '📊 Lingue processate: %d', count( $languages ) ) );
		WP_CLI::log( sprintf( '🔌 Servizi rilevati: %d', $detected_count ) );
		WP_CLI::log( sprintf( '⏰ Timestamp: %s', gmdate( 'Y-m-d H:i:s', $timestamp ) ) );

		if ( $dry_run ) {
			WP_CLI::log( '' );
			WP_CLI::warning( 'Modalità DRY-RUN: nessuna modifica è stata salvata' );
			WP_CLI::log( 'Rimuovi --dry-run per applicare le modifiche' );
		}
	}
}
















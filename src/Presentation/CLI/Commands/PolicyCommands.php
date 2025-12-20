<?php
/**
 * WP-CLI commands for policy management.
 *
 * @package FP\Privacy\CLI
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\CLI\Commands;

use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Utils\Options;
use WP_CLI;

/**
 * Handles WP-CLI commands related to policy management.
 */
class PolicyCommands {
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
	 * Orchestrator.
	 *
	 * @var PolicyPagesOrchestrator
	 */
	private $orchestrator;

	/**
	 * Constructor.
	 *
	 * @param Options          $options  Options handler.
	 * @param PolicyGenerator  $generator Generator.
	 * @param DetectorRegistry $detector  Detector registry.
	 */
	public function __construct( Options $options, PolicyGenerator $generator, DetectorRegistry $detector ) {
		$this->options          = $options;
		$this->generator       = $generator;
		$this->detector        = $detector;
		$this->page_generator  = new PolicyPageGenerator( $options, $generator );
		$this->page_validator  = new PolicyPageValidator( $options );
		$this->snapshot_manager = new PolicySnapshotManager( $options );
		$this->orchestrator     = new PolicyPagesOrchestrator( $options, $detector, $this->page_generator, $this->page_validator, $this->snapshot_manager );
	}

	/**
	 * Detect services.
	 *
	 * @return void
	 */
	public function detect() {
		$services = $this->detector->detect_services();
		foreach ( $services as $service ) {
			WP_CLI::log( sprintf( '%s [%s] - %s', $service['name'], $service['category'], $service['detected'] ? 'detected' : 'not detected' ) );
		}
	}

	/**
	 * Regenerate policy documents.
	 *
	 * ## OPTIONS
	 *
	 * [--lang=<lang>]
	 * : Language to use.
	 *
	 * [--bump-revision]
	 * : Increment consent revision after regeneration.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function regenerate( $args, $assoc_args ) {
		$lang    = isset( $assoc_args['lang'] ) ? $assoc_args['lang'] : \get_locale();
		$privacy = $this->generator->generate_privacy_policy( $lang );
		$cookie  = $this->generator->generate_cookie_policy( $lang );

		WP_CLI::log( '--- Privacy Policy ---' );
		WP_CLI::log( $privacy );
		WP_CLI::log( '--- Cookie Policy ---' );
		WP_CLI::log( $cookie );

		if ( isset( $assoc_args['bump-revision'] ) ) {
			$this->options->bump_revision();
			$this->options->set( $this->options->all() );
			WP_CLI::success( 'Consent revision bumped.' );
		}
	}

	/**
	 * Genera automaticamente le pagine Cookie Policy e Privacy Policy.
	 *
	 * ## DESCRIZIONE
	 *
	 * Questo comando:
	 * - Crea automaticamente le pagine Privacy Policy e Cookie Policy se non esistono
	 * - Rileva i servizi integrati sul sito
	 * - Genera il contenuto delle policy basato sui servizi rilevati
	 * - Aggiorna le pagine WordPress con il contenuto generato
	 * - Salva uno snapshot dei servizi per riferimento futuro
	 *
	 * ## OPZIONI
	 *
	 * [--force]
	 * : Forza la rigenerazione anche se le pagine sono state modificate manualmente.
	 *
	 * [--all-languages]
	 * : Genera le policy per tutte le lingue configurate.
	 *
	 * [--lang=<lang>]
	 * : Genera le policy solo per la lingua specificata (es. it_IT, en_US).
	 *
	 * [--bump-revision]
	 * : Incrementa la revisione del consenso dopo la generazione.
	 *
	 * [--dry-run]
	 * : Mostra solo cosa verrebbe fatto senza modificare nulla.
	 *
	 * ## ESEMPI
	 *
	 *     # Genera le pagine per tutte le lingue
	 *     wp fp-privacy generate-pages --all-languages
	 *
	 *     # Genera solo per l'italiano
	 *     wp fp-privacy generate-pages --lang=it_IT
	 *
	 *     # Forza la rigenerazione e incrementa la revisione
	 *     wp fp-privacy generate-pages --force --bump-revision
	 *
	 *     # Verifica cosa verrebbe fatto senza modificare
	 *     wp fp-privacy generate-pages --dry-run
	 *
	 * @param array $args       Argomenti posizionali.
	 * @param array $assoc_args Argomenti associativi.
	 * @return void
	 */
	public function generate_pages( $args, $assoc_args ) {
		$dry_run       = isset( $assoc_args['dry-run'] );
		$force         = isset( $assoc_args['force'] );
		$all_languages = isset( $assoc_args['all-languages'] );
		$single_lang   = isset( $assoc_args['lang'] ) ? $assoc_args['lang'] : null;
		$bump_revision = isset( $assoc_args['bump-revision'] );

		WP_CLI::log( '🚀 Avvio generazione automatica delle policy...' );

		// Determina le lingue da processare
		$languages = $this->orchestrator->determine_languages( $single_lang, $all_languages );

		if ( $dry_run ) {
			WP_CLI::warning( '🔍 Modalità DRY-RUN: nessuna modifica verrà salvata' );
		}

		// Assicura che le pagine esistano
		if ( ! $dry_run ) {
			WP_CLI::log( '📄 Verifica esistenza delle pagine...' );
			$this->options->ensure_pages_exist();
			WP_CLI::success( 'Pagine verificate/create' );
		}

		// Rileva i servizi integrati
		list( $services, $detected_count ) = $this->orchestrator->detect_and_log_services();

		// Genera e salva le policy per ogni lingua
		$timestamp = time();
		$generated = $this->orchestrator->generate_for_all_languages( $languages, $force, $dry_run );

		// Salva lo snapshot dei servizi
		if ( ! $dry_run ) {
			$this->snapshot_manager->save_snapshot( $services, $generated['privacy'], $generated['cookie'], $timestamp );
		}

		// Incrementa la revisione se richiesto
		if ( $bump_revision && ! $dry_run ) {
			WP_CLI::log( '' );
			WP_CLI::log( '🔄 Incremento revisione consenso...' );
			$this->options->bump_revision();
			$payload = $this->options->all();
			$this->options->set( $payload );
			WP_CLI::success( sprintf( 'Revisione aggiornata a: %s', $this->options->get( 'consent_revision', '1.0' ) ) );
		}

		// Riepilogo finale
		$this->orchestrator->render_summary( $languages, $detected_count, $timestamp, $dry_run );
	}

	/**
	 * Force update banner texts translations for all active languages.
	 *
	 * ## EXAMPLES
	 *
	 *     wp fp-privacy update-texts
	 *
	 * @return void
	 */
	public function update_texts() {
		WP_CLI::log( '🔄 Aggiornamento testi del banner per tutte le lingue attive...' );

		try {
			$this->options->force_update_banner_texts_translations();

			$languages = $this->options->get_languages();
			WP_CLI::log( sprintf( '📝 Lingue aggiornate: %s', implode( ', ', $languages ) ) );

			WP_CLI::success( '✅ Testi del banner aggiornati con successo!' );
			WP_CLI::log( 'I testi ora dovrebbero essere correttamente tradotti per tutte le lingue attive.' );

		} catch ( \Exception $e ) {
			WP_CLI::error( sprintf( 'Errore durante l\'aggiornamento: %s', $e->getMessage() ) );
		}
	}
}



<?php
/**
 * WP-CLI commands.
 *
 * @package FP\Privacy\CLI
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\CLI;

use FP\Privacy\Consent\Cleanup;
use FP\Privacy\Consent\LogModel;
use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Utils\Options;
use WP_CLI_Command;

/**
 * Implements fp-privacy CLI commands.
 * 
 * This class acts as a facade, delegating to specialized command classes
 * while maintaining backward compatibility with existing WP-CLI command structure.
 */
class Commands extends WP_CLI_Command {
	/**
	 * Consent commands handler.
	 *
	 * @var ConsentCommands
	 */
	private $consent_commands;

	/**
	 * Settings commands handler.
	 *
	 * @var SettingsCommands
	 */
	private $settings_commands;

	/**
	 * Policy commands handler.
	 *
	 * @var PolicyCommands
	 */
	private $policy_commands;

	/**
	 * Constructor.
	 *
	 * @param LogModel        $log_model Log model.
	 * @param Options         $options   Options.
	 * @param PolicyGenerator $generator Generator.
	 * @param DetectorRegistry $detector Detector.
	 * @param Cleanup         $cleanup   Cleanup handler.
	 */
	public function __construct( LogModel $log_model, Options $options, PolicyGenerator $generator, DetectorRegistry $detector, Cleanup $cleanup ) {
		$this->consent_commands  = new ConsentCommands( $log_model, $cleanup );
		$this->settings_commands = new SettingsCommands( $options );
		$this->policy_commands   = new PolicyCommands( $options, $generator, $detector );
	}

	/**
	 * Display status information.
	 *
	 * @return void
	 */
	public function status() {
		$this->consent_commands->status();
	}

	/**
	 * Recreate database table.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Drop and recreate the table.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function recreate( $args, $assoc_args ) {
		$this->consent_commands->recreate( $args, $assoc_args );
	}

	/**
	 * Run cleanup immediately.
	 *
	 * @return void
	 */
	public function cleanup() {
		$this->consent_commands->cleanup();
	}

	/**
	 * Export CSV.
	 *
	 * ## OPTIONS
	 *
	 * --file=<path>
	 * : Destination file path.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function export( $args, $assoc_args ) {
		$this->consent_commands->export( $args, $assoc_args );
	}

	/**
	 * Export settings to JSON.
	 *
	 * ## OPTIONS
	 *
	 * --file=<path>
	 * : Destination file path.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function settings_export( $args, $assoc_args ) {
		$this->settings_commands->settings_export( $args, $assoc_args );
	}

	/**
	 * Import settings from JSON.
	 *
	 * ## OPTIONS
	 *
	 * --file=<path>
	 * : Source file path.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function settings_import( $args, $assoc_args ) {
		$this->settings_commands->settings_import( $args, $assoc_args );
	}

	/**
	 * Detect services.
	 *
	 * @return void
	 */
	public function detect() {
		$this->policy_commands->detect();
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
		$this->policy_commands->regenerate( $args, $assoc_args );
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
		$this->policy_commands->generate_pages( $args, $assoc_args );
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
		$this->policy_commands->update_texts();
	}
}

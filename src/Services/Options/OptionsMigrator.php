<?php
/**
 * Options migrator for handling option structure changes.
 *
 * @package FP\Privacy\Services\Options
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Services\Options;

/**
 * Handles migration of options between versions.
 */
class OptionsMigrator {
	/**
	 * Options service.
	 *
	 * @var OptionsInterface
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param OptionsInterface $options Options service.
	 */
	public function __construct( OptionsInterface $options ) {
		$this->options = $options;
	}

	/**
	 * Run migrations if needed.
	 *
	 * @param string $current_version Current plugin version.
	 * @return void
	 */
	public function migrate( string $current_version ): void {
		$last_version = $this->options->get( 'version', '0.0.0' );

		if ( version_compare( $last_version, $current_version, '<' ) ) {
			// Run migrations based on version.
			$this->runMigrations( $last_version, $current_version );
			$this->options->set( 'version', $current_version );
		}
	}

	/**
	 * Run migrations between versions.
	 *
	 * @param string $from_version From version.
	 * @param string $to_version To version.
	 * @return void
	 */
	private function runMigrations( string $from_version, string $to_version ): void {
		// Add migration logic here as needed.
		// Example:
		// if ( version_compare( $from_version, '0.2.0', '<' ) ) {
		//     $this->migrateTo020();
		// }
	}
}











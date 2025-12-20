<?php
/**
 * Options adapter - bridges old Options singleton to OptionsInterface.
 *
 * @package FP\Privacy\Infrastructure\Options
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Infrastructure\Options;

use FP\Privacy\Utils\Options as LegacyOptions;
use FP\Privacy\Services\Options\OptionsInterface;

/**
 * Adapter that wraps legacy Options singleton to implement OptionsRepositoryInterface and OptionsInterface.
 * This allows gradual migration from singleton to service container.
 * 
 * @deprecated This adapter is temporary during migration. Use WpOptionsAdapter instead.
 */
class OptionsAdapter implements OptionsRepositoryInterface, OptionsInterface {
	/**
	 * Legacy options instance.
	 *
	 * @var LegacyOptions
	 */
	private $legacy_options;

	/**
	 * Constructor.
	 *
	 * @param LegacyOptions|null $legacy_options Legacy options instance (uses singleton if null).
	 */
	public function __construct( ?LegacyOptions $legacy_options = null ) {
		// Try to get from container first, then fallback to singleton.
		if ( $legacy_options ) {
			$this->legacy_options = $legacy_options;
		} elseif ( class_exists( '\\FP\\Privacy\\Core\\Kernel' ) ) {
			try {
				$kernel = \FP\Privacy\Core\Kernel::make();
				$container = $kernel->getContainer();
				if ( $container->has( LegacyOptions::class ) ) {
					$this->legacy_options = $container->get( LegacyOptions::class );
				} else {
					$this->legacy_options = LegacyOptions::instance();
				}
			} catch ( \Exception $e ) {
				$this->legacy_options = LegacyOptions::instance();
			}
		} else {
			$this->legacy_options = LegacyOptions::instance();
		}
	}

	/**
	 * Get an option value.
	 *
	 * @param string $key Option key.
	 * @param mixed  $default Default value if not found.
	 * @return mixed Option value or default.
	 */
	public function get( string $key, $default = null ) {
		return $this->legacy_options->get( $key, $default );
	}

	/**
	 * Set an option value.
	 *
	 * @param string $key Option key.
	 * @param mixed  $value Value to store.
	 * @return bool True on success.
	 */
	public function set( string $key, $value ): bool {
		// Legacy Options uses set() with array, so we need to get all, update, and set.
		$all = $this->legacy_options->all();
		$all[ $key ] = $value;
		$this->legacy_options->set( $all );
		return true;
	}

	/**
	 * Delete an option.
	 *
	 * @param string $key Option key.
	 * @return bool True on success.
	 */
	public function delete( string $key ): bool {
		// Legacy Options doesn't have delete, so we set to null/empty.
		$all = $this->legacy_options->all();
		unset( $all[ $key ] );
		$this->legacy_options->set( $all );
		return true;
	}

	/**
	 * Get all options.
	 *
	 * @return array<string, mixed> All options.
	 */
	public function all(): array {
		return $this->legacy_options->all();
	}

	/**
	 * Get the legacy options instance (for methods not in interface).
	 *
	 * @return LegacyOptions Legacy options instance.
	 */
	public function getLegacy(): LegacyOptions {
		return $this->legacy_options;
	}
}







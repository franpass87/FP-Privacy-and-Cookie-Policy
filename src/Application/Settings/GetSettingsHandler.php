<?php
/**
 * Get settings query handler.
 *
 * @package FP\Privacy\Application\Settings
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Application\Settings;

use FP\Privacy\Infrastructure\Options\OptionsRepositoryInterface;

/**
 * Handler for retrieving plugin settings.
 */
class GetSettingsHandler {
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
	 * Get all settings.
	 *
	 * @return array<string, mixed> All settings.
	 */
	public function all(): array {
		return $this->options->all();
	}

	/**
	 * Get a specific setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed Setting value.
	 */
	public function get( string $key, $default = null ) {
		return $this->options->get( $key, $default );
	}
}















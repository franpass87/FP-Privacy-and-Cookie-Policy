<?php
/**
 * WordPress options adapter - implements OptionsRepositoryInterface.
 *
 * @package FP\Privacy\Infrastructure\Options
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Infrastructure\Options;

/**
 * WordPress options adapter that wraps get_option/update_option.
 * This is the new implementation that should replace direct WordPress calls.
 */
class WpOptionsAdapter implements OptionsRepositoryInterface {
	/**
	 * Option key prefix.
	 *
	 * @var string
	 */
	private $prefix;

	/**
	 * Constructor.
	 *
	 * @param string $prefix Option key prefix (default: 'fp_privacy_').
	 */
	public function __construct( string $prefix = 'fp_privacy_' ) {
		$this->prefix = $prefix;
	}

	/**
	 * Get an option value.
	 *
	 * @param string $key Option key.
	 * @param mixed  $default Default value if not found.
	 * @return mixed Option value or default.
	 */
	public function get( string $key, $default = null ) {
		$full_key = $this->prefix . $key;
		return get_option( $full_key, $default );
	}

	/**
	 * Set an option value.
	 *
	 * @param string $key Option key.
	 * @param mixed  $value Value to store.
	 * @return bool True on success.
	 */
	public function set( string $key, $value ): bool {
		$full_key = $this->prefix . $key;
		return update_option( $full_key, $value );
	}

	/**
	 * Delete an option.
	 *
	 * @param string $key Option key.
	 * @return bool True on success.
	 */
	public function delete( string $key ): bool {
		$full_key = $this->prefix . $key;
		return delete_option( $full_key );
	}

	/**
	 * Get all options.
	 *
	 * @return array<string, mixed> All options.
	 */
	public function all(): array {
		// For now, return the main options array.
		// This will be improved when we migrate from the singleton.
		$main_options = get_option( 'fp_privacy_options', array() );
		return is_array( $main_options ) ? $main_options : array();
	}
}














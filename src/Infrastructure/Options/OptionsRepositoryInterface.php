<?php
/**
 * Options repository interface.
 *
 * @package FP\Privacy\Infrastructure\Options
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Infrastructure\Options;

/**
 * Interface for options repository implementations.
 * This is the new interface that replaces direct WordPress option calls.
 */
interface OptionsRepositoryInterface {
	/**
	 * Get an option value.
	 *
	 * @param string $key Option key.
	 * @param mixed  $default Default value if not found.
	 * @return mixed Option value or default.
	 */
	public function get( string $key, $default = null );

	/**
	 * Set an option value.
	 *
	 * @param string $key Option key.
	 * @param mixed  $value Value to store.
	 * @return bool True on success.
	 */
	public function set( string $key, $value ): bool;

	/**
	 * Delete an option.
	 *
	 * @param string $key Option key.
	 * @return bool True on success.
	 */
	public function delete( string $key ): bool;

	/**
	 * Get all options.
	 *
	 * @return array<string, mixed> All options.
	 */
	public function all(): array;
}














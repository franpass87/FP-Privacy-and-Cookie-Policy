<?php
/**
 * Service container interface.
 *
 * @package FP\Privacy\Core
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Core;

/**
 * Service container interface for dependency injection.
 */
interface ContainerInterface {
	/**
	 * Bind an abstract to a concrete implementation.
	 *
	 * @param string $abstract Abstract identifier (class name or interface).
	 * @param callable|string|null $concrete Concrete implementation or factory.
	 * @return void
	 */
	public function bind( string $abstract, $concrete = null ): void;

	/**
	 * Bind a singleton (shared instance).
	 *
	 * @param string $abstract Abstract identifier.
	 * @param callable|string|null $concrete Concrete implementation or factory.
	 * @return void
	 */
	public function singleton( string $abstract, $concrete = null ): void;

	/**
	 * Resolve and return an instance.
	 *
	 * @param string $abstract Abstract identifier.
	 * @param array<string, mixed> $parameters Optional parameters for constructor.
	 * @return object Resolved instance.
	 * @throws \Exception If resolution fails.
	 */
	public function make( string $abstract, array $parameters = [] ): object;

	/**
	 * Check if abstract is bound.
	 *
	 * @param string $abstract Abstract identifier.
	 * @return bool True if bound.
	 */
	public function has( string $abstract ): bool;

	/**
	 * Create an alias for an abstract.
	 *
	 * @param string $abstract Abstract identifier.
	 * @param string $alias Alias name.
	 * @return void
	 */
	public function alias( string $abstract, string $alias ): void;

	/**
	 * Get a resolved instance (alias for make).
	 *
	 * @param string $abstract Abstract identifier.
	 * @return object Resolved instance.
	 */
	public function get( string $abstract ): object;
}








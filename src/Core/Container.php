<?php
/**
 * Service container implementation.
 *
 * @package FP\Privacy\Core
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Core;

use ReflectionClass;
use ReflectionParameter;

/**
 * Simple service container with dependency injection.
 */
class Container implements ContainerInterface {
	/**
	 * Bound services.
	 *
	 * @var array<string, mixed>
	 */
	private $bindings = array();

	/**
	 * Singleton instances.
	 *
	 * @var array<string, object>
	 */
	private $instances = array();

	/**
	 * Registered singletons (tracks which abstracts are singletons).
	 *
	 * @var array<string, bool>
	 */
	private $singletons = array();

	/**
	 * Aliases.
	 *
	 * @var array<string, string>
	 */
	private $aliases = array();

	/**
	 * Currently resolving abstracts (prevents circular dependencies).
	 *
	 * @var array<string, bool>
	 */
	private $resolving = array();

	/**
	 * Bind an abstract to a concrete implementation.
	 *
	 * @param string $abstract Abstract identifier (class name or interface).
	 * @param callable|string|null $concrete Concrete implementation or factory.
	 * @return void
	 */
	public function bind( string $abstract, $concrete = null ): void {
		$this->bindings[ $abstract ] = $concrete ?? $abstract;
	}

	/**
	 * Bind a singleton (shared instance).
	 *
	 * @param string $abstract Abstract identifier.
	 * @param callable|string|null $concrete Concrete implementation or factory.
	 * @return void
	 */
	public function singleton( string $abstract, $concrete = null ): void {
		$this->bind( $abstract, $concrete );
		$this->singletons[ $abstract ] = true;
	}

	/**
	 * Resolve and return an instance.
	 *
	 * @param string $abstract Abstract identifier.
	 * @param array<string, mixed> $parameters Optional parameters for constructor.
	 * @return object Resolved instance.
	 * @throws \Exception If resolution fails.
	 */
	public function make( string $abstract, array $parameters = [] ): object {
		// Resolve alias if present.
		$abstract = $this->resolveAlias( $abstract );

		// Check if already resolved as singleton.
		if ( isset( $this->instances[ $abstract ] ) ) {
			return $this->instances[ $abstract ];
		}

		// Check for circular dependency.
		if ( isset( $this->resolving[ $abstract ] ) && $this->resolving[ $abstract ] ) {
			throw new \Exception( "Circular dependency detected while resolving: {$abstract}" );
		}

		// Mark as resolving.
		$this->resolving[ $abstract ] = true;

		try {
			// Get concrete implementation.
			$concrete = $this->getConcrete( $abstract );

			// If concrete is a callable factory, invoke it.
			if ( is_callable( $concrete ) && ! is_string( $concrete ) ) {
				$instance = call_user_func( $concrete, $this );
			} else {
				// Resolve class.
				$instance = $this->build( $concrete, $parameters );
			}

			// Store as singleton if bound as such.
			if ( isset( $this->singletons[ $abstract ] ) && $this->singletons[ $abstract ] ) {
				$this->instances[ $abstract ] = $instance;
			}

			return $instance;
		} finally {
			// Unmark as resolving.
			unset( $this->resolving[ $abstract ] );
		}
	}

	/**
	 * Get a resolved instance (alias for make).
	 *
	 * @param string $abstract Abstract identifier.
	 * @return object Resolved instance.
	 */
	public function get( string $abstract ): object {
		return $this->make( $abstract );
	}

	/**
	 * Check if abstract is bound.
	 *
	 * @param string $abstract Abstract identifier.
	 * @return bool True if bound.
	 */
	public function has( string $abstract ): bool {
		$abstract = $this->resolveAlias( $abstract );
		return isset( $this->bindings[ $abstract ] ) || isset( $this->instances[ $abstract ] );
	}

	/**
	 * Create an alias for an abstract.
	 *
	 * @param string $abstract Abstract identifier.
	 * @param string $alias Alias name.
	 * @return void
	 */
	public function alias( string $abstract, string $alias ): void {
		$this->aliases[ $alias ] = $abstract;
	}

	/**
	 * Resolve alias to actual abstract.
	 *
	 * @param string $abstract Abstract identifier.
	 * @return string Resolved abstract.
	 */
	private function resolveAlias( string $abstract ): string {
		return $this->aliases[ $abstract ] ?? $abstract;
	}

	/**
	 * Get concrete implementation for abstract.
	 *
	 * @param string $abstract Abstract identifier.
	 * @return callable|string Concrete implementation.
	 */
	private function getConcrete( string $abstract ) {
		if ( isset( $this->bindings[ $abstract ] ) ) {
			return $this->bindings[ $abstract ];
		}

		// If not bound, assume abstract is the concrete class.
		return $abstract;
	}


	/**
	 * Build an instance of a class.
	 *
	 * @param string $class Class name.
	 * @param array<string, mixed> $parameters Optional parameters.
	 * @return object Instance.
	 * @throws \Exception If class cannot be instantiated.
	 */
	private function build( string $class, array $parameters = [] ): object {
		if ( ! class_exists( $class ) ) {
			throw new \Exception( "Class {$class} does not exist." );
		}

		$reflector = new ReflectionClass( $class );

		if ( ! $reflector->isInstantiable() ) {
			throw new \Exception( "Class {$class} is not instantiable." );
		}

		$constructor = $reflector->getConstructor();

		// If no constructor, instantiate directly.
		if ( null === $constructor ) {
			return new $class();
		}

		// Resolve constructor parameters.
		$dependencies = $this->resolveDependencies( $constructor->getParameters(), $parameters );

		return $reflector->newInstanceArgs( $dependencies );
	}

	/**
	 * Resolve dependencies for a method.
	 *
	 * @param array<int, ReflectionParameter> $parameters Parameters.
	 * @param array<string, mixed> $provided Provided parameters.
	 * @return array<int, mixed> Resolved dependencies.
	 * @throws \Exception If dependency cannot be resolved.
	 */
	private function resolveDependencies( array $parameters, array $provided = [] ): array {
		$dependencies = array();

		foreach ( $parameters as $parameter ) {
			$name = $parameter->getName();

			// Use provided parameter if available.
			if ( isset( $provided[ $name ] ) ) {
				$dependencies[] = $provided[ $name ];
				continue;
			}

			// Try to resolve from type hint.
			$type = $parameter->getType();

			if ( $type instanceof \ReflectionNamedType && ! $type->isBuiltin() ) {
				$type_name = $type->getName();
				$dependencies[] = $this->make( $type_name );
			} elseif ( $parameter->isDefaultValueAvailable() ) {
				$dependencies[] = $parameter->getDefaultValue();
			} else {
				throw new \Exception( "Cannot resolve dependency: {$name}" );
			}
		}

		return $dependencies;
	}
}


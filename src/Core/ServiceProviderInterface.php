<?php
/**
 * Service provider interface.
 *
 * @package FP\Privacy\Core
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Core;

/**
 * Service provider interface.
 */
interface ServiceProviderInterface {
	/**
	 * Register services in the container.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	public function register( ContainerInterface $container ): void;

	/**
	 * Boot services after all providers are registered.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	public function boot( ContainerInterface $container ): void;
}








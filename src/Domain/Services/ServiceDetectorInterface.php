<?php
/**
 * Service detector interface.
 *
 * @package FP\Privacy\Domain\Services
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Domain\Services;

/**
 * Interface for service detector implementations.
 */
interface ServiceDetectorInterface {
	/**
	 * Detect if a service is active.
	 *
	 * @param string $service_slug Service slug.
	 * @return bool True if service is detected.
	 */
	public function detect( string $service_slug ): bool;

	/**
	 * Detect all active services.
	 *
	 * @return array<string, bool> Array of service slugs => detected status.
	 */
	public function detectAll(): array;
}














<?php
/**
 * Legacy facade over the canonical service registry.
 *
 * @package FP\Privacy\Integrations
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 *
 * @deprecated 2.0.0 Use {@see \FP\Privacy\Domain\Services\ServiceRegistry} directly.
 */

namespace FP\Privacy\Integrations;

/**
 * Delegates to {@see \FP\Privacy\Domain\Services\ServiceRegistry} to avoid duplicate definitions.
 */
class ServiceRegistry {

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_base_registry(): array {
		return \FP\Privacy\Domain\Services\ServiceRegistry::get_base_registry();
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public function get_registry(): array {
		return self::get_base_registry();
	}
}

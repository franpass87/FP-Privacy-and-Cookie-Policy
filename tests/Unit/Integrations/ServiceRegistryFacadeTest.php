<?php
/**
 * @package FP\Privacy\Tests\Unit\Integrations
 */

declare(strict_types=1);

namespace FP\Privacy\Tests\Unit\Integrations;

use FP\Privacy\Domain\Services\ServiceRegistry as DomainServiceRegistry;
use FP\Privacy\Integrations\ServiceRegistry as IntegrationsServiceRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Verifies Integrations ServiceRegistry delegates to Domain (single source of truth).
 */
final class ServiceRegistryFacadeTest extends TestCase {
	/**
	 * @return void
	 */
	public function test_integrations_static_matches_domain_ga4_key(): void {
		$domain     = DomainServiceRegistry::get_base_registry();
		$via_facade = IntegrationsServiceRegistry::get_base_registry();

		$this->assertArrayHasKey( 'ga4', $domain );
		$this->assertArrayHasKey( 'ga4', $via_facade );
		// Stesso contenuto “stabile” (i detector sono closure diverse per istanza).
		$this->assertSame( $domain['ga4']['name'] ?? null, $via_facade['ga4']['name'] ?? null );
		$this->assertSame( $domain['ga4']['category'] ?? null, $via_facade['ga4']['category'] ?? null );
		$this->assertSame( array_keys( $domain ), array_keys( $via_facade ) );
	}

	/**
	 * Istanza e statico delegano allo stesso registry; le closure detector sono ricreate a ogni chiamata.
	 *
	 * @return void
	 */
	public function test_integrations_instance_get_registry_matches_static(): void {
		$static   = IntegrationsServiceRegistry::get_base_registry();
		$instance = ( new IntegrationsServiceRegistry() )->get_registry();

		$this->assertSame( array_keys( $static ), array_keys( $instance ) );
		foreach ( array_keys( $static ) as $key ) {
			$this->assertSame( $static[ $key ]['name'] ?? null, $instance[ $key ]['name'] ?? null );
			$this->assertSame( $static[ $key ]['category'] ?? null, $instance[ $key ]['category'] ?? null );
			$da = $static[ $key ]['detector'] ?? null;
			$db = $instance[ $key ]['detector'] ?? null;
			if ( $da instanceof \Closure ) {
				$this->assertInstanceOf( \Closure::class, $db );
			} else {
				$this->assertSame( $da, $db );
			}
		}
	}
}

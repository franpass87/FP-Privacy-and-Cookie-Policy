<?php
/**
 * Tests for RESTPermissionChecker (same-origin rules).
 *
 * @package FP\Privacy\Tests\Unit\REST
 */

declare(strict_types=1);

namespace FP\Privacy\Tests\Unit\REST;

use FP\Privacy\REST\RESTPermissionChecker;
use PHPUnit\Framework\TestCase;

/**
 * @covers \FP\Privacy\REST\RESTPermissionChecker
 */
final class RESTPermissionCheckerTest extends TestCase {
	/**
	 * @return void
	 */
	public function test_is_same_origin_empty_candidate_returns_false(): void {
		$checker = new RESTPermissionChecker();
		$this->assertFalse( $checker->is_same_origin( '', array( 'scheme' => 'https', 'host' => 'example.com' ) ) );
	}

	/**
	 * @return void
	 */
	public function test_is_same_origin_matching_https_host(): void {
		$checker = new RESTPermissionChecker();
		$site    = array(
			'scheme' => 'https',
			'host'   => 'example.com',
		);
		$this->assertTrue( $checker->is_same_origin( 'https://example.com/consent', $site ) );
		$this->assertTrue( $checker->is_same_origin( 'https://www.example.com/page', $site ) );
	}

	/**
	 * @return void
	 */
	public function test_is_same_origin_scheme_mismatch(): void {
		$checker = new RESTPermissionChecker();
		$site    = array(
			'scheme' => 'https',
			'host'   => 'example.com',
		);
		$this->assertFalse( $checker->is_same_origin( 'http://example.com/', $site ) );
	}

	/**
	 * @return void
	 */
	public function test_is_same_origin_host_mismatch(): void {
		$checker = new RESTPermissionChecker();
		$site    = array(
			'scheme' => 'https',
			'host'   => 'example.com',
		);
		$this->assertFalse( $checker->is_same_origin( 'https://evil.test/', $site ) );
	}

	/**
	 * @return void
	 */
	public function test_is_same_origin_explicit_port_mismatch(): void {
		$checker = new RESTPermissionChecker();
		$site    = array(
			'scheme' => 'https',
			'host'   => 'example.com',
			'port'   => 443,
		);
		$this->assertFalse( $checker->is_same_origin( 'https://example.com:8443/', $site ) );
	}

	/**
	 * @return void
	 */
	public function test_is_same_origin_http_default_ports_match(): void {
		$checker = new RESTPermissionChecker();
		$site    = array(
			'scheme' => 'http',
			'host'   => 'localhost',
		);
		$this->assertTrue( $checker->is_same_origin( 'http://localhost:80/wp-json/', $site ) );
	}
}

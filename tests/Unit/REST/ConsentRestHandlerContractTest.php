<?php
/**
 * @package FP\Privacy\Tests\Unit\REST
 */

declare(strict_types=1);

namespace FP\Privacy\Tests\Unit\REST;

use FP\Privacy\Presentation\REST\Controllers\ConsentController;
use FP\Privacy\REST\ConsentRestHandlerInterface;
use FP\Privacy\REST\RESTConsentHandler;
use PHPUnit\Framework\TestCase;

/**
 * Contract tests for consent REST handlers (no WordPress bootstrap).
 */
final class ConsentRestHandlerContractTest extends TestCase {
	/**
	 * @return void
	 */
	public function test_rest_consent_handler_implements_interface(): void {
		$reflection = new \ReflectionClass( RESTConsentHandler::class );
		$this->assertTrue( $reflection->implementsInterface( ConsentRestHandlerInterface::class ) );
	}

	/**
	 * @return void
	 */
	public function test_consent_controller_implements_interface(): void {
		$reflection = new \ReflectionClass( ConsentController::class );
		$this->assertTrue( $reflection->implementsInterface( ConsentRestHandlerInterface::class ) );
	}

	/**
	 * @return void
	 */
	public function test_consent_controller_has_revoke_method(): void {
		$this->assertTrue( method_exists( ConsentController::class, 'revoke_consent' ) );
	}
}

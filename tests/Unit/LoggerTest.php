<?php
/**
 * Tests for Logger utility.
 *
 * @package FP\Privacy\Tests\Unit
 */

namespace FP\Privacy\Tests\Unit;

use FP\Privacy\Utils\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Test Logger utility.
 */
class LoggerTest extends TestCase {
	/**
	 * Test error logging.
	 *
	 * @return void
	 */
	public function test_error_logging(): void {
		// Logger should not throw exceptions.
		$this->expectNotToPerformAssertions();
		
		Logger::error( 'Test error message' );
		Logger::error( 'Test error with exception', new \Exception( 'Test exception' ) );
	}

	/**
	 * Test warning logging.
	 *
	 * @return void
	 */
	public function test_warning_logging(): void {
		// Logger should not throw exceptions.
		$this->expectNotToPerformAssertions();
		
		Logger::warning( 'Test warning message' );
	}

	/**
	 * Test debug logging.
	 *
	 * @return void
	 */
	public function test_debug_logging(): void {
		// Logger should not throw exceptions.
		$this->expectNotToPerformAssertions();
		
		Logger::debug( 'Test debug message' );
		Logger::debug( 'Test debug with data', array( 'key' => 'value' ) );
	}
}




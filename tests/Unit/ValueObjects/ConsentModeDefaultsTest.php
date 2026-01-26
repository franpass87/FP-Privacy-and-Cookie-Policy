<?php
/**
 * Tests for ConsentModeDefaults value object.
 *
 * @package FP\Privacy\Tests\Unit\ValueObjects
 */

namespace FP\Privacy\Tests\Unit\ValueObjects;

use FP\Privacy\Domain\ValueObjects\ConsentModeDefaults;
use PHPUnit\Framework\TestCase;

/**
 * Test ConsentModeDefaults value object.
 */
class ConsentModeDefaultsTest extends TestCase {
	/**
	 * Test creating ConsentModeDefaults from array.
	 *
	 * @return void
	 */
	public function test_from_array(): void {
		$data = array(
			'analytics_storage'     => 'granted',
			'ad_storage'            => 'denied',
			'ad_user_data'          => 'denied',
			'ad_personalization'    => 'denied',
			'functionality_storage' => 'granted',
			'personalization_storage' => 'granted',
			'security_storage'      => 'granted',
		);

		$defaults = ConsentModeDefaults::from_array( $data );

		$this->assertInstanceOf( ConsentModeDefaults::class, $defaults );
		$this->assertEquals( 'granted', $defaults->get_analytics_storage() );
		$this->assertEquals( 'denied', $defaults->get_ad_storage() );
	}

	/**
	 * Test ConsentModeDefaults to_array conversion.
	 *
	 * @return void
	 */
	public function test_to_array(): void {
		$data = array(
			'analytics_storage' => 'granted',
			'ad_storage'        => 'denied',
		);

		$defaults = ConsentModeDefaults::from_array( $data );
		$result = $defaults->to_array();

		$this->assertIsArray( $result );
		$this->assertEquals( 'granted', $result['analytics_storage'] );
		$this->assertEquals( 'denied', $result['ad_storage'] );
	}

	/**
	 * Test ConsentModeDefaults with default values.
	 *
	 * @return void
	 */
	public function test_default_values(): void {
		$defaults = new ConsentModeDefaults();

		// ConsentModeDefaults should have default values.
		$this->assertIsString( $defaults->get_analytics_storage() );
		$this->assertIsString( $defaults->get_ad_storage() );
	}
}





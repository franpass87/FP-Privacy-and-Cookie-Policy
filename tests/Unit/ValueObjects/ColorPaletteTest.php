<?php
/**
 * Tests for ColorPalette value object.
 *
 * @package FP\Privacy\Tests\Unit\ValueObjects
 */

namespace FP\Privacy\Tests\Unit\ValueObjects;

use FP\Privacy\Domain\ValueObjects\ColorPalette;
use PHPUnit\Framework\TestCase;

/**
 * Test ColorPalette value object.
 */
class ColorPaletteTest extends TestCase {
	/**
	 * Test creating ColorPalette from array.
	 *
	 * @return void
	 */
	public function test_from_array(): void {
		$data = array(
			'primary'   => '#000000',
			'secondary' => '#ffffff',
			'accent'    => '#ff0000',
		);

		$palette = ColorPalette::from_array( $data );

		$this->assertInstanceOf( ColorPalette::class, $palette );
		$this->assertEquals( '#000000', $palette->get_primary() );
		$this->assertEquals( '#ffffff', $palette->get_secondary() );
		$this->assertEquals( '#ff0000', $palette->get_accent() );
	}

	/**
	 * Test ColorPalette to_array conversion.
	 *
	 * @return void
	 */
	public function test_to_array(): void {
		$data = array(
			'primary'   => '#123456',
			'secondary' => '#789abc',
		);

		$palette = ColorPalette::from_array( $data );
		$result = $palette->to_array();

		$this->assertIsArray( $result );
		$this->assertEquals( '#123456', $result['primary'] );
		$this->assertEquals( '#789abc', $result['secondary'] );
	}

	/**
	 * Test ColorPalette with default values.
	 *
	 * @return void
	 */
	public function test_default_values(): void {
		$palette = new ColorPalette();

		// ColorPalette should have default colors.
		$this->assertIsString( $palette->get_primary() );
		$this->assertIsString( $palette->get_secondary() );
	}
}




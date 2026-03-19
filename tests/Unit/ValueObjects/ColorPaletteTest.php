<?php
/**
 * Tests for ColorPalette value object.
 *
 * @package FP\Privacy\Tests\Unit\ValueObjects
 */

declare(strict_types=1);

namespace FP\Privacy\Tests\Unit\ValueObjects;

use FP\Privacy\Domain\ValueObjects\ColorPalette;
use PHPUnit\Framework\TestCase;

/**
 * Test ColorPalette value object.
 */
class ColorPaletteTest extends TestCase {
	/**
	 * @return void
	 */
	public function test_from_array(): void {
		$data = array(
			'surface_bg'          => '#000000',
			'surface_text'        => '#ffffff',
			'button_primary_bg'   => '#ff0000',
			'button_primary_tx'   => '#ffffff',
			'button_secondary_bg' => '#ffffff',
			'button_secondary_tx' => '#000000',
			'link'                => '#1D4ED8',
			'border'              => '#D1D5DB',
			'focus'               => '#2563EB',
		);

		$palette = ColorPalette::from_array( $data );

		$this->assertInstanceOf( ColorPalette::class, $palette );
		$this->assertSame( '#000000', $palette->get_surface_bg() );
		$this->assertSame( '#ffffff', $palette->get_surface_text() );
		$this->assertSame( '#ff0000', $palette->get_button_primary_bg() );
	}

	/**
	 * @return void
	 */
	public function test_to_array(): void {
		$data = array(
			'surface_bg'   => '#123456',
			'surface_text' => '#789abc',
		);

		$palette = ColorPalette::from_array( $data );
		$result  = $palette->to_array();

		$this->assertIsArray( $result );
		$this->assertSame( '#123456', $result['surface_bg'] );
		$this->assertSame( '#789abc', $result['surface_text'] );
	}

	/**
	 * @return void
	 */
	public function test_default_values(): void {
		$palette = new ColorPalette();

		$this->assertIsString( $palette->get_surface_bg() );
		$this->assertIsString( $palette->get_button_primary_bg() );
	}
}

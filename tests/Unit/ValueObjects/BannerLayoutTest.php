<?php
/**
 * Tests for BannerLayout value object.
 *
 * @package FP\Privacy\Tests\Unit\ValueObjects
 */

namespace FP\Privacy\Tests\Unit\ValueObjects;

use FP\Privacy\Domain\ValueObjects\BannerLayout;
use FP\Privacy\Domain\ValueObjects\ColorPalette;
use PHPUnit\Framework\TestCase;

/**
 * Test BannerLayout value object.
 */
class BannerLayoutTest extends TestCase {
	/**
	 * Test creating BannerLayout from array.
	 *
	 * @return void
	 */
	public function test_from_array(): void {
		$data = array(
			'type'                  => 'floating',
			'position'              => 'bottom',
			'palette'               => array(
				'primary'   => '#000000',
				'secondary' => '#ffffff',
			),
			'sync_modal_and_button' => true,
			'enable_dark_mode'      => false,
		);

		$layout = BannerLayout::from_array( $data );

		$this->assertInstanceOf( BannerLayout::class, $layout );
		$this->assertEquals( 'floating', $layout->get_type() );
		$this->assertEquals( 'bottom', $layout->get_position() );
		$this->assertInstanceOf( ColorPalette::class, $layout->get_palette() );
		$this->assertTrue( $layout->get_sync_modal_and_button() );
		$this->assertFalse( $layout->get_enable_dark_mode() );
	}

	/**
	 * Test BannerLayout to_array conversion.
	 *
	 * @return void
	 */
	public function test_to_array(): void {
		$data = array(
			'type'                  => 'modal',
			'position'              => 'top',
			'palette'               => array(
				'primary' => '#ff0000',
			),
			'sync_modal_and_button' => false,
			'enable_dark_mode'      => true,
		);

		$layout = BannerLayout::from_array( $data );
		$result = $layout->to_array();

		$this->assertIsArray( $result );
		$this->assertEquals( 'modal', $result['type'] );
		$this->assertEquals( 'top', $result['position'] );
		$this->assertIsArray( $result['palette'] );
		$this->assertFalse( $result['sync_modal_and_button'] );
		$this->assertTrue( $result['enable_dark_mode'] );
	}

	/**
	 * Test BannerLayout with default values.
	 *
	 * @return void
	 */
	public function test_default_values(): void {
		$layout = new BannerLayout();

		$this->assertEquals( 'floating', $layout->get_type() );
		$this->assertEquals( 'bottom', $layout->get_position() );
		$this->assertTrue( $layout->get_sync_modal_and_button() );
		$this->assertFalse( $layout->get_enable_dark_mode() );
	}
}




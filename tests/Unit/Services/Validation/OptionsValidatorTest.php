<?php
/**
 * Tests for OptionsValidator.
 *
 * @package FP\Privacy\Tests\Unit\Services\Validation
 */

namespace FP\Privacy\Tests\Unit\Services\Validation;

use FP\Privacy\Services\Validation\OptionsValidator;
use PHPUnit\Framework\TestCase;

/**
 * Test OptionsValidator.
 */
class OptionsValidatorTest extends TestCase {
	/**
	 * Test sanitize with minimal data.
	 *
	 * @return void
	 */
	public function test_sanitize_minimal(): void {
		$validator = new OptionsValidator();

		$value = array(
			'languages_active' => array( 'en_US' ),
		);

		$defaults = array(
			'languages_active'      => array( 'en_US' ),
			'banner_texts'           => array(),
			'banner_layout'          => array(
				'type'     => 'floating',
				'position' => 'bottom',
				'palette'  => array(),
			),
			'categories'             => array(),
			'consent_mode_defaults' => array(),
			'retention_days'         => 365,
			'consent_revision'       => 1,
			'gpc_enabled'            => false,
			'preview_mode'           => false,
			'debug_logging'          => false,
			'pages'                  => array(),
			'org_name'               => '',
			'vat'                    => '',
			'address'                => '',
			'dpo_name'               => '',
			'dpo_email'              => '',
			'privacy_email'          => '',
			'snapshots'              => array(),
			'scripts'                => array(),
			'detector_alert'         => array(),
			'detector_notifications' => array(),
			'auto_update_services'   => false,
			'auto_update_policies'   => false,
			'auto_translations'      => array(),
		);

		$result = $validator->sanitize( $value, $defaults );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'languages_active', $result );
		$this->assertArrayHasKey( 'banner_layout', $result );
		$this->assertArrayHasKey( 'categories', $result );
	}

	/**
	 * Test sanitize with empty arrays.
	 *
	 * @return void
	 */
	public function test_sanitize_empty(): void {
		$validator = new OptionsValidator();

		$value = array();
		$defaults = array(
			'languages_active'      => array( 'en_US' ),
			'banner_texts'          => array(),
			'banner_layout'         => array(
				'type'     => 'floating',
				'position' => 'bottom',
				'palette'  => array(),
			),
			'categories'            => array(),
			'consent_mode_defaults' => array(),
			'retention_days'        => 365,
			'consent_revision'      => 1,
			'gpc_enabled'           => false,
			'preview_mode'          => false,
			'debug_logging'         => false,
			'pages'                 => array(),
			'org_name'              => '',
			'vat'                   => '',
			'address'               => '',
			'dpo_name'              => '',
			'dpo_email'             => '',
			'privacy_email'         => '',
			'snapshots'             => array(),
			'scripts'               => array(),
			'detector_alert'        => array(),
			'detector_notifications' => array(),
			'auto_update_services'  => false,
			'auto_update_policies'  => false,
			'auto_translations'     => array(),
		);

		$result = $validator->sanitize( $value, $defaults );

		$this->assertIsArray( $result );
		// Should return sanitized defaults.
		$this->assertArrayHasKey( 'languages_active', $result );
	}
}




<?php
/**
 * Consent mode defaults value object.
 *
 * @package FP\Privacy\Domain\ValueObjects
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Domain\ValueObjects;

use FP\Privacy\Domain\Exceptions\InvalidConsentModeDefaultsException;
use function strtolower;
use function in_array;
use function trim;

/**
 * Immutable consent mode defaults value object.
 *
 * Represents the Google Consent Mode v2 defaults configuration.
 */
class ConsentModeDefaults {
	/**
	 * Analytics storage consent.
	 *
	 * @var string
	 */
	private $analytics_storage;

	/**
	 * Ad storage consent.
	 *
	 * @var string
	 */
	private $ad_storage;

	/**
	 * Ad user data consent.
	 *
	 * @var string
	 */
	private $ad_user_data;

	/**
	 * Ad personalization consent.
	 *
	 * @var string
	 */
	private $ad_personalization;

	/**
	 * Functionality storage consent.
	 *
	 * @var string
	 */
	private $functionality_storage;

	/**
	 * Personalization storage consent.
	 *
	 * @var string
	 */
	private $personalization_storage;

	/**
	 * Security storage consent.
	 *
	 * @var string
	 */
	private $security_storage;

	/**
	 * Valid consent values.
	 *
	 * @var array<string>
	 */
	private const VALID_VALUES = array( 'granted', 'denied' );

	/**
	 * Constructor.
	 *
	 * @param string $analytics_storage       Analytics storage consent.
	 * @param string $ad_storage              Ad storage consent.
	 * @param string $ad_user_data            Ad user data consent.
	 * @param string $ad_personalization      Ad personalization consent.
	 * @param string $functionality_storage   Functionality storage consent.
	 * @param string $personalization_storage Personalization storage consent.
	 * @param string $security_storage        Security storage consent.
	 *
	 * @throws InvalidConsentModeDefaultsException If any value is invalid.
	 */
	public function __construct(
		$analytics_storage = 'denied',
		$ad_storage = 'denied',
		$ad_user_data = 'denied',
		$ad_personalization = 'denied',
		$functionality_storage = 'granted',
		$personalization_storage = 'denied',
		$security_storage = 'granted'
	) {
		$this->analytics_storage       = $this->validate_value( $analytics_storage, 'denied' );
		$this->ad_storage              = $this->validate_value( $ad_storage, 'denied' );
		$this->ad_user_data            = $this->validate_value( $ad_user_data, 'denied' );
		$this->ad_personalization      = $this->validate_value( $ad_personalization, 'denied' );
		$this->functionality_storage   = $this->validate_value( $functionality_storage, 'granted' );
		$this->personalization_storage = $this->validate_value( $personalization_storage, 'denied' );
		$this->security_storage        = $this->validate_value( $security_storage, 'granted' );
	}

	/**
	 * Create from array.
	 *
	 * @param array<string, string> $data Consent mode data.
	 *
	 * @return self
	 */
	public static function from_array( array $data ) {
		return new self(
			$data['analytics_storage'] ?? 'denied',
			$data['ad_storage'] ?? 'denied',
			$data['ad_user_data'] ?? 'denied',
			$data['ad_personalization'] ?? 'denied',
			$data['functionality_storage'] ?? 'granted',
			$data['personalization_storage'] ?? 'denied',
			$data['security_storage'] ?? 'granted'
		);
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, string>
	 */
	public function to_array() {
		return array(
			'analytics_storage'       => $this->analytics_storage,
			'ad_storage'              => $this->ad_storage,
			'ad_user_data'            => $this->ad_user_data,
			'ad_personalization'      => $this->ad_personalization,
			'functionality_storage'   => $this->functionality_storage,
			'personalization_storage' => $this->personalization_storage,
			'security_storage'        => $this->security_storage,
		);
	}

	/**
	 * Get analytics storage consent.
	 *
	 * @return string
	 */
	public function get_analytics_storage() {
		return $this->analytics_storage;
	}

	/**
	 * Get ad storage consent.
	 *
	 * @return string
	 */
	public function get_ad_storage() {
		return $this->ad_storage;
	}

	/**
	 * Get ad user data consent.
	 *
	 * @return string
	 */
	public function get_ad_user_data() {
		return $this->ad_user_data;
	}

	/**
	 * Get ad personalization consent.
	 *
	 * @return string
	 */
	public function get_ad_personalization() {
		return $this->ad_personalization;
	}

	/**
	 * Get functionality storage consent.
	 *
	 * @return string
	 */
	public function get_functionality_storage() {
		return $this->functionality_storage;
	}

	/**
	 * Get personalization storage consent.
	 *
	 * @return string
	 */
	public function get_personalization_storage() {
		return $this->personalization_storage;
	}

	/**
	 * Get security storage consent.
	 *
	 * @return string
	 */
	public function get_security_storage() {
		return $this->security_storage;
	}

	/**
	 * Validate consent value.
	 *
	 * @param string $value   Value to validate.
	 * @param string $default Default value if invalid.
	 *
	 * @return string
	 */
	private function validate_value( $value, $default ) {
		if ( ! is_string( $value ) ) {
			return $default;
		}

		$normalized = strtolower( trim( $value ) );

		if ( ! in_array( $normalized, self::VALID_VALUES, true ) ) {
			return $default;
		}

		return $normalized;
	}
}


<?php
/**
 * Service definition value object.
 *
 * @package FP\Privacy\Domain\ValueObjects
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Domain\ValueObjects;

/**
 * Immutable service definition value object.
 *
 * Represents a third-party service definition for policy generation.
 */
class ServiceDefinition {
	/**
	 * Service name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Service category.
	 *
	 * @var string
	 */
	private $category;

	/**
	 * Service provider.
	 *
	 * @var string
	 */
	private $provider;

	/**
	 * Privacy policy URL.
	 *
	 * @var string
	 */
	private $policy_url;

	/**
	 * Cookie names.
	 *
	 * @var array<string>
	 */
	private $cookies;

	/**
	 * Legal basis.
	 *
	 * @var string
	 */
	private $legal_basis;

	/**
	 * Purpose.
	 *
	 * @var string
	 */
	private $purpose;

	/**
	 * Data retention period.
	 *
	 * @var string
	 */
	private $retention;

	/**
	 * Data location.
	 *
	 * @var string
	 */
	private $data_location;

	/**
	 * Detector callable.
	 *
	 * @var callable|null
	 */
	private $detector;

	/**
	 * Valid categories.
	 *
	 * @var array<string>
	 */
	private const VALID_CATEGORIES = array( 'necessary', 'preferences', 'statistics', 'marketing' );

	/**
	 * Valid legal bases.
	 *
	 * @var array<string>
	 */
	private const VALID_LEGAL_BASES = array( 'Consent', 'Contract', 'Legitimate interest', 'Legal obligation' );

	/**
	 * Constructor.
	 *
	 * @param string        $name         Service name.
	 * @param string        $category     Service category.
	 * @param string        $provider    Service provider.
	 * @param string        $policy_url  Privacy policy URL.
	 * @param array<string> $cookies     Cookie names.
	 * @param string        $legal_basis Legal basis.
	 * @param string        $purpose     Purpose.
	 * @param string        $retention   Data retention period.
	 * @param string        $data_location Data location.
	 * @param callable|null $detector    Detector callable.
	 */
	public function __construct(
		$name,
		$category = 'marketing',
		$provider = '',
		$policy_url = '',
		array $cookies = array(),
		$legal_basis = 'Consent',
		$purpose = '',
		$retention = '',
		$data_location = '',
		$detector = null
	) {
		$this->name         = $this->sanitize_string( $name );
		$this->category     = $this->validate_category( $category );
		$this->provider     = $this->sanitize_string( $provider );
		$this->policy_url   = $this->sanitize_url( $policy_url );
		$this->cookies      = $this->sanitize_cookies( $cookies );
		$this->legal_basis  = $this->validate_legal_basis( $legal_basis );
		$this->purpose      = $this->sanitize_string( $purpose );
		$this->retention    = $this->sanitize_string( $retention );
		$this->data_location = $this->sanitize_string( $data_location );
		$this->detector     = is_callable( $detector ) ? $detector : null;
	}

	/**
	 * Create from array.
	 *
	 * @param array<string, mixed> $data Service data.
	 *
	 * @return self
	 */
	public static function from_array( array $data ) {
		return new self(
			$data['name'] ?? '',
			$data['category'] ?? 'marketing',
			$data['provider'] ?? '',
			$data['policy_url'] ?? '',
			$data['cookies'] ?? array(),
			$data['legal_basis'] ?? 'Consent',
			$data['purpose'] ?? '',
			$data['retention'] ?? '',
			$data['data_location'] ?? '',
			$data['detector'] ?? null
		);
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array() {
		$data = array(
			'name'         => $this->name,
			'category'     => $this->category,
			'provider'     => $this->provider,
			'policy_url'   => $this->policy_url,
			'cookies'      => $this->cookies,
			'legal_basis'  => $this->legal_basis,
			'purpose'      => $this->purpose,
			'retention'    => $this->retention,
			'data_location' => $this->data_location,
		);

		if ( $this->detector !== null ) {
			$data['detector'] = $this->detector;
		}

		return $data;
	}

	/**
	 * Get service name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get service category.
	 *
	 * @return string
	 */
	public function get_category() {
		return $this->category;
	}

	/**
	 * Get service provider.
	 *
	 * @return string
	 */
	public function get_provider() {
		return $this->provider;
	}

	/**
	 * Get privacy policy URL.
	 *
	 * @return string
	 */
	public function get_policy_url() {
		return $this->policy_url;
	}

	/**
	 * Get cookie names.
	 *
	 * @return array<string>
	 */
	public function get_cookies() {
		return $this->cookies;
	}

	/**
	 * Get legal basis.
	 *
	 * @return string
	 */
	public function get_legal_basis() {
		return $this->legal_basis;
	}

	/**
	 * Get purpose.
	 *
	 * @return string
	 */
	public function get_purpose() {
		return $this->purpose;
	}

	/**
	 * Get data retention period.
	 *
	 * @return string
	 */
	public function get_retention() {
		return $this->retention;
	}

	/**
	 * Get data location.
	 *
	 * @return string
	 */
	public function get_data_location() {
		return $this->data_location;
	}

	/**
	 * Get detector callable.
	 *
	 * @return callable|null
	 */
	public function get_detector() {
		return $this->detector;
	}

	/**
	 * Check if service can be detected.
	 *
	 * @return bool
	 */
	public function can_be_detected() {
		return $this->detector !== null;
	}

	/**
	 * Sanitize string value.
	 *
	 * @param mixed $value Value to sanitize.
	 *
	 * @return string
	 */
	private function sanitize_string( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}

		return trim( $value );
	}

	/**
	 * Sanitize URL value.
	 *
	 * @param mixed $url URL to sanitize.
	 *
	 * @return string
	 */
	private function sanitize_url( $url ) {
		if ( ! is_string( $url ) ) {
			return '';
		}

		$url = trim( $url );
		if ( empty( $url ) ) {
			return '';
		}

		// Validate URL format.
		if ( filter_var( $url, FILTER_VALIDATE_URL ) !== false ) {
			return $url;
		}

		return '';
	}

	/**
	 * Sanitize cookies array.
	 *
	 * @param mixed $cookies Cookies to sanitize.
	 *
	 * @return array<string>
	 */
	private function sanitize_cookies( $cookies ) {
		if ( ! is_array( $cookies ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $cookies as $cookie ) {
			if ( is_string( $cookie ) ) {
				$sanitized[] = trim( $cookie );
			}
		}

		return array_filter( $sanitized );
	}

	/**
	 * Validate category.
	 *
	 * @param string $category Category to validate.
	 *
	 * @return string
	 */
	private function validate_category( $category ) {
		if ( ! is_string( $category ) || ! in_array( $category, self::VALID_CATEGORIES, true ) ) {
			return 'marketing';
		}

		return $category;
	}

	/**
	 * Validate legal basis.
	 *
	 * @param string $legal_basis Legal basis to validate.
	 *
	 * @return string
	 */
	private function validate_legal_basis( $legal_basis ) {
		if ( ! is_string( $legal_basis ) || ! in_array( $legal_basis, self::VALID_LEGAL_BASES, true ) ) {
			return 'Consent';
		}

		return $legal_basis;
	}
}





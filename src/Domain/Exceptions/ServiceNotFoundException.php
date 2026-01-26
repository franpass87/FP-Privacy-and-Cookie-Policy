<?php
/**
 * Service not found exception.
 *
 * @package FP\Privacy\Domain\Exceptions
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Domain\Exceptions;

/**
 * Exception thrown when a service is not found in the registry.
 */
class ServiceNotFoundException extends PrivacyException {
	/**
	 * Service slug.
	 *
	 * @var string
	 */
	private $service_slug;

	/**
	 * Constructor.
	 *
	 * @param string         $service_slug Service slug.
	 * @param string         $message     Exception message.
	 * @param int            $code        Exception code.
	 * @param \Throwable|null $previous   Previous exception.
	 */
	public function __construct( $service_slug, $message = '', $code = 0, $previous = null ) {
		$this->service_slug = $service_slug;
		$message            = $message ?: sprintf( 'Service "%s" not found in registry', $service_slug );
		parent::__construct( $message, $code, $previous );
	}

	/**
	 * Get service slug.
	 *
	 * @return string
	 */
	public function get_service_slug() {
		return $this->service_slug;
	}
}









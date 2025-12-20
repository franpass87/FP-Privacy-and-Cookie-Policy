<?php
/**
 * Invalid consent mode defaults exception.
 *
 * @package FP\Privacy\Domain\Exceptions
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Domain\Exceptions;

/**
 * Exception thrown when consent mode defaults are invalid.
 */
class InvalidConsentModeDefaultsException extends \InvalidArgumentException {
	/**
	 * Constructor.
	 *
	 * @param string         $message  Exception message.
	 * @param int            $code     Exception code.
	 * @param \Throwable|null $previous Previous exception.
	 */
	public function __construct( $message = 'Invalid consent mode defaults', $code = 0, $previous = null ) {
		parent::__construct( $message, $code, $previous );
	}
}





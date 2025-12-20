<?php
/**
 * IP salt service - replaces global function.
 *
 * @package FP\Privacy\Services\Security
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Services\Security;

use FP\Privacy\Services\Options\OptionsInterface;

/**
 * Service for generating and managing IP salt.
 */
class IpSaltService {
	/**
	 * Options service.
	 *
	 * @var OptionsInterface
	 */
	private $options;

	/**
	 * Cached salt.
	 *
	 * @var string|null
	 */
	private $salt = null;

	/**
	 * Constructor.
	 *
	 * @param OptionsInterface $options Options service.
	 */
	public function __construct( OptionsInterface $options ) {
		$this->options = $options;
	}

	/**
	 * Get IP salt.
	 *
	 * @return string Salt value.
	 */
	public function getSalt(): string {
		if ( null !== $this->salt ) {
			return $this->salt;
		}

		$option_key = 'fp_privacy_ip_salt';
		$stored = $this->options->get( $option_key );

		if ( is_string( $stored ) && '' !== $stored ) {
			$this->salt = $stored;
			return $this->salt;
		}

		// Generate new salt.
		if ( function_exists( 'wp_generate_password' ) ) {
			$this->salt = wp_generate_password( 64, false, false );
		} elseif ( function_exists( 'wp_salt' ) ) {
			$this->salt = wp_salt( 'fp-privacy-ip' );
		} else {
			try {
				$this->salt = bin2hex( random_bytes( 32 ) );
			} catch ( \Exception $e ) {
				$this->salt = md5( uniqid( 'fp-privacy', true ) );
			}
		}

		// Store salt.
		$this->options->set( $option_key, $this->salt );

		return $this->salt;
	}
}







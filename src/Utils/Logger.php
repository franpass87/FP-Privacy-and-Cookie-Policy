<?php
/**
 * Logger utility for centralized error logging.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

/**
 * Centralized logger for plugin errors and debug messages.
 */
class Logger {
	/**
	 * Log prefix.
	 *
	 * @var string
	 */
	private const LOG_PREFIX = 'FP Privacy: ';

	/**
	 * Log an error message.
	 *
	 * @param string $message Error message.
	 * @param \Throwable|null $exception Optional exception for context.
	 * @return void
	 */
	public static function error( string $message, ?\Throwable $exception = null ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$log_message = self::LOG_PREFIX . $message;

		if ( $exception ) {
			$log_message .= ' - ' . $exception->getMessage();
			if ( $exception->getFile() ) {
				$log_message .= sprintf( ' in %s:%d', $exception->getFile(), $exception->getLine() );
			}
		}

		error_log( $log_message );
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message Warning message.
	 * @return void
	 */
	public static function warning( string $message ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		error_log( self::LOG_PREFIX . '[WARNING] ' . $message );
	}

	/**
	 * Log a debug message.
	 *
	 * @param string $message Debug message.
	 * @param mixed  $data Optional data to log.
	 * @return void
	 */
	public static function debug( string $message, $data = null ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$log_message = self::LOG_PREFIX . '[DEBUG] ' . $message;

		if ( null !== $data ) {
			$log_message .= ' - ' . wp_json_encode( $data, JSON_PRETTY_PRINT );
		}

		error_log( $log_message );
	}
}




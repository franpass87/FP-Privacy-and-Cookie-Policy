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
	 * @param string                       $message   Error message.
	 * @param \Throwable|array<mixed>|null $context   Optional exception or data array for context.
	 * @return void
	 */
	public static function error( string $message, $context = null ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$log_message = self::LOG_PREFIX . $message;

		if ( $context instanceof \Throwable ) {
			$log_message .= ' - ' . $context->getMessage();
			if ( $context->getFile() ) {
				$log_message .= sprintf( ' in %s:%d', $context->getFile(), $context->getLine() );
			}
		} elseif ( \is_array( $context ) || \is_object( $context ) ) {
			$log_message .= ' - ' . wp_json_encode( $context, JSON_PRETTY_PRINT );
		} elseif ( null !== $context ) {
			$log_message .= ' - ' . (string) $context;
		}

		error_log( $log_message );
	}

	/**
	 * Log an informational message.
	 *
	 * @param string $message Info message.
	 * @param mixed  $data    Optional data to log.
	 * @return void
	 */
	public static function info( string $message, $data = null ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$log_message = self::LOG_PREFIX . '[INFO] ' . $message;

		if ( null !== $data ) {
			$log_message .= ' - ' . wp_json_encode( $data, JSON_PRETTY_PRINT );
		}

		error_log( $log_message );
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message Warning message.
	 * @param mixed  $data    Optional data to log.
	 * @return void
	 */
	public static function warning( string $message, $data = null ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$log_message = self::LOG_PREFIX . '[WARNING] ' . $message;

		if ( null !== $data ) {
			$log_message .= ' - ' . wp_json_encode( $data, JSON_PRETTY_PRINT );
		}

		error_log( $log_message );
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





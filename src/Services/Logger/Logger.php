<?php
/**
 * Logger implementation.
 *
 * @package FP\Privacy\Services\Logger
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Services\Logger;

/**
 * Logger implementation using WordPress debug log.
 */
class Logger implements LoggerInterface {
	/**
	 * Minimum log level.
	 *
	 * @var string
	 */
	private $min_level;

	/**
	 * Constructor.
	 *
	 * @param string $min_level Minimum log level.
	 */
	public function __construct( string $min_level = LogLevel::DEBUG ) {
		$this->min_level = $min_level;
	}

	/**
	 * System is unusable.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	public function emergency( string $message, array $context = array() ): void {
		$this->log( LogLevel::EMERGENCY, $message, $context );
	}

	/**
	 * Action must be taken immediately.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	public function alert( string $message, array $context = array() ): void {
		$this->log( LogLevel::ALERT, $message, $context );
	}

	/**
	 * Critical conditions.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	public function critical( string $message, array $context = array() ): void {
		$this->log( LogLevel::CRITICAL, $message, $context );
	}

	/**
	 * Runtime errors that do not require immediate action.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	public function error( string $message, array $context = array() ): void {
		$this->log( LogLevel::ERROR, $message, $context );
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	public function warning( string $message, array $context = array() ): void {
		$this->log( LogLevel::WARNING, $message, $context );
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	public function notice( string $message, array $context = array() ): void {
		$this->log( LogLevel::NOTICE, $message, $context );
	}

	/**
	 * Interesting events.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	public function info( string $message, array $context = array() ): void {
		$this->log( LogLevel::INFO, $message, $context );
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	public function debug( string $message, array $context = array() ): void {
		$this->log( LogLevel::DEBUG, $message, $context );
	}

	/**
	 * Log a message.
	 *
	 * @param string $level Log level.
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	private function log( string $level, string $message, array $context = array() ): void {
		// Check if we should log this level.
		if ( LogLevel::getPriority( $level ) < LogLevel::getPriority( $this->min_level ) ) {
			return;
		}

		// Only log if WP_DEBUG is enabled.
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		// Format message with context.
		$formatted = $this->formatMessage( $message, $context );

		// Use WordPress error_log if available, otherwise PHP error_log.
		if ( function_exists( 'error_log' ) ) {
			error_log( sprintf( '[FP Privacy %s] %s', strtoupper( $level ), $formatted ) );
		}
	}

	/**
	 * Format message with context.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return string Formatted message.
	 */
	private function formatMessage( string $message, array $context = array() ): string {
		if ( empty( $context ) ) {
			return $message;
		}

		$context_str = wp_json_encode( $context, JSON_PRETTY_PRINT );
		return $message . ' ' . $context_str;
	}
}











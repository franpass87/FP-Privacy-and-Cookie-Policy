<?php
/**
 * Logger interface (PSR-3 compatible).
 *
 * @package FP\Privacy\Services\Logger
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Services\Logger;

/**
 * Logger interface following PSR-3.
 */
interface LoggerInterface {
	/**
	 * System is unusable.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	public function emergency( string $message, array $context = array() ): void;

	/**
	 * Action must be taken immediately.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	public function alert( string $message, array $context = array() ): void;

	/**
	 * Critical conditions.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	public function critical( string $message, array $context = array() ): void;

	/**
	 * Runtime errors that do not require immediate action.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	public function error( string $message, array $context = array() ): void;

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	public function warning( string $message, array $context = array() ): void;

	/**
	 * Normal but significant events.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	public function notice( string $message, array $context = array() ): void;

	/**
	 * Interesting events.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	public function info( string $message, array $context = array() ): void;

	/**
	 * Detailed debug information.
	 *
	 * @param string $message Message.
	 * @param array<string, mixed> $context Context data.
	 * @return void
	 */
	public function debug( string $message, array $context = array() ): void;
}











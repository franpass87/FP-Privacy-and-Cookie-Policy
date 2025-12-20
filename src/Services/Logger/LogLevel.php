<?php
/**
 * Log levels.
 *
 * @package FP\Privacy\Services\Logger
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Services\Logger;

/**
 * Log level constants.
 */
class LogLevel {
	const EMERGENCY = 'emergency';
	const ALERT     = 'alert';
	const CRITICAL  = 'critical';
	const ERROR     = 'error';
	const WARNING   = 'warning';
	const NOTICE    = 'notice';
	const INFO      = 'info';
	const DEBUG     = 'debug';

	/**
	 * Get all log levels.
	 *
	 * @return array<int, string> Log levels.
	 */
	public static function all(): array {
		return array(
			self::EMERGENCY,
			self::ALERT,
			self::CRITICAL,
			self::ERROR,
			self::WARNING,
			self::NOTICE,
			self::INFO,
			self::DEBUG,
		);
	}

	/**
	 * Get numeric priority for level.
	 *
	 * @param string $level Log level.
	 * @return int Priority (higher = more important).
	 */
	public static function getPriority( string $level ): int {
		$priorities = array(
			self::EMERGENCY => 8,
			self::ALERT     => 7,
			self::CRITICAL  => 6,
			self::ERROR     => 5,
			self::WARNING   => 4,
			self::NOTICE    => 3,
			self::INFO      => 2,
			self::DEBUG     => 1,
		);

		return $priorities[ $level ] ?? 0;
	}
}











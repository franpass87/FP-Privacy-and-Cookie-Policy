<?php
/**
 * Stub minimi WP-CLI per PHPStan (senza dipendenza composer aggiuntiva).
 *
 * @package FP\Privacy\Tools
 */

declare(strict_types=1);

if ( ! class_exists( 'WP_CLI_Command', false ) ) {
	/**
	 * Base class for WP-CLI commands.
	 */
	class WP_CLI_Command {
	}
}

if ( ! class_exists( 'WP_CLI', false ) ) {
	/**
	 * WP-CLI facade (metodi usati dal plugin).
	 */
	class WP_CLI {
		/**
		 * @param mixed ...$args
		 */
		public static function log( ...$args ): void {
		}

		/**
		 * @param mixed ...$args
		 */
		public static function success( ...$args ): void {
		}

		/**
		 * @param mixed ...$args
		 */
		public static function warning( ...$args ): void {
		}

		/**
		 * @param mixed ...$args
		 */
		public static function error( ...$args ): void {
		}

		/**
		 * @param mixed ...$args
		 */
		public static function line( ...$args ): void {
		}

		/**
		 * @param mixed ...$args
		 */
		public static function add_command( ...$args ): void {
		}
	}
}

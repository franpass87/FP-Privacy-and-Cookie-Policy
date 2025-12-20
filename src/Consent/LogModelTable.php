<?php
/**
 * Consent log model table manager.
 *
 * @package FP\Privacy\Consent
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Consent;

use FP\Privacy\Services\Database\DatabaseInterface;

/**
 * Handles table creation and management for consent log.
 */
class LogModelTable {
	/**
	 * Table name.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Database service (optional, falls back to $wpdb if not provided).
	 *
	 * @var DatabaseInterface|null
	 */
	private $database;

	/**
	 * Tracks whether the consent log table has already been ensured for this request.
	 *
	 * @var bool
	 */
	private static $table_ensured = false;

	/**
	 * Constructor.
	 *
	 * @param string $table_name Table name.
	 * @param DatabaseInterface|null $database Database service (optional).
	 */
	public function __construct( $table_name, ?DatabaseInterface $database = null ) {
		$this->table = $table_name;
		$this->database = $database;
	}

	/**
	 * Ensure the consent log table exists, attempting to create it if missing.
	 *
	 * @return bool
	 */
	public function ensure_table_exists() {
		if ( $this->database ) {
			// Use database service.
			$like = str_replace( '_', '\\_', $this->table );
			$exists = $this->database->get_var( 'SHOW TABLES LIKE %s', array( $like ) );

			if ( $exists === $this->table ) {
				return true;
			}

			$this->maybe_create_table();

			$exists = $this->database->get_var( 'SHOW TABLES LIKE %s', array( $like ) );

			if ( $exists === $this->table ) {
				return true;
			}

			if ( function_exists( 'error_log' ) ) {
				error_log( sprintf( '[FP Privacy] Unable to create consent log table %s', $this->table ) );
			}

			return false;
		}

		// Fallback to global $wpdb for backward compatibility.
		global $wpdb;

		if ( ! isset( $wpdb ) || ! isset( $wpdb->dbh ) ) {
			return false;
		}

		$table = $this->table;
		$like  = $wpdb->esc_like( $table );

		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );

		if ( $exists === $table ) {
			return true;
		}

		$this->maybe_create_table();

		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );

		if ( $exists === $table ) {
			return true;
		}

		if ( function_exists( 'error_log' ) ) {
			error_log( sprintf( '[FP Privacy] Unable to create consent log table %s', $table ) );
		}

		return false;
	}

	/**
	 * Maybe create table.
	 *
	 * @return void
	 */
	public function maybe_create_table() {
		// Get charset collate - always use $wpdb for this as it's WordPress-specific.
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table           = $this->table;

		$sql = "CREATE TABLE {$table} (
id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
consent_id VARCHAR(64) NOT NULL,
event ENUM('accept_all','reject_all','consent','reset','revision_bump') NOT NULL,
states LONGTEXT NULL,
ip_hash CHAR(64) NOT NULL,
ua VARCHAR(255) NULL,
lang VARCHAR(16) NULL,
rev INT NOT NULL DEFAULT 1,
created_at DATETIME NOT NULL,
PRIMARY KEY  (id),
KEY event (event),
KEY created_at (created_at),
KEY rev (rev)
) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		\dbDelta( $sql );
	}

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public function get_table() {
		return $this->table;
	}

	/**
	 * Check if table has been ensured in this request.
	 *
	 * @return bool
	 */
	public static function is_table_ensured() {
		return self::$table_ensured;
	}

	/**
	 * Mark table as ensured.
	 *
	 * @return void
	 */
	public static function mark_table_ensured() {
		self::$table_ensured = true;
	}
}







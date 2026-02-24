<?php
/**
 * Consent log model table manager.
 *
 * @package FP\Privacy\Consent
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Infrastructure\Database;

use FP\Privacy\Services\Database\DatabaseInterface;

/**
 * Handles table creation and management for consent log.
 * 
 * This class was moved from Consent\LogModelTable to Infrastructure\Database\ConsentTable.
 * The old class name is kept as an alias for backward compatibility.
 */
class ConsentTable {
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
	 * Current schema version. Bump when the table structure changes.
	 */
	const SCHEMA_VERSION = 2;

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
				$this->maybe_migrate_schema();
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
			$this->maybe_migrate_schema();
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
event ENUM('accept_all','reject_all','consent','reset','revision_bump','consent_revoked','consent_withdrawn') NOT NULL,
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
	 * Run pending schema migrations if the stored version is outdated.
	 *
	 * @return void
	 */
	private function maybe_migrate_schema() {
		$option_key      = 'fp_privacy_consent_schema_version';
		$current_version = (int) \get_option( $option_key, 1 );

		if ( $current_version >= self::SCHEMA_VERSION ) {
			return;
		}

		global $wpdb;

		if ( $current_version < 2 ) {
			// v2: add consent_revoked and consent_withdrawn to ENUM.
			$wpdb->query(
				"ALTER TABLE {$this->table} MODIFY COLUMN event ENUM('accept_all','reject_all','consent','reset','revision_bump','consent_revoked','consent_withdrawn') NOT NULL"
			);
		}

		\update_option( $option_key, self::SCHEMA_VERSION, true );
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
	 * Shares state with legacy LogModelTable for compatibility.
	 *
	 * @return bool
	 */
	public static function is_table_ensured() {
		// Check legacy LogModelTable first for shared state.
		if ( class_exists( '\\FP\\Privacy\\Consent\\LogModelTable' ) ) {
			return \FP\Privacy\Consent\LogModelTable::is_table_ensured();
		}
		return self::$table_ensured;
	}

	/**
	 * Mark table as ensured.
	 * 
	 * Shares state with legacy LogModelTable for compatibility.
	 *
	 * @return void
	 */
	public static function mark_table_ensured() {
		// Update legacy LogModelTable state if it exists.
		if ( class_exists( '\\FP\\Privacy\\Consent\\LogModelTable' ) ) {
			\FP\Privacy\Consent\LogModelTable::mark_table_ensured();
		}
		self::$table_ensured = true;
	}
}

// Backward compatibility alias.
if ( ! class_exists( '\\FP\\Privacy\\Consent\\LogModelTable' ) ) {
	class_alias( \FP\Privacy\Infrastructure\Database\ConsentTable::class, '\\FP\\Privacy\\Consent\\LogModelTable' );
}







<?php
/**
 * WordPress database adapter.
 *
 * @package FP\Privacy\Infrastructure\Database
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Infrastructure\Database;

use FP\Privacy\Services\Database\DatabaseInterface;

/**
 * Database adapter wrapping WordPress $wpdb.
 */
class WpdbAdapter implements DatabaseInterface {
	/**
	 * WordPress database instance.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Execute a query.
	 *
	 * @param string $query SQL query.
	 * @param array<int|string, mixed> $args Query arguments.
	 * @return int|false Number of rows affected or false on error.
	 */
	public function query( string $query, array $args = array() ) {
		if ( ! empty( $args ) ) {
			$query = $this->wpdb->prepare( $query, ...$args );
		}
		return $this->wpdb->query( $query );
	}

	/**
	 * Get a single variable.
	 *
	 * @param string $query SQL query.
	 * @param array<int|string, mixed> $args Query arguments.
	 * @return mixed|null Variable value or null.
	 */
	public function get_var( string $query, array $args = array() ) {
		if ( ! empty( $args ) ) {
			$query = $this->wpdb->prepare( $query, ...$args );
		}
		$result = $this->wpdb->get_var( $query );
		return null !== $result ? $result : null;
	}

	/**
	 * Get a single row.
	 *
	 * @param string $query SQL query.
	 * @param array<int|string, mixed> $args Query arguments.
	 * @param string $output Output format (OBJECT, ARRAY_A, ARRAY_N).
	 * @return object|array<string|int, mixed>|null Row or null.
	 */
	public function get_row( string $query, array $args = array(), string $output = 'OBJECT' ) {
		if ( ! empty( $args ) ) {
			$query = $this->wpdb->prepare( $query, ...$args );
		}
		// Convert string constants to actual constants.
		if ( 'ARRAY_A' === $output ) {
			$output = ARRAY_A;
		} elseif ( 'ARRAY_N' === $output ) {
			$output = ARRAY_N;
		}
		$result = $this->wpdb->get_row( $query, $output );
		return $result ?: null;
	}

	/**
	 * Get a column.
	 *
	 * @param string $query SQL query.
	 * @param array<int|string, mixed> $args Query arguments.
	 * @return array<int, mixed> Column values.
	 */
	public function get_col( string $query, array $args = array() ): array {
		if ( ! empty( $args ) ) {
			$query = $this->wpdb->prepare( $query, ...$args );
		}
		$result = $this->wpdb->get_col( $query );
		return $result ?: array();
	}

	/**
	 * Get results.
	 *
	 * @param string $query SQL query.
	 * @param array<int|string, mixed> $args Query arguments.
	 * @param string $output Output format (OBJECT, ARRAY_A, ARRAY_N).
	 * @return array<int, object|array<string|int, mixed>> Results.
	 */
	public function get_results( string $query, array $args = array(), string $output = 'OBJECT' ): array {
		if ( ! empty( $args ) ) {
			$query = $this->wpdb->prepare( $query, ...$args );
		}
		// Convert string constants to actual constants.
		if ( 'ARRAY_A' === $output ) {
			$output = ARRAY_A;
		} elseif ( 'ARRAY_N' === $output ) {
			$output = ARRAY_N;
		}
		$result = $this->wpdb->get_results( $query, $output );
		return $result ?: array();
	}

	/**
	 * Insert a row.
	 *
	 * @param string $table Table name.
	 * @param array<string, mixed> $data Data to insert.
	 * @param array<string, string>|null $format Format array.
	 * @return int|false Insert ID or false on error.
	 */
	public function insert( string $table, array $data, ?array $format = null ) {
		$table = $this->get_table_name( $table );
		$result = $this->wpdb->insert( $table, $data, $format );
		return false !== $result ? $this->wpdb->insert_id : false;
	}

	/**
	 * Update rows.
	 *
	 * @param string $table Table name.
	 * @param array<string, mixed> $data Data to update.
	 * @param array<string, mixed> $where Where clause.
	 * @param array<string, string>|null $format Format array.
	 * @param array<string, string>|null $where_format Where format array.
	 * @return int|false Number of rows updated or false on error.
	 */
	public function update( string $table, array $data, array $where, ?array $format = null, ?array $where_format = null ) {
		$table = $this->get_table_name( $table );
		return $this->wpdb->update( $table, $data, $where, $format, $where_format );
	}

	/**
	 * Delete rows.
	 *
	 * @param string $table Table name.
	 * @param array<string, mixed> $where Where clause.
	 * @param array<string, string>|null $where_format Where format array.
	 * @return int|false Number of rows deleted or false on error.
	 */
	public function delete( string $table, array $where, ?array $where_format = null ) {
		$table = $this->get_table_name( $table );
		return $this->wpdb->delete( $table, $where, $where_format );
	}

	/**
	 * Get table name with prefix.
	 *
	 * @param string $table Table name.
	 * @return string Full table name.
	 */
	public function get_table_name( string $table ): string {
		return $this->wpdb->prefix . $table;
	}
}






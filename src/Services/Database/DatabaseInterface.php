<?php
/**
 * Database interface.
 *
 * @package FP\Privacy\Services\Database
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Services\Database;

/**
 * Database interface for database operations.
 */
interface DatabaseInterface {
	/**
	 * Execute a query.
	 *
	 * @param string $query SQL query.
	 * @param array<int|string, mixed> $args Query arguments.
	 * @return int|false Number of rows affected or false on error.
	 */
	public function query( string $query, array $args = array() );

	/**
	 * Get a single variable.
	 *
	 * @param string $query SQL query.
	 * @param array<int|string, mixed> $args Query arguments.
	 * @return mixed|null Variable value or null.
	 */
	public function get_var( string $query, array $args = array() );

	/**
	 * Get a single row.
	 *
	 * @param string $query SQL query.
	 * @param array<int|string, mixed> $args Query arguments.
	 * @param string $output Output format (OBJECT, ARRAY_A, ARRAY_N).
	 * @return object|array<string|int, mixed>|null Row or null.
	 */
	public function get_row( string $query, array $args = array(), string $output = 'OBJECT' );

	/**
	 * Get a column.
	 *
	 * @param string $query SQL query.
	 * @param array<int|string, mixed> $args Query arguments.
	 * @return array<int, mixed> Column values.
	 */
	public function get_col( string $query, array $args = array() ): array;

	/**
	 * Get results.
	 *
	 * @param string $query SQL query.
	 * @param array<int|string, mixed> $args Query arguments.
	 * @param string $output Output format (OBJECT, ARRAY_A, ARRAY_N).
	 * @return array<int, object|array<string|int, mixed>> Results.
	 */
	public function get_results( string $query, array $args = array(), string $output = 'OBJECT' ): array;

	/**
	 * Insert a row.
	 *
	 * @param string $table Table name.
	 * @param array<string, mixed> $data Data to insert.
	 * @param array<string, string>|null $format Format array.
	 * @return int|false Insert ID or false on error.
	 */
	public function insert( string $table, array $data, ?array $format = null );

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
	public function update( string $table, array $data, array $where, ?array $format = null, ?array $where_format = null );

	/**
	 * Delete rows.
	 *
	 * @param string $table Table name.
	 * @param array<string, mixed> $where Where clause.
	 * @param array<string, string>|null $where_format Where format array.
	 * @return int|false Number of rows deleted or false on error.
	 */
	public function delete( string $table, array $where, ?array $where_format = null );

	/**
	 * Get table name with prefix.
	 *
	 * @param string $table Table name.
	 * @return string Full table name.
	 */
	public function get_table_name( string $table ): string;
}











<?php
/**
 * Consent repository implementation.
 *
 * @package FP\Privacy\Infrastructure\Database
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Infrastructure\Database;

use FP\Privacy\Consent\LogModel;
use FP\Privacy\Infrastructure\Database\ConsentTable;
use FP\Privacy\Consent\LogModelTable as LegacyLogModelTable;
use FP\Privacy\Domain\Consent\ConsentRepositoryInterface;
use FP\Privacy\Services\Database\DatabaseInterface;

/**
 * Consent repository implementation using LogModel.
 * This bridges the existing LogModel to the new repository interface.
 */
class ConsentRepository implements ConsentRepositoryInterface {
	/**
	 * Log model instance.
	 *
	 * @var LogModel
	 */
	private $log_model;

	/**
	 * Constructor.
	 *
	 * @param LogModel|null $log_model Log model instance (will be created if not provided).
	 */
	public function __construct( ?LogModel $log_model = null ) {
		if ( $log_model ) {
			$this->log_model = $log_model;
		} else {
			// Create LogModel with database service if available.
			global $wpdb;
			$table_name = $wpdb->prefix . 'fp_consent_log';
			// Use new ConsentTable if available, fallback to old LogModelTable.
			if ( class_exists( '\\FP\\Privacy\\Infrastructure\\Database\\ConsentTable' ) ) {
				$table_manager = new ConsentTable( $table_name );
			} else {
				$table_manager = new LegacyLogModelTable( $table_name );
			}
			$this->log_model = new LogModel( $table_manager );
		}
	}

	/**
	 * Store a consent record.
	 *
	 * @param array<string, mixed> $data Consent data.
	 * @return int|false Consent ID or false on failure.
	 */
	public function create( array $data ) {
		$result = $this->log_model->insert( $data );
		if ( ! $result ) {
			return false;
		}
		global $wpdb;
		return (int) $wpdb->insert_id;
	}

	/**
	 * Get a consent record by ID.
	 *
	 * @param int $id Consent ID.
	 * @return array<string, mixed>|null Consent data or null if not found.
	 */
	public function find( int $id ): ?array {
		global $wpdb;
		$table = $this->log_model->get_table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Get consent records matching criteria.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<int, array<string, mixed>> Consent records.
	 */
	public function findMany( array $args = array() ): array {
		// Map repository args to LogModel method.
		// For now, use a simple query - this can be enhanced later.
		return $this->log_model->query( $args );
	}

	/**
	 * Update a consent record.
	 *
	 * @param int $id Consent ID.
	 * @param array<string, mixed> $data Data to update.
	 * @return bool True on success.
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;

		$allowed = array( 'event', 'states', 'lang', 'rev' );
		$filtered = array_intersect_key( $data, array_flip( $allowed ) );

		if ( empty( $filtered ) ) {
			return false;
		}

		$valid_events = array( 'accept_all', 'reject_all', 'consent', 'reset', 'revision_bump', 'consent_revoked', 'consent_withdrawn' );
		if ( isset( $filtered['event'] ) && ! in_array( $filtered['event'], $valid_events, true ) ) {
			return false;
		}

		if ( isset( $filtered['states'] ) && is_array( $filtered['states'] ) ) {
			$filtered['states'] = wp_json_encode( $filtered['states'] );
		}

		if ( isset( $filtered['lang'] ) ) {
			$filtered['lang'] = \sanitize_text_field( $filtered['lang'] );
		}

		if ( isset( $filtered['rev'] ) ) {
			$filtered['rev'] = (int) $filtered['rev'];
		}

		$formats = array();
		foreach ( $filtered as $key => $value ) {
			$formats[] = 'rev' === $key ? '%d' : '%s';
		}

		$result = $wpdb->update(
			$this->log_model->get_table(),
			$filtered,
			array( 'id' => $id ),
			$formats,
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete a consent record.
	 *
	 * @param int $id Consent ID.
	 * @return bool True on success.
	 */
	public function delete( int $id ): bool {
		global $wpdb;

		$result = $wpdb->delete(
			$this->log_model->get_table(),
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete consent records matching criteria.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return int Number of records deleted.
	 */
	public function deleteMany( array $args = array() ): int {
		// If 'days' is provided, use delete_older_than.
		if ( isset( $args['days'] ) ) {
			return $this->log_model->delete_older_than( (int) $args['days'] );
		}
		// For other criteria, we'd need to implement in LogModel.
		// For now, return 0 - this will be implemented when we refactor LogModel.
		return 0;
	}

	/**
	 * Count consent records matching criteria.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return int Count.
	 */
	public function count( array $args = array() ): int {
		return $this->log_model->count( $args );
	}

	/**
	 * Find the most recent consent record for a given consent ID.
	 *
	 * @param string $consent_id Consent identifier.
	 * @return array<string, mixed>|null Consent record or null if not found.
	 */
	public function find_latest_by_consent_id( string $consent_id ): ?array {
		$result = $this->log_model->find_latest_by_consent_id( $consent_id );
		return is_array( $result ) ? $result : null;
	}

	/**
	 * Get the underlying LogModel (for backward compatibility).
	 *
	 * @return LogModel Log model instance.
	 */
	public function getLogModel(): LogModel {
		return $this->log_model;
	}
}


<?php
/**
 * Consent log model.
 *
 * @package FP\Privacy\Consent
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Consent;

use FP\Privacy\Services\Database\DatabaseInterface;
use FP\Privacy\Infrastructure\Database\ConsentTable;

/**
 * Handles persistence for consent log.
 */
class LogModel {
	/**
	 * Table manager.
	 *
	 * @var LogModelTable|ConsentTable
	 */
	private $table_manager;

	/**
	 * Database service (optional, falls back to $wpdb if not provided).
	 *
	 * @var DatabaseInterface|null
	 */
	private $database;

	/**
	 * Constructor.
	 *
	 * @param LogModelTable|ConsentTable|null $table_manager Table manager (optional, will be created if not provided).
	 * @param DatabaseInterface|null $database Database service (optional).
	 */
	public function __construct( $table_manager = null, ?DatabaseInterface $database = null ) {
		$this->database = $database;

		if ( $table_manager ) {
			// Accept both LogModelTable and ConsentTable (they have the same interface)
			if ( ! ( $table_manager instanceof LogModelTable ) && ! ( $table_manager instanceof \FP\Privacy\Infrastructure\Database\ConsentTable ) ) {
				throw new \InvalidArgumentException( 'Table manager must be an instance of LogModelTable or ConsentTable' );
			}
			$this->table_manager = $table_manager;
		} else {
			// Fallback: create table manager (backward compatibility).
			global $wpdb;
			$table_name = $wpdb->prefix . 'fp_consent_log';
			$this->table_manager = new LogModelTable( $table_name, $database );
		}

		// Check if table is ensured (works with both old and new table classes).
		$table_ensured = false;
		if ( class_exists( '\\FP\\Privacy\\Infrastructure\\Database\\ConsentTable' ) ) {
			$table_ensured = \FP\Privacy\Infrastructure\Database\ConsentTable::is_table_ensured();
		} elseif ( class_exists( '\\FP\\Privacy\\Consent\\LogModelTable' ) ) {
			$table_ensured = \FP\Privacy\Consent\LogModelTable::is_table_ensured();
		}

		if ( ! $table_ensured ) {
			$ensured = $this->table_manager->ensure_table_exists();
			if ( $ensured ) {
				if ( class_exists( '\\FP\\Privacy\\Infrastructure\\Database\\ConsentTable' ) ) {
					\FP\Privacy\Infrastructure\Database\ConsentTable::mark_table_ensured();
				} elseif ( class_exists( '\\FP\\Privacy\\Consent\\LogModelTable' ) ) {
					\FP\Privacy\Consent\LogModelTable::mark_table_ensured();
				}
			}
		}
	}

	/**
	 * Maybe create table.
	 *
	 * @return void
	 */
	public function maybe_create_table() {
		$this->table_manager->maybe_create_table();
	}

/**
 * Insert log entry.
 *
 * @param array<string, mixed> $data Data.
 *
 * @return bool
 */
	public function insert( array $data ) {
		$sanitized = LogModelSanitizer::sanitize_insert_data( $data );

		if ( $this->database ) {
			// Use database service.
			$table = $this->table_manager->get_table();
			// Extract table name without prefix for get_table_name.
			global $wpdb;
			$table_without_prefix = str_replace( $wpdb->prefix, '', $table );
			$result = $this->database->insert( $table_without_prefix, $sanitized );
			return false !== $result;
		}

		// Fallback to $wpdb for backward compatibility.
		global $wpdb;
		return (bool) $wpdb->insert( $this->table_manager->get_table(), $sanitized );
	}

    /**
     * Retrieve the latest entry for a consent identifier.
     *
     * @param string $consent_id Consent identifier.
     *
     * @return array<string, mixed>|null
     */
	public function find_latest_by_consent_id( $consent_id ) {
		$consent_id = \substr( \sanitize_text_field( (string) $consent_id ), 0, 64 );

		if ( '' === $consent_id ) {
			return null;
		}

		$table = $this->table_manager->get_table();

		if ( $this->database ) {
			// Use database service.
			$row = $this->database->get_row(
				"SELECT * FROM {$table} WHERE consent_id = %s ORDER BY created_at DESC LIMIT 1",
				array( $consent_id ),
				ARRAY_A
			);
		} else {
			// Fallback to $wpdb for backward compatibility.
			global $wpdb;
			$sql   = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE consent_id = %s ORDER BY created_at DESC LIMIT 1",
				$consent_id
			);
			$row = $wpdb->get_row( $sql, ARRAY_A );
		}

		if ( ! $row ) {
			return null;
		}

		$row['states'] = LogModelSanitizer::ensure_states( $row['states'] );

		return $row;
	}

/**
 * Get paginated entries.
 *
 * @param array<string, mixed> $args Args.
 *
 * @return array<int, array<string, mixed>>
 */
public function query( array $args = array() ) {
global $wpdb;

$defaults = array(
'paged'      => 1,
'per_page'   => 50,
'event'      => '',
'search'     => '',
'from'       => '',
'to'         => '',
);

$args = \wp_parse_args( $args, $defaults );

$where  = 'WHERE 1=1';
$params = array();

if ( $args['event'] ) {
$where   .= ' AND event = %s';
$params[] = $args['event'];
}

if ( $args['search'] ) {
$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
$where   .= ' AND (consent_id LIKE %s OR ua LIKE %s OR lang LIKE %s)';
$params[] = $like;
$params[] = $like;
$params[] = $like;
}

		if ( $args['from'] ) {
			$where   .= ' AND created_at >= %s';
			$params[] = LogModelSanitizer::sanitize_datetime( $args['from'] );
		}

		if ( $args['to'] ) {
			$where   .= ' AND created_at <= %s';
			$params[] = LogModelSanitizer::sanitize_datetime( $args['to'] );
		}

		$limit  = min( max( 1, (int) $args['per_page'] ), 500 );
		$offset = ( max( 1, (int) $args['paged'] ) - 1 ) * $limit;

		$table = $this->table_manager->get_table();
		$sql   = $wpdb->prepare(
			"SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
			array_merge( $params, array( $limit, $offset ) )
		);

		$results = $wpdb->get_results( $sql, ARRAY_A );

		foreach ( $results as &$row ) {
			$row['states'] = LogModelSanitizer::ensure_states( $row['states'] );
		}

		unset( $row );

		return $results;
	}

/**
 * Count entries.
 *
 * @param array<string, mixed> $args Args.
 *
 * @return int
 */
public function count( array $args = array() ) {
global $wpdb;

$defaults = array(
'event'  => '',
'search' => '',
'from'   => '',
'to'     => '',
);
$args = \wp_parse_args( $args, $defaults );

$where  = 'WHERE 1=1';
$params = array();

if ( $args['event'] ) {
$where   .= ' AND event = %s';
$params[] = $args['event'];
}

if ( $args['search'] ) {
$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
$where   .= ' AND (consent_id LIKE %s OR ua LIKE %s OR lang LIKE %s)';
$params[] = $like;
$params[] = $like;
$params[] = $like;
}

		if ( $args['from'] ) {
			$where   .= ' AND created_at >= %s';
			$params[] = LogModelSanitizer::sanitize_datetime( $args['from'] );
		}

		if ( $args['to'] ) {
			$where   .= ' AND created_at <= %s';
			$params[] = LogModelSanitizer::sanitize_datetime( $args['to'] );
		}

		$table = $this->table_manager->get_table();
		$sql   = "SELECT COUNT(*) FROM {$table} {$where}";

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, $params );
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Delete records older than retention days.
	 *
	 * @param int $days Days.
	 *
	 * @return int
	 */
	public function delete_older_than( $days ) {
		global $wpdb;

		$threshold = \gmdate( 'Y-m-d H:i:s', time() - ( (int) $days * DAY_IN_SECONDS ) );
		$table     = $this->table_manager->get_table();

		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $threshold ) );
	}

	/**
	 * Summary per event last 30 days.
	 *
	 * @return array<string, int>
	 */
	public function summary_last_30_days() {
		global $wpdb;

		$from  = \gmdate( 'Y-m-d H:i:s', time() - ( 30 * DAY_IN_SECONDS ) );
		$table = $this->table_manager->get_table();

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT event, COUNT(*) as total FROM {$table} WHERE created_at >= %s GROUP BY event", $from ),
			ARRAY_A
		);

		$summary = array(
			'accept_all'     => 0,
			'reject_all'     => 0,
			'consent'        => 0,
			'reset'          => 0,
			'revision_bump'   => 0,
			'consent_revoked' => 0,
			'consent_withdrawn' => 0,
		);

		foreach ( $rows as $row ) {
			// Only update summary if the event is in the predefined list to prevent unexpected keys
			if ( isset( $summary[ $row['event'] ] ) ) {
				$summary[ $row['event'] ] = (int) $row['total'];
			}
		}

		return $summary;
	}

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public function get_table() {
		return $this->table_manager->get_table();
	}

	/**
	 * Normalize stored states into an array structure.
	 *
	 * @param mixed $states Raw states payload.
	 *
	 * @return array<string, mixed>
	 */
	public function normalize_states( $states ) {
		return LogModelSanitizer::normalize_states( $states );
	}
}

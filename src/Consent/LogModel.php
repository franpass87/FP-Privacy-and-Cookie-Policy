<?php
/**
 * Consent log model.
 *
 * @package FP\Privacy\Consent
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Consent;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

/**
 * Handles persistence for consent log.
 */
class LogModel {
/**
 * Table name cache.
 *
 * @var string
 */
private $table;

/**
 * Constructor.
 */
public function __construct() {
global $wpdb;
$this->table = $wpdb->prefix . 'fp_consent_log';
}

/**
 * Maybe create table.
 *
 * @return void
 */
public function maybe_create_table() {
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
 * Insert log entry.
 *
 * @param array<string, mixed> $data Data.
 *
 * @return bool
 */
public function insert( array $data ) {
global $wpdb;

$defaults = array(
'consent_id' => '',
'event'      => 'consent',
'states'     => '{}',
'ip_hash'    => '',
'ua'         => '',
'lang'       => '',
'rev'        => 1,
'created_at' => \current_time( 'mysql', true ),
);

        $data = \wp_parse_args( $data, $defaults );

        $consent_id = \substr( \sanitize_text_field( $data['consent_id'] ), 0, 64 );
        $encoded    = \wp_json_encode( $this->ensure_states( $data['states'] ) );

        if ( false === $encoded ) {
            $encoded = '{}';
        }

        $data = array(
            'consent_id' => $consent_id,
            'event'      => in_array( $data['event'], array( 'accept_all', 'reject_all', 'consent', 'reset', 'revision_bump' ), true ) ? $data['event'] : 'consent',
            'states'     => $encoded,
            'ip_hash'    => \sanitize_text_field( $data['ip_hash'] ),
            'ua'         => \sanitize_text_field( $data['ua'] ),
            'lang'       => \sanitize_text_field( $data['lang'] ),
            'rev'        => (int) $data['rev'],
            'created_at' => $this->sanitize_datetime( $data['created_at'] ),
        );

        return (bool) $wpdb->insert( $this->table, $data );
    }

    /**
     * Retrieve the latest entry for a consent identifier.
     *
     * @param string $consent_id Consent identifier.
     *
     * @return array<string, mixed>|null
     */
    public function find_latest_by_consent_id( $consent_id ) {
        global $wpdb;

        $consent_id = \substr( \sanitize_text_field( (string) $consent_id ), 0, 64 );

        if ( '' === $consent_id ) {
            return null;
        }

        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE consent_id = %s ORDER BY created_at DESC LIMIT 1",
            $consent_id
        );

        $row = $wpdb->get_row( $sql, ARRAY_A );

        if ( ! $row ) {
            return null;
        }

        $row['states'] = $this->ensure_states( $row['states'] );

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
$params[] = $this->sanitize_datetime( $args['from'] );
}

if ( $args['to'] ) {
$where   .= ' AND created_at <= %s';
$params[] = $this->sanitize_datetime( $args['to'] );
}

$limit  = (int) $args['per_page'];
$offset = ( max( 1, (int) $args['paged'] ) - 1 ) * $limit;

$sql = $wpdb->prepare(
"SELECT * FROM {$this->table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
array_merge( $params, array( $limit, $offset ) )
);

        $results = $wpdb->get_results( $sql, ARRAY_A );

        foreach ( $results as &$row ) {
            $row['states'] = $this->ensure_states( $row['states'] );
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
$params[] = $this->sanitize_datetime( $args['from'] );
}

if ( $args['to'] ) {
$where   .= ' AND created_at <= %s';
$params[] = $this->sanitize_datetime( $args['to'] );
}

$sql = "SELECT COUNT(*) FROM {$this->table} {$where}";

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

return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table} WHERE created_at < %s", $threshold ) );
}

/**
 * Summary per event last 30 days.
 *
 * @return array<string, int>
 */
public function summary_last_30_days() {
global $wpdb;

$from = \gmdate( 'Y-m-d H:i:s', time() - ( 30 * DAY_IN_SECONDS ) );

$rows = $wpdb->get_results(
$wpdb->prepare( "SELECT event, COUNT(*) as total FROM {$this->table} WHERE created_at >= %s GROUP BY event", $from ),
ARRAY_A
);

	$summary = array(
		'accept_all' => 0,
		'reject_all' => 0,
		'consent'    => 0,
		'reset'      => 0,
		'revision_bump' => 0,
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
return $this->table;
}

/**
 * Ensure states array.
 *
 * @param mixed $states States.
 *
 * @return array<string, mixed>
 */
    /**
     * Normalize stored states into an array structure.
     *
     * @param mixed $states Raw states payload.
     *
     * @return array<string, mixed>
     */
    public function normalize_states( $states ) {
        return $this->ensure_states( $states );
    }

    private function ensure_states( $states ) {
        if ( \is_array( $states ) ) {
            return $states;
        }

if ( \is_string( $states ) ) {
$decoded = \json_decode( $states, true );
if ( \is_array( $decoded ) ) {
return $decoded;
}
}

return array();
}

/**
 * Sanitize datetime string.
 *
 * @param string $date Date string.
 *
 * @return string
 */
private function sanitize_datetime( $date ) {
try {
$dt = new DateTimeImmutable( $date, new DateTimeZone( 'UTC' ) );
return $dt->format( 'Y-m-d H:i:s' );
} catch ( Exception $e ) {
return \gmdate( 'Y-m-d H:i:s' );
}
}
}

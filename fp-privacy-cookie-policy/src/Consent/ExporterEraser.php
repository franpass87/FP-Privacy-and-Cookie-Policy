<?php
/**
 * Privacy exporters and erasers integration.
 *
 * @package FP\Privacy\Consent
 */

namespace FP\Privacy\Consent;

use FP\Privacy\Utils\Options;

/**
 * Registers exporters and erasers for GDPR tools.
 */
class ExporterEraser {
/**
 * Log model.
 *
 * @var LogModel
 */
private $log_model;

/**
 * Options handler.
 *
 * @var Options
 */
private $options;

/**
 * Constructor.
 *
 * @param LogModel $log_model Log model.
 * @param Options  $options   Options.
 */
public function __construct( LogModel $log_model, Options $options ) {
$this->log_model = $log_model;
$this->options   = $options;
}

/**
 * Register hooks.
 *
 * @return void
 */
public function hooks() {
\add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
\add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
}

/**
 * Register exporter callback.
 *
 * @param array<string, mixed> $exporters Exporters.
 *
 * @return array<string, mixed>
 */
public function register_exporter( $exporters ) {
$exporters['fp-privacy-consent'] = array(
'exporter_friendly_name' => \__( 'FP Privacy consent log', 'fp-privacy' ),
'callback'               => array( $this, 'export_personal_data' ),
);

return $exporters;
}

/**
 * Register eraser callback.
 *
 * @param array<string, mixed> $erasers Erasers.
 *
 * @return array<string, mixed>
 */
public function register_eraser( $erasers ) {
$erasers['fp-privacy-consent'] = array(
'eraser_friendly_name' => \__( 'FP Privacy consent log', 'fp-privacy' ),
'callback'             => array( $this, 'erase_personal_data' ),
);

return $erasers;
}

/**
 * Export personal data.
 *
 * @param string $email Email or consent id.
 * @param int    $page  Page number.
 *
 * @return array<string, mixed>
 */
public function export_personal_data( $email, $page ) {
global $wpdb;

$page      = max( 1, (int) $page );
$per_page  = 100;
$offset    = ( $page - 1 ) * $per_page;
$consent_id = \sanitize_text_field( $email );

$results = $wpdb->get_results(
$wpdb->prepare(
"SELECT * FROM {$this->log_model->get_table()} WHERE consent_id = %s ORDER BY created_at ASC LIMIT %d OFFSET %d",
$consent_id,
$per_page,
$offset
),
ARRAY_A
);

$data = array();

foreach ( $results as $row ) {
$data[] = array(
'name'  => \__( 'Consent Log Entry', 'fp-privacy' ),
            'value' => \wp_json_encode( array(
                'event'   => $row['event'],
                'lang'    => $row['lang'],
                'rev'     => (int) $row['rev'],
                'time'    => $row['created_at'],
                'states'  => $this->log_model->normalize_states( $row['states'] ),
                'user_agent' => $row['ua'],
            ) ),
        );
}

$done = count( $results ) < $per_page;

return array(
'data' => $data,
'done' => $done,
);
}

/**
 * Erase personal data.
 *
 * @param string $email Email or consent id.
 * @param int    $page  Page number.
 *
 * @return array<string, mixed>
 */
public function erase_personal_data( $email, $page ) {
global $wpdb;

$page       = max( 1, (int) $page );
$per_page   = 100;
$offset     = ( $page - 1 ) * $per_page;
$consent_id = \sanitize_text_field( $email );

$rows = $wpdb->get_results(
$wpdb->prepare(
"SELECT id FROM {$this->log_model->get_table()} WHERE consent_id = %s ORDER BY created_at ASC LIMIT %d OFFSET %d",
$consent_id,
$per_page,
$offset
),
ARRAY_A
);

$ids = \wp_list_pluck( $rows, 'id' );

$removed = 0;
if ( $ids ) {
$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->log_model->get_table()} WHERE id IN ({$placeholders})", $ids ) );
$removed = count( $ids );
}

$done = count( $rows ) < $per_page;

return array(
'items_removed'  => $removed > 0,
'items_retained' => false,
'messages'       => array(),
'done'           => $done,
);
}
}

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
        if ( ! $this->supports_privacy_requests() ) {
            return $exporters;
        }

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
        if ( ! $this->supports_privacy_requests() ) {
            return $erasers;
        }

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
        $consent_ids = $this->resolve_consent_ids( $email );
        $is_email    = \is_email( $email );

        if ( empty( $consent_ids ) ) {
            $errors = array();

            if ( $is_email ) {
                $errors[] = \__( 'Consent logs are not linked to email addresses. Map requests via the fp_privacy_consent_ids_for_email filter before enabling this exporter.', 'fp-privacy' );
            }

            return array(
                'data'   => array(),
                'done'   => true,
                'errors' => $errors,
            );
        }

        $placeholders = implode( ',', array_fill( 0, count( $consent_ids ), '%s' ) );
        $query_args   = array_merge( $consent_ids, array( $per_page, $offset ) );

        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->log_model->get_table()} WHERE consent_id IN ({$placeholders}) ORDER BY created_at ASC LIMIT %d OFFSET %d",
            $query_args
        );

        $results = $wpdb->get_results(
            $sql,
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
            'data'   => $data,
            'done'   => $done,
            'errors' => array(),
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
        $consent_ids = $this->resolve_consent_ids( $email );
        $is_email    = \is_email( $email );

        if ( empty( $consent_ids ) ) {
            $messages = array();
            $retained = false;

            if ( $is_email ) {
                $messages[] = \__( 'Consent logs are stored by consent ID. Provide a mapping via the fp_privacy_consent_ids_for_email filter to erase records for email-based requests.', 'fp-privacy' );
                $retained   = true;
            }

            return array(
                'items_removed'  => false,
                'items_retained' => $retained,
                'messages'       => $messages,
                'done'           => true,
            );
        }

        $placeholders = implode( ',', array_fill( 0, count( $consent_ids ), '%s' ) );
        $query_args   = array_merge( $consent_ids, array( $per_page, $offset ) );

        $sql = $wpdb->prepare(
            "SELECT id FROM {$this->log_model->get_table()} WHERE consent_id IN ({$placeholders}) ORDER BY created_at ASC LIMIT %d OFFSET %d",
            $query_args
        );

        $rows = $wpdb->get_results(
            $sql,
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

    /**
     * Determine whether privacy tools should be registered.
     *
     * @return bool
     */
    private function supports_privacy_requests() {
        $enabled = \apply_filters( 'fp_privacy_enable_privacy_tools', false, $this->options );

        return (bool) $enabled || \has_filter( 'fp_privacy_consent_ids_for_email' );
    }

    /**
     * Resolve one or more consent identifiers for a request value.
     *
     * @param string $value Email address or consent identifier.
     *
     * @return array<int, string>
     */
    private function resolve_consent_ids( $value ) {
        $value = \sanitize_text_field( (string) $value );

        if ( '' === $value ) {
            return array();
        }

        if ( \is_email( $value ) ) {
            $mapped = \apply_filters( 'fp_privacy_consent_ids_for_email', array(), $value, $this->options );

            if ( ! \is_array( $mapped ) ) {
                return array();
            }

            $ids = array();

            foreach ( $mapped as $candidate ) {
                $sanitized = \substr( \sanitize_text_field( (string) $candidate ), 0, 64 );

                if ( '' !== $sanitized ) {
                    $ids[] = $sanitized;
                }
            }

            return array_values( array_unique( $ids ) );
        }

        return array( \substr( $value, 0, 64 ) );
    }
}

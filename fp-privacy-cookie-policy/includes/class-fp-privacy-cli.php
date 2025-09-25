<?php
/**
 * WP-CLI commands for FP Privacy & Cookie Policy.
 *
 * @package FP_Privacy_Cookie_Policy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

/**
 * Manage the consent registry via WP-CLI.
 */
class FP_Privacy_CLI {

    /**
     * Plugin instance.
     *
     * @var FP_Privacy_Cookie_Policy
     */
    protected $plugin;

    /**
     * Constructor.
     *
     * @param FP_Privacy_Cookie_Policy $plugin Plugin instance.
     */
    public function __construct( FP_Privacy_Cookie_Policy $plugin ) {
        $this->plugin = $plugin;
    }

    /**
     * Display the current status of the consent registry and cleanup schedule.
     *
     * ## EXAMPLES
     *
     *     wp fp-privacy status
     *
     * @subcommand status
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function status( $args, $assoc_args ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedParameter
        global $wpdb;

        $table_name = $this->get_consent_table_name( $wpdb );
        $table_exists = $this->table_exists( $wpdb, $table_name );

        if ( $table_exists ) {
            \WP_CLI::success( __( 'Consent log table is operational.', 'fp-privacy-cookie-policy' ) );
            $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
            \WP_CLI::log( sprintf( __( 'Stored consent events: %d', 'fp-privacy-cookie-policy' ), $total ) );
        } else {
            \WP_CLI::warning( __( 'The consent log table is missing. Recreate it from the admin tools before logging consents.', 'fp-privacy-cookie-policy' ) );
        }

        $timestamp = wp_next_scheduled( FP_Privacy_Cookie_Policy::CLEANUP_HOOK );

        if ( $timestamp ) {
            $diff = human_time_diff( time(), $timestamp );
            \WP_CLI::log( sprintf( __( 'Next cleanup runs in %s (scheduled for %s).', 'fp-privacy-cookie-policy' ), $diff, date_i18n( 'Y-m-d H:i:s', $timestamp ) ) );
        } else {
            \WP_CLI::warning( __( 'The consent cleanup event is not scheduled.', 'fp-privacy-cookie-policy' ) );
        }
    }

    /**
     * Manually run the consent log cleanup respecting the configured retention.
     *
     * ## EXAMPLES
     *
     *     wp fp-privacy cleanup
     *
     * @subcommand cleanup
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function cleanup( $args, $assoc_args ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedParameter
        global $wpdb;

        $table_name = $this->get_consent_table_name( $wpdb );

        if ( ! $this->table_exists( $wpdb, $table_name ) ) {
            \WP_CLI::error( __( 'The consent log table could not be found.', 'fp-privacy-cookie-policy' ) );
        }

        $settings       = $this->plugin->get_settings();
        $retention_days = $this->get_effective_retention_days( $settings );

        if ( $retention_days < 1 ) {
            \WP_CLI::warning( __( 'Automatic cleanup is disabled. Set a retention period before running the cleanup.', 'fp-privacy-cookie-policy' ) );
            return;
        }

        $removed = $this->plugin->cleanup_consent_logs( $settings );

        if ( $removed > 0 ) {
            \WP_CLI::success( sprintf( __( 'Cleanup completed. %d entries were removed.', 'fp-privacy-cookie-policy' ), $removed ) );
            return;
        }

        \WP_CLI::log( __( 'No consent entries matched the retention criteria.', 'fp-privacy-cookie-policy' ) );
    }

    /**
     * Recreate the consent log table if missing or when forced.
     *
     * ## OPTIONS
     *
     * [--force]
     * : Recreate the table even if it already exists.
     *
     * ## EXAMPLES
     *
     *     wp fp-privacy recreate
     *
     * @subcommand recreate
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function recreate( $args, $assoc_args ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedParameter
        global $wpdb;

        $table_name   = $this->get_consent_table_name( $wpdb );
        $table_exists = $this->table_exists( $wpdb, $table_name );
        $force        = ! empty( $assoc_args['force'] );

        if ( $table_exists && ! $force ) {
            \WP_CLI::success( __( 'Consent log table is operational.', 'fp-privacy-cookie-policy' ) );
            return;
        }

        FP_Privacy_Cookie_Policy::create_consent_table();

        if ( ! wp_next_scheduled( FP_Privacy_Cookie_Policy::CLEANUP_HOOK ) ) {
            wp_schedule_event( time(), 'daily', FP_Privacy_Cookie_Policy::CLEANUP_HOOK );
        }

        update_option( FP_Privacy_Cookie_Policy::VERSION_OPTION, FP_Privacy_Cookie_Policy::VERSION );

        if ( $this->table_exists( $wpdb, $table_name ) ) {
            \WP_CLI::success( __( 'La tabella del registro consensi è stata ricreata correttamente.', 'fp-privacy-cookie-policy' ) );
            return;
        }

        \WP_CLI::error( __( 'Impossibile creare la tabella del registro consensi. Verifica i permessi del database.', 'fp-privacy-cookie-policy' ) );
    }

    /**
     * Export the consent log to a CSV file.
     *
     * ## OPTIONS
     *
     * [--file=<file>]
     * : Destination path for the CSV export.
     *
     * ## EXAMPLES
     *
     *     wp fp-privacy export --file=consents.csv
     *
     * @subcommand export
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function export( $args, $assoc_args ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedParameter
        global $wpdb;

        $table_name = $this->get_consent_table_name( $wpdb );

        if ( ! $this->table_exists( $wpdb, $table_name ) ) {
            \WP_CLI::error( __( 'The consent log table could not be found.', 'fp-privacy-cookie-policy' ) );
        }

        if ( empty( $assoc_args['file'] ) ) {
            \WP_CLI::error( __( 'You must provide a destination file via the --file parameter.', 'fp-privacy-cookie-policy' ) );
        }

        $file_path = wp_normalize_path( $assoc_args['file'] );
        $directory = dirname( $file_path );

        if ( ! is_dir( $directory ) && ! wp_mkdir_p( $directory ) ) {
            \WP_CLI::error( sprintf( __( 'Unable to create the export directory: %s', 'fp-privacy-cookie-policy' ), $directory ) );
        }

        $handle = @fopen( $file_path, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen

        if ( false === $handle ) {
            \WP_CLI::error( sprintf( __( 'Unable to open %s for writing.', 'fp-privacy-cookie-policy' ), $file_path ) );
        }

        $batch_size = $this->get_export_batch_size();
        fputcsv( $handle, array( 'created_at', 'consent_id', 'user_id', 'event_type', 'consent_state', 'ip_address', 'user_agent' ) );

        $last_id = (int) $wpdb->get_var( "SELECT MAX(id) FROM {$table_name}" );
        $exported = 0;

        while ( $last_id > 0 ) {
            $logs = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table_name} WHERE id <= %d ORDER BY id DESC LIMIT %d",
                    $last_id,
                    $batch_size
                )
            );

            if ( empty( $logs ) ) {
                break;
            }

            foreach ( $logs as $log ) {
                fputcsv(
                    $handle,
                    array(
                        $log->created_at,
                        $log->consent_id,
                        $log->user_id,
                        $log->event_type,
                        $log->consent_state,
                        $log->ip_address,
                        $log->user_agent,
                    )
                );
                $exported++;
            }

            $last_row = end( $logs );
            $last_id  = $last_row ? ( (int) $last_row->id ) - 1 : 0;

            if ( $last_id <= 0 || count( $logs ) < $batch_size ) {
                break;
            }
        }

        fclose( $handle );

        \WP_CLI::success( sprintf( __( 'Export completed. %1$d entries written to %2$s.', 'fp-privacy-cookie-policy' ), $exported, $file_path ) );
    }

    /**
     * Determine the consent table name.
     *
     * @param wpdb $wpdb WordPress database abstraction.
     *
     * @return string
     */
    protected function get_consent_table_name( $wpdb ) {
        return $wpdb->prefix . FP_Privacy_Cookie_Policy::CONSENT_TABLE;
    }

    /**
     * Check whether the consent table exists.
     *
     * @param wpdb   $wpdb       WordPress database abstraction.
     * @param string $table_name Table name.
     *
     * @return bool
     */
    protected function table_exists( $wpdb, $table_name ) {
        return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    /**
     * Retrieve the export batch size, keeping compatibility with the filter used in the admin export.
     *
     * @return int
     */
    protected function get_export_batch_size() {
        $batch_size = (int) apply_filters( 'fp_privacy_csv_export_batch_size', 500 );

        if ( $batch_size < 1 ) {
            $batch_size = 500;
        }

        return $batch_size;
    }

    /**
     * Compute the effective retention period based on the plugin settings.
     *
     * @param array $settings Plugin settings.
     *
     * @return int
     */
    protected function get_effective_retention_days( array $settings ) {
        $retention_days = isset( $settings['retention_days'] ) ? (int) $settings['retention_days'] : 0;
        $retention_days = (int) apply_filters( 'fp_privacy_consent_retention_days', $retention_days, $settings );

        if ( $retention_days < 0 ) {
            $retention_days = 0;
        }

        return $retention_days;
    }
}

<?php
/**
 * Scheduled detector audit and admin notices.
 *
 * @package FP\Privacy\Admin
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Utils\Options;

use const DAY_IN_SECONDS;
use function _n;
use function __;
use function count;
use function esc_html;
use function esc_html__;
use function esc_html_e;
use function esc_url;
use function get_bloginfo;
use function get_option;
use function home_url;
use function implode;
use function is_array;
use function number_format_i18n;
use function sanitize_email;
use function time;
use function wp_json_encode;
use function wp_mail;
use function wp_date;

/**
 * Monitors integration changes and alerts administrators.
 */
class IntegrationAudit {
    const EMAIL_COOLDOWN = DAY_IN_SECONDS;

    /**
     * Options handler.
     *
     * @var Options
     */
    private $options;

    /**
     * Policy generator.
     *
     * @var PolicyGenerator
     */
    private $generator;

    /**
     * Constructor.
     *
     * @param Options         $options   Options handler.
     * @param PolicyGenerator $generator Policy generator.
     */
    public function __construct( Options $options, PolicyGenerator $generator ) {
        $this->options   = $options;
        $this->generator = $generator;
    }

    /**
     * Register hooks.
     *
     * @return void
     */
    public function hooks() {
        \add_action( 'fp_privacy_detector_audit', array( $this, 'run_audit' ) );
        \add_action( 'admin_notices', array( $this, 'render_notice' ) );
        \add_action( 'network_admin_notices', array( $this, 'render_notice' ) );
        \add_action( 'fp_privacy_snapshots_refreshed', array( $this, 'handle_snapshots_refreshed' ) );
    }

    /**
     * Execute scheduled audit and persist alert metadata.
     *
     * @return void
     */
    public function run_audit() {
        $current   = $this->generator->snapshot( true );
        $snapshots = $this->options->get( 'snapshots', array() );
        $previous  = array();

        if ( is_array( $snapshots ) && isset( $snapshots['services']['detected'] ) && is_array( $snapshots['services']['detected'] ) ) {
            $previous = $snapshots['services']['detected'];
        }

        $this->options->prime_script_rules_from_services( $current );
        $diff      = $this->diff_services( $previous, $current );
        $timestamp = time();
        $alert     = $this->options->get_detector_alert();

        if ( empty( $diff['added'] ) && empty( $diff['removed'] ) ) {
            $alert['active']       = false;
            $alert['added']        = array();
            $alert['removed']      = array();
            $alert['detected_at']  = 0;
            $alert['last_checked'] = $timestamp;
            $this->options->set_detector_alert( $alert );

            return;
        }

        $alert['active']       = true;
        $alert['detected_at']  = $timestamp;
        $alert['last_checked'] = $timestamp;
        $alert['added']        = $this->summarize_services( $diff['added'] );
        $alert['removed']      = $this->summarize_services( $diff['removed'] );

        $this->options->set_detector_alert( $alert );
        $this->maybe_send_email_alert( $alert );
    }

    /**
     * Render the integration change notice when required.
     *
     * @return void
     */
    public function render_notice() {
        if ( ! \current_user_can( 'manage_options' ) ) {
            return;
        }

        $alert = $this->options->get_detector_alert();

        if ( empty( $alert['active'] ) ) {
            return;
        }

        $added_count   = isset( $alert['added'] ) && is_array( $alert['added'] ) ? count( $alert['added'] ) : 0;
        $removed_count = isset( $alert['removed'] ) && is_array( $alert['removed'] ) ? count( $alert['removed'] ) : 0;
        $summary_parts = array();

        if ( $added_count ) {
            $summary_parts[] = sprintf(
                _n( '%s new service detected', '%s new services detected', $added_count, 'fp-privacy' ),
                number_format_i18n( $added_count )
            );
        }

        if ( $removed_count ) {
            $summary_parts[] = sprintf(
                _n( '%s service missing', '%s services missing', $removed_count, 'fp-privacy' ),
                number_format_i18n( $removed_count )
            );
        }

        $detected_at = '';
        if ( ! empty( $alert['detected_at'] ) ) {
            $detected_at = \wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $alert['detected_at'] );
        }

        $editor_url = \admin_url( 'admin.php?page=fp-privacy-policy-editor' );
        $added_list = $this->format_services_list( isset( $alert['added'] ) ? $alert['added'] : array() );
        $removed_list = $this->format_services_list( isset( $alert['removed'] ) ? $alert['removed'] : array() );
        ?>
        <div class="notice notice-warning fp-privacy-detector-alert">
            <p>
                <?php
                if ( $detected_at ) {
                    printf(
                        '<strong>%s</strong> %s ',
                        esc_html__( 'Integration changes detected', 'fp-privacy' ),
                        esc_html( sprintf( __( 'on %s', 'fp-privacy' ), $detected_at ) )
                    );
                } else {
                    printf( '<strong>%s</strong> ', esc_html__( 'Integration changes detected', 'fp-privacy' ) );
                }

                if ( ! empty( $summary_parts ) ) {
                    echo esc_html( implode( ' · ', $summary_parts ) );
                }
                ?>
            </p>
            <?php if ( $added_list ) : ?>
                <p><strong><?php esc_html_e( 'New:', 'fp-privacy' ); ?></strong> <?php echo esc_html( $added_list ); ?></p>
            <?php endif; ?>
            <?php if ( $removed_list ) : ?>
                <p><strong><?php esc_html_e( 'Removed:', 'fp-privacy' ); ?></strong> <?php echo esc_html( $removed_list ); ?></p>
            <?php endif; ?>
            <p>
                <a class="button button-primary" href="<?php echo esc_url( $editor_url ); ?>">
                    <?php esc_html_e( 'Review in policy editor', 'fp-privacy' ); ?>
                </a>
                <a class="button" href="<?php echo esc_url( \admin_url( 'admin.php?page=fp-privacy-tools' ) ); ?>">
                    <?php esc_html_e( 'Open tools', 'fp-privacy' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Reset alert metadata once snapshots are refreshed manually.
     *
     * @return void
     */
    public function handle_snapshots_refreshed() {
        $alert                = $this->options->get_default_detector_alert();
        $alert['last_checked'] = time();
        $this->options->set_detector_alert( $alert );
    }

    /**
     * Send email notifications when alerts become active.
     *
     * @param array<string, mixed> $alert Alert payload.
     *
     * @return void
     */
    private function maybe_send_email_alert( array $alert ) {
        $settings = $this->options->get_detector_notifications();

        if ( empty( $settings['email'] ) ) {
            return;
        }

        $now       = time();
        $last_sent = isset( $settings['last_sent'] ) ? (int) $settings['last_sent'] : 0;

        if ( $last_sent && ( $now - $last_sent ) < self::EMAIL_COOLDOWN ) {
            return;
        }

        $recipients = array();

        if ( isset( $settings['recipients'] ) && is_array( $settings['recipients'] ) ) {
            foreach ( $settings['recipients'] as $recipient ) {
                $email = sanitize_email( $recipient );

                if ( '' === $email || in_array( $email, $recipients, true ) ) {
                    continue;
                }

                $recipients[] = $email;
            }
        }

        if ( empty( $recipients ) ) {
            $admin_email = sanitize_email( get_option( 'admin_email' ) );

            if ( '' !== $admin_email ) {
                $recipients[] = $admin_email;
            }
        }

        if ( empty( $recipients ) ) {
            return;
        }

        $site_name = trim( (string) get_bloginfo( 'name' ) );

        if ( '' === $site_name ) {
            $site_name = home_url();
        }

        $subject = sprintf( __( '[%s] Integration changes detected', 'fp-privacy' ), $site_name );

        $lines = array();
        $lines[] = sprintf( __( 'Integration monitoring detected changes on %s.', 'fp-privacy' ), $site_name );

        if ( ! empty( $alert['detected_at'] ) ) {
            $lines[] = sprintf(
                __( 'Detected at: %s', 'fp-privacy' ),
                wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $alert['detected_at'] )
            );
        }

        if ( ! empty( $alert['added'] ) ) {
            $added_block = $this->format_services_for_email( $alert['added'] );

            if ( $added_block ) {
                $lines[] = __( 'New services:', 'fp-privacy' );
                $lines[] = $added_block;
            }
        }

        if ( ! empty( $alert['removed'] ) ) {
            $removed_block = $this->format_services_for_email( $alert['removed'] );

            if ( $removed_block ) {
                $lines[] = __( 'Removed services:', 'fp-privacy' );
                $lines[] = $removed_block;
            }
        }

        $lines[] = sprintf( __( 'Policy editor: %s', 'fp-privacy' ), \admin_url( 'admin.php?page=fp-privacy-policy-editor' ) );
        $lines[] = sprintf( __( 'Tools dashboard: %s', 'fp-privacy' ), \admin_url( 'admin.php?page=fp-privacy-tools' ) );

        $message = implode( "\n\n", array_filter( $lines ) );

        if ( wp_mail( $recipients, $subject, $message ) ) {
            $this->options->update_detector_notifications(
                array(
                    'last_sent' => $now,
                )
            );
        }
    }

    /**
     * Format services summary for email output.
     *
     * @param array<int, array<string, string>> $services Services summary.
     *
     * @return string
     */
    private function format_services_for_email( array $services ) {
        if ( empty( $services ) ) {
            return '';
        }

        $lines = array();

        foreach ( $services as $service ) {
            if ( ! is_array( $service ) ) {
                continue;
            }

            $label = $service['name'] ?: $service['slug'];

            if ( ! empty( $service['provider'] ) ) {
                $label .= ' — ' . $service['provider'];
            }

            if ( ! empty( $service['category'] ) ) {
                $label .= ' [' . $service['category'] . ']';
            }

            $lines[] = '- ' . $label;
        }

        return implode( "\n", $lines );
    }

    /**
     * Compare previous and current detector output.
     *
     * @param array<int, array<string, mixed>> $previous Previous snapshot.
     * @param array<int, array<string, mixed>> $current  Current snapshot.
     *
     * @return array{added:array<int, array<string, mixed>>, removed:array<int, array<string, mixed>>}
     */
    private function diff_services( array $previous, array $current ) {
        $previous_indexed = $this->index_services( $previous );
        $current_indexed  = $this->index_services( $current );

        $added = array();
        foreach ( $current_indexed as $key => $service ) {
            if ( ! isset( $previous_indexed[ $key ] ) ) {
                $added[] = $service;
            }
        }

        $removed = array();
        foreach ( $previous_indexed as $key => $service ) {
            if ( ! isset( $current_indexed[ $key ] ) ) {
                $removed[] = $service;
            }
        }

        return array(
            'added'   => $added,
            'removed' => $removed,
        );
    }

    /**
     * Index services by a stable key for diffing.
     *
     * @param array<int, array<string, mixed>> $services Services list.
     *
     * @return array<string, array<string, mixed>>
     */
    private function index_services( array $services ) {
        $indexed = array();

        foreach ( $services as $service ) {
            if ( ! is_array( $service ) ) {
                continue;
            }

            $slug = isset( $service['slug'] ) ? \sanitize_key( $service['slug'] ) : '';
            $name = isset( $service['name'] ) ? \sanitize_text_field( $service['name'] ) : '';
            $provider = isset( $service['provider'] ) ? \sanitize_text_field( $service['provider'] ) : '';

            $key = $slug;

            if ( '' === $key && '' !== $name ) {
                $key = \sanitize_key( $name . '-' . $provider );
            }

            if ( '' === $key ) {
                $encoded = wp_json_encode( $service );
                $key = \md5( false !== $encoded ? $encoded : serialize( $service ) );
            }

            $indexed[ $key ] = $service;
        }

        return $indexed;
    }

    /**
     * Normalize services for alert summaries.
     *
     * @param array<int, array<string, mixed>> $services Services list.
     *
     * @return array<int, array<string, string>>
     */
    private function summarize_services( array $services ) {
        $summaries = array();

        foreach ( $services as $service ) {
            if ( ! is_array( $service ) ) {
                continue;
            }

            $summaries[] = array(
                'slug'     => \sanitize_key( $service['slug'] ?? '' ),
                'name'     => \sanitize_text_field( $service['name'] ?? '' ),
                'category' => \sanitize_key( $service['category'] ?? '' ),
                'provider' => \sanitize_text_field( $service['provider'] ?? '' ),
            );
        }

        return $summaries;
    }

    /**
     * Format services list for the admin notice.
     *
     * @param array<int, array<string, string>> $services Services summary.
     *
     * @return string
     */
    private function format_services_list( array $services ) {
        if ( empty( $services ) ) {
            return '';
        }

        $entries = array();
        $slice   = array_slice( $services, 0, 3 );

        foreach ( $slice as $service ) {
            $label = $service['name'] ?: $service['slug'];

            if ( $service['provider'] ) {
                $label .= ' — ' . $service['provider'];
            }

            $entries[] = $label;
        }

        if ( count( $services ) > 3 ) {
            $entries[] = sprintf(
                __( 'and %s more', 'fp-privacy' ),
                number_format_i18n( count( $services ) - 3 )
            );
        }

        return implode( ', ', $entries );
    }
}

<?php
/**
 * Consent state manager.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Consent\LogModel;
use FP\Privacy\Utils\Options;
use FP\Privacy\Utils\Validator;
use WP_Error;

/**
 * Handles cookie persistence and logging of consent events.
 */
class ConsentState {
const COOKIE_NAME = 'fp_consent_state_id';

/**
 * Options handler.
 *
 * @var Options
 */
private $options;

/**
 * Log model.
 *
 * @var LogModel
 */
private $log_model;

/**
 * Constructor.
 *
 * @param Options  $options   Options handler.
 * @param LogModel $log_model Log model.
 */
public function __construct( Options $options, LogModel $log_model ) {
$this->options   = $options;
$this->log_model = $log_model;
}

/**
 * Get frontend state for a language.
 *
 * @param string $lang Language.
 *
 * @return array<string, mixed>
 */
    public function get_frontend_state( $lang ) {
        $languages  = $this->options->get_languages();
        $primary    = $languages[0] ?? 'en_US';
        $requested  = Validator::locale( $lang, $primary );
        $normalized = $this->options->normalize_language( $requested );
        $cookie     = $this->get_cookie_payload();
        $revision   = (int) $this->options->get( 'consent_revision', 1 );
        $preview    = (bool) $this->options->get( 'preview_mode', false );
        $needs_consent = $preview || empty( $cookie['id'] ) || ( (int) $cookie['rev'] < $revision );

        $states = array(
            'categories'     => array(),
            'consent_id'     => $cookie['id'],
            'revision'       => $revision,
            'should_display' => $needs_consent,
            'preview_mode'   => $preview,
            'lang'           => $requested,
            'resolved_lang'  => $normalized,
        );

        if ( $cookie['id'] ) {
            $record = $this->log_model->find_latest_by_consent_id( $cookie['id'] );

            if ( $record ) {
                $states['categories']    = isset( $record['states'] ) && is_array( $record['states'] ) ? $this->sanitize_states_payload( $record['states'] ) : array();
                $states['last_event']    = isset( $record['created_at'] ) ? $record['created_at'] : '';
                $states['last_revision'] = isset( $record['rev'] ) ? (int) $record['rev'] : 0;
            }
        }

        $text       = $this->options->get_banner_text( $requested );
        $categories = $this->options->get_categories_for_language( $normalized );
        
        // Get policy page URLs
        $privacy_page_id = $this->options->get_page_id( 'privacy_policy_page_id', $normalized );
        $cookie_page_id = $this->options->get_page_id( 'cookie_policy_page_id', $normalized );
        $privacy_url = $privacy_page_id ? \get_permalink( $privacy_page_id ) : '';
        $cookie_url = $cookie_page_id ? \get_permalink( $cookie_page_id ) : '';

        return array(
            'texts'     => $text,
            'layout'    => $this->options->get( 'banner_layout' ),
            'categories'=> $categories,
            'state'     => $states,
            'mode'      => $this->options->get( 'consent_mode_defaults' ),
            'policy_urls' => array(
                'privacy' => $privacy_url,
                'cookie' => $cookie_url,
            ),
        );
    }

    /**
     * Save consent event.
     *
     * @param string               $event       Event.
     * @param array<string, mixed> $states      States.
     * @param string               $lang        Language.
     * @param string               $consent_id  Optional consent identifier.
     *
     * @return array<string, mixed>|WP_Error
     */
    public function save_event( $event, array $states, $lang, $consent_id = '' ) {
        $preview = (bool) $this->options->get( 'preview_mode', false );
        $cookie  = $this->get_cookie_payload();

        $event  = in_array( $event, array( 'accept_all', 'reject_all', 'consent', 'reset' ), true ) ? $event : 'consent';
        $lang   = $this->options->normalize_language( $lang );
        $states = $this->sanitize_states_payload( $states );
        $states = $this->filter_known_categories( $states, $lang );
        $states = $this->enforce_locked_categories( $states, $lang );

        if ( empty( $cookie['id'] ) ) {
            $provided = \sanitize_text_field( $consent_id );
            $cookie['id'] = '' !== $provided ? $provided : $this->generate_consent_id();
        }

        $cookie['id'] = \substr( \sanitize_text_field( (string) $cookie['id'] ), 0, 64 );

        $revision       = (int) $this->options->get( 'consent_revision', 1 );
        $cookie['rev'] = $revision;

        if ( ! $preview ) {
            $inserted = $this->log_model->insert(
                array(
                    'consent_id' => $cookie['id'],
                    'event'      => $event,
                    'states'     => $states,
                    'ip_hash'    => $this->get_ip_hash(),
                    'ua'         => $this->get_user_agent(),
                    'lang'       => $lang,
                    'rev'        => $revision,
                )
            );

            if ( ! $inserted ) {
                return new WP_Error(
                    'fp_consent_log_failed',
                    \__( 'Unable to store the consent event.', 'fp-privacy' ),
                    array( 'status' => 500 )
                );
            }

            \do_action( 'fp_consent_update', $states, $event, $revision );
            $this->set_cookie( $cookie['id'], $revision );
        }

        // Link consent id to logged-in user for DSAR mapping.
        if ( ! $preview && function_exists( '\is_user_logged_in' ) && \is_user_logged_in() && function_exists( '\get_current_user_id' ) ) {
            $user_id = (int) \get_current_user_id();

            if ( $user_id > 0 && function_exists( '\get_user_meta' ) && function_exists( '\update_user_meta' ) ) {
                $meta_key = 'fp_consent_ids';
                $existing = \get_user_meta( $user_id, $meta_key, true );

                if ( ! \is_array( $existing ) ) {
                    $existing = array();
                }

                $existing[] = (string) $cookie['id'];
                $existing    = array_values( array_unique( array_filter( $existing ) ) );
                \update_user_meta( $user_id, $meta_key, $existing );
            }
        }

        return array(
            'consent_id' => $cookie['id'],
            'revision'   => $revision,
            'preview'    => $preview,
        );
    }

    /**
     * Sanitize the states payload to booleans keyed by safe category slugs.
     *
     * @param array<string, mixed> $states Raw states payload.
     *
     * @return array<string, bool>
     */
    private function sanitize_states_payload( array $states ) {
        $sanitized = array();

        foreach ( $states as $key => $value ) {
            $clean_key = \sanitize_key( $key );

            if ( '' === $clean_key ) {
                continue;
            }

            $sanitized[ $clean_key ] = $this->normalize_boolean( $value );
        }

        return $sanitized;
    }

    /**
     * Filter out unknown consent categories from the payload.
     *
     * @param array<string, bool> $states   Sanitized states.
     * @param string              $language Active language.
     *
     * @return array<string, bool>
     */
    private function filter_known_categories( array $states, $language ) {
        $categories = $this->options->get_categories_for_language( $language );

        if ( empty( $categories ) ) {
            return array();
        }

        $filtered = array();

        foreach ( $states as $slug => $value ) {
            if ( isset( $categories[ $slug ] ) ) {
                $filtered[ $slug ] = (bool) $value;
            }
        }

        return $filtered;
    }

    /**
     * Convert mixed value into a strict boolean following common string conventions.
     *
     * @param mixed $value Raw value.
     *
     * @return bool
     */
    private function normalize_boolean( $value ) {
        if ( \is_bool( $value ) ) {
            return $value;
        }

        if ( \is_numeric( $value ) ) {
            return (bool) (int) $value;
        }

        if ( \is_string( $value ) ) {
            $value = strtolower( trim( $value ) );

            // Support legacy payloads that stored consent states as strings such as
            // "granted"/"denied" in addition to generic truthy tokens.
            $truthy = array( 'true', '1', 'yes', 'on', 'granted', 'allow', 'allowed', 'enabled', 'accept', 'accepted' );
            if ( in_array( $value, $truthy, true ) ) {
                return true;
            }

            $falsy = array( 'false', '0', 'no', 'off', 'denied', 'deny', 'disabled', 'disallow', 'disallowed', 'rejected', 'reject', 'revoked' );
            if ( in_array( $value, $falsy, true ) ) {
                return false;
            }
        }

        return false;
    }

    /**
     * Ensure locked consent categories remain granted.
     *
     * @param array<string, bool> $states   Sanitized states.
     * @param string              $language Active language.
     *
     * @return array<string, bool>
     */
    private function enforce_locked_categories( array $states, $language ) {
        $categories = $this->options->get_categories_for_language( $language );

        foreach ( $categories as $slug => $category ) {
            if ( ! empty( $category['locked'] ) ) {
                $states[ $slug ] = true;
            }
        }

        return $states;
    }

    /**
     * Reset consent state.
     *
     * @return void
     */
    public function reset() {
        $cookie = $this->get_cookie_payload();
        $this->set_cookie( '', 0, time() - HOUR_IN_SECONDS );

        if ( $cookie['id'] ) {
            $this->log_model->insert(
                array(
                    'consent_id' => $cookie['id'],
                    'event'      => 'reset',
                    'ip_hash'    => $this->get_ip_hash(),
                    'ua'         => $this->get_user_agent(),
                    'lang'       => \get_locale(),
                    'rev'        => (int) $this->options->get( 'consent_revision', 1 ),
                )
            );
        }

        \do_action( 'fp_consent_update', array(), 'reset', (int) $this->options->get( 'consent_revision', 1 ) );
    }

/**
 * Get cookie payload.
 *
 * @return array{id:string,rev:int}
 */
private function get_cookie_payload() {
if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
return array(
'id'  => '',
'rev' => 0,
);
}

$value = \sanitize_text_field( \wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );

$parts = explode( '|', $value );

return array(
'id'  => isset( $parts[0] ) ? $parts[0] : '',
'rev' => isset( $parts[1] ) ? (int) $parts[1] : 0,
);
}

/**
 * Set cookie.
 *
 * @param string $id    Consent ID.
 * @param int    $rev   Revision.
 * @param int    $time  Expiration timestamp.
 *
 * @return void
 */
private function set_cookie( $id, $rev, $time = 0 ) {
if ( headers_sent() ) {
return;
}

$days = (int) \apply_filters( 'fp_privacy_cookie_duration_days', 180 );
$days = $days > 0 ? $days : 180;

if ( ! $time ) {
$time = time() + ( $days * DAY_IN_SECONDS );
}

$secure = \is_ssl();
$value  = $id ? $id . '|' . (int) $rev : '';
        $cookie_path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
        $cookie_domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';

        $options = array(
            'expires'  => $time,
            'path'     => $cookie_path,
            'domain'   => $cookie_domain,
            'secure'   => $secure,
            'httponly' => false,
            'samesite' => 'Lax',
        );

        if ( function_exists( '\apply_filters' ) ) {
            $filtered = \apply_filters( 'fp_privacy_cookie_options', $options, $value, $id, $rev );

            if ( is_array( $filtered ) ) {
                $options = array_merge( $options, $filtered );
            }
        }

        \setcookie( self::COOKIE_NAME, $value, $options );

        if ( defined( 'SITECOOKIEPATH' ) ) {
            $site_path = SITECOOKIEPATH ? SITECOOKIEPATH : '/';

            if ( $site_path !== $options['path'] ) {
                $site_options         = $options;
                $site_options['path'] = $site_path;
                \setcookie( self::COOKIE_NAME, $value, $site_options );
            }
        }
    }

/**
 * Generate unique consent identifier.
 *
 * @return string
 */
private function generate_consent_id() {
try {
return bin2hex( random_bytes( 16 ) );
} catch ( \Exception $e ) {
return uniqid( 'fpconsent', true );
}
}

/**
 * Get hashed IP.
 *
 * @return string
 */
private function get_ip_hash() {
$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
$salt = function_exists( '\fp_privacy_get_ip_salt' ) ? \fp_privacy_get_ip_salt() : 'fp-privacy-cookie-policy-salt';

return hash( 'sha256', $ip . '|' . $salt );
}

/**
 * Get user agent.
 *
 * @return string
 */
private function get_user_agent() {
return isset( $_SERVER['HTTP_USER_AGENT'] ) ? \sanitize_text_field( \wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
}
}

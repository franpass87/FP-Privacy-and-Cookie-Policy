<?php
/**
 * Consent state manager.
 *
 * @package FP\Privacy\Frontend
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Consent\LogModel;
use FP\Privacy\Utils\Options;

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
        $lang       = $this->options->normalize_language( $lang );
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
            'lang'           => $lang,
        );

        if ( ! $needs_consent ) {
            $record = $this->log_model->query(
                array(
'paged'    => 1,
'per_page' => 1,
'event'    => 'consent',
'from'     => '',
'to'       => '',
'search'   => $cookie['id'],
)
);
if ( $record ) {
$states['categories'] = $record[0]['states'];
$states['last_event'] = $record[0]['created_at'];
            }
        }

        $text       = $this->options->get_banner_text( $lang );
        $categories = $this->options->get_categories_for_language( $lang );

        return array(
            'texts'     => $text,
            'layout'    => $this->options->get( 'banner_layout' ),
            'categories'=> $categories,
            'state'     => $states,
            'mode'      => $this->options->get( 'consent_mode_defaults' ),
        );
    }

/**
 * Save consent event.
 *
 * @param string               $event  Event.
 * @param array<string, mixed> $states States.
 * @param string               $lang   Language.
 *
 * @return array<string, mixed>
 */
public function save_event( $event, array $states, $lang ) {
$preview = (bool) $this->options->get( 'preview_mode', false );
$cookie  = $this->get_cookie_payload();

if ( empty( $cookie['id'] ) ) {
$cookie['id'] = $this->generate_consent_id();
}

$revision = (int) $this->options->get( 'consent_revision', 1 );
$cookie['rev'] = $revision;

if ( ! $preview ) {
$this->log_model->insert(
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

\do_action( 'fp_consent_update', $states, $event, $revision );
}

$this->set_cookie( $cookie['id'], $revision );

return array(
'consent_id' => $cookie['id'],
'revision'   => $revision,
'preview'    => $preview,
);
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
$options = array(
'expires'  => $time,
'path'     => COOKIEPATH ? COOKIEPATH : '/',
'domain'   => COOKIE_DOMAIN,
'secure'   => $secure,
'httponly' => false,
'samesite' => 'Lax',
);

\setcookie( self::COOKIE_NAME, $value, $options );

if ( COOKIEPATH !== SITECOOKIEPATH ) {
$options['path'] = SITECOOKIEPATH ? SITECOOKIEPATH : '/';
\setcookie( self::COOKIE_NAME, $value, $options );
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
$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
return hash( 'sha256', $ip . '|' . FP_PRIVACY_IP_SALT );
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

<?php
/**
 * REST controller.
 *
 * @package FP\Privacy\REST
 */

namespace FP\Privacy\REST;

use FP\Privacy\Consent\LogModel;
use FP\Privacy\Frontend\ConsentState;
use FP\Privacy\Utils\Options;
use FP\Privacy\Admin\PolicyGenerator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Registers REST API routes.
 */
class Controller {
/**
 * Consent state.
 *
 * @var ConsentState
 */
private $state;

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
 * Log model.
 *
 * @var LogModel
 */
private $log_model;

/**
 * Constructor.
 *
 * @param ConsentState    $state     State handler.
 * @param Options         $options   Options handler.
 * @param PolicyGenerator $generator Generator.
 * @param LogModel        $log_model Log model.
 */
public function __construct( ConsentState $state, Options $options, PolicyGenerator $generator, LogModel $log_model ) {
$this->state     = $state;
$this->options   = $options;
$this->generator = $generator;
$this->log_model = $log_model;
}

/**
 * Hooks.
 *
 * @return void
 */
public function hooks() {
    \add_action( 'rest_api_init', array( $this, 'register_routes' ) );
}

/**
 * Register routes.
 *
 * @return void
 */
public function register_routes() {
        \register_rest_route(
'fp-privacy/v1',
'/consent/summary',
array(
'permission_callback' => function () {
            return \current_user_can( 'manage_options' );
},
'methods'             => 'GET',
'callback'            => array( $this, 'get_summary' ),
)
);

        \register_rest_route(
'fp-privacy/v1',
'/consent',
array(
'permission_callback' => '__return_true',
'methods'             => 'POST',
'callback'            => array( $this, 'post_consent' ),
)
);

        \register_rest_route(
'fp-privacy/v1',
'/revision/bump',
array(
'permission_callback' => function () {
            return \current_user_can( 'manage_options' );
},
'methods'             => 'POST',
'callback'            => array( $this, 'bump_revision' ),
)
);
}

/**
 * Summary endpoint.
 *
 * @return WP_REST_Response
 */
public function get_summary() {
$data = array(
'summary'  => $this->log_model->summary_last_30_days(),
'total'    => $this->log_model->count(),
'options'  => $this->options->all(),
'snapshot' => $this->generator->snapshot(),
);

return new WP_REST_Response( $data, 200 );
}

/**
 * Save consent via REST.
 *
 * @param WP_REST_Request $request Request.
 *
 * @return WP_REST_Response|WP_Error
 */
public function post_consent( WP_REST_Request $request ) {
$nonce = $request->get_header( 'X-WP-Nonce' );
if ( ! $nonce || ! \wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        return new WP_Error( 'fp_privacy_invalid_nonce', \__( 'Invalid security token.', 'fp-privacy' ), array( 'status' => 403 ) );
}

$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : ''; // for rate limiting only.
$salt    = function_exists( '\fp_privacy_get_ip_salt' ) ? \fp_privacy_get_ip_salt() : 'fp-privacy-cookie-policy-salt';
$limit   = 'fp_privacy_rate_' . hash( 'sha256', $ip . '|' . $salt );
$attempt = (int) \get_transient( $limit );
if ( $attempt > 10 ) {
return new WP_Error( 'fp_privacy_rate_limited', \__( 'Too many requests. Please try again later.', 'fp-privacy' ), array( 'status' => 429 ) );
}
\set_transient( $limit, $attempt + 1, MINUTE_IN_SECONDS * 10 );

$event  = \sanitize_text_field( $request->get_param( 'event' ) );
$states = $request->get_param( 'states' );
$lang   = \sanitize_text_field( $request->get_param( 'lang' ) );
$consent_id = $request->get_param( 'consent_id' );
$consent_id = $consent_id ? \sanitize_text_field( $consent_id ) : '';

if ( ! \is_array( $states ) ) {
$states = array();
}

        $result = $this->state->save_event( $event, $states, $lang, $consent_id );

        if ( \is_wp_error( $result ) ) {
            return $result;
        }

        return new WP_REST_Response( $result, 200 );
}

/**
 * Bump revision.
 *
 * @param WP_REST_Request $request Request.
 *
 * @return WP_REST_Response
 */
public function bump_revision( WP_REST_Request $request ) {
$this->options->bump_revision();
$this->options->set( $this->options->all() );

return new WP_REST_Response( array( 'revision' => $this->options->get( 'consent_revision' ) ), 200 );
}
}

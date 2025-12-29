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
	 * State sanitizer.
	 *
	 * @var ConsentStateSanitizer
	 */
	private $sanitizer;

	/**
	 * Constructor.
	 *
	 * @param Options  $options   Options handler.
	 * @param LogModel $log_model Log model.
	 */
	public function __construct( Options $options, LogModel $log_model ) {
		$this->options   = $options;
		$this->log_model = $log_model;
		$this->sanitizer = new ConsentStateSanitizer( $options );
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
        
        // Auto-detect user language if not specified
        if ( empty( $lang ) ) {
            $lang = $this->options->detect_user_language();
        }
        
        $requested  = Validator::locale( $lang, $primary );
        $normalized = $this->options->normalize_language( $requested );
        $cookie     = ConsentCookieManager::get_cookie_payload();
        $revision   = (int) $this->options->get( 'consent_revision', \FP\Privacy\Shared\Constants::CONSENT_REVISION_INITIAL );
        $preview    = (bool) $this->options->get( 'preview_mode', false );
        
        // CORREZIONE: Migliora la logica per determinare se il consenso è necessario
        // Il banner deve essere mostrato solo se:
        // 1. È in modalità preview, OPPURE
        // 2. Non c'è un cookie ID valido, OPPURE  
        // 3. La revisione del cookie è inferiore alla revisione corrente
        $needs_consent = $preview || empty( $cookie['id'] ) || ( (int) $cookie['rev'] < $revision );
        
        // Debug logging per aiutare a identificare problemi
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $this->options->is_debug_enabled() ) {
            error_log( 'FP Privacy Debug - Cookie ID: ' . $cookie['id'] . ', Revision: ' . $cookie['rev'] . ', Current Revision: ' . $revision . ', Needs Consent: ' . ( $needs_consent ? 'true' : 'false' ) );
        }

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
                $states['categories']    = isset( $record['states'] ) && is_array( $record['states'] ) ? $this->sanitizer->sanitize_states_payload( $record['states'] ) : array();
                $states['last_event']    = isset( $record['created_at'] ) ? $record['created_at'] : '';
                $states['last_revision'] = isset( $record['rev'] ) ? (int) $record['rev'] : 0;
            }
        }

        $text       = $this->options->get_banner_text( $requested );
        $categories = $this->options->get_categories_for_language( $normalized );
        
        // Get policy page URLs
        // NOTE: Options::get_page_id expects 'privacy_policy' or 'cookie_policy' as type.
        // Passing the *_page_id keys would cause both to resolve to the cookie policy.
        $privacy_page_id = $this->options->get_page_id( 'privacy_policy', $normalized );
        $cookie_page_id  = $this->options->get_page_id( 'cookie_policy', $normalized );
        
        // Ensure we have valid page IDs and they are different
        $privacy_url = '';
        $cookie_url = '';
        
        if ( $privacy_page_id && $privacy_page_id > 0 ) {
            $privacy_permalink = \get_permalink( $privacy_page_id );
            if ( $privacy_permalink && ! \is_wp_error( $privacy_permalink ) ) {
                $privacy_url = $privacy_permalink;
            }
        }
        
        if ( $cookie_page_id && $cookie_page_id > 0 && $cookie_page_id !== $privacy_page_id ) {
            $cookie_permalink = \get_permalink( $cookie_page_id );
            if ( $cookie_permalink && ! \is_wp_error( $cookie_permalink ) ) {
                $cookie_url = $cookie_permalink;
            }
        }
        
        // Debug: Log the page IDs and URLs to help identify the issue
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $this->options->is_debug_enabled() ) {
            error_log( 'FP Privacy Debug - Privacy Page ID: ' . $privacy_page_id . ', URL: ' . $privacy_url );
            error_log( 'FP Privacy Debug - Cookie Page ID: ' . $cookie_page_id . ', URL: ' . $cookie_url );
        }

        // Get banner layout as value object and convert to array for frontend compatibility.
        $banner_layout = $this->options->get_banner_layout();
        
        return array(
            'texts'     => $text,
            'layout'    => $banner_layout->to_array(),
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
        $cookie  = ConsentCookieManager::get_cookie_payload();

        $event  = in_array( $event, array( 'accept_all', 'reject_all', 'consent', 'reset', 'consent_revoked', 'consent_withdrawn' ), true ) ? $event : 'consent';
        $lang   = $this->options->normalize_language( $lang );
        $states = $this->sanitizer->sanitize_states_payload( $states );
        $states = $this->sanitizer->filter_known_categories( $states, $lang );
        $states = $this->sanitizer->enforce_locked_categories( $states, $lang );

        if ( empty( $cookie['id'] ) ) {
            $provided = \sanitize_text_field( $consent_id );
            $cookie['id'] = '' !== $provided ? $provided : ConsentCookieManager::generate_consent_id();
        }

        $cookie['id'] = \substr( \sanitize_text_field( (string) $cookie['id'] ), 0, 64 );

        $revision       = (int) $this->options->get( 'consent_revision', \FP\Privacy\Shared\Constants::CONSENT_REVISION_INITIAL );
        $cookie['rev'] = $revision;

        if ( ! $preview ) {
            $inserted = $this->log_model->insert(
                array(
                    'consent_id' => $cookie['id'],
                    'event'      => $event,
                    'states'     => $states,
                    'ip_hash'    => ConsentCookieManager::get_ip_hash(),
                    'ua'         => ConsentCookieManager::get_user_agent(),
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
            ConsentCookieManager::set_cookie( $cookie['id'], $revision );
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
     * Reset consent state.
     *
     * @return void
     */
    public function reset() {
        $cookie = ConsentCookieManager::get_cookie_payload();
        ConsentCookieManager::set_cookie( '', 0, time() - HOUR_IN_SECONDS );

        if ( $cookie['id'] ) {
            $this->log_model->insert(
                array(
                    'consent_id' => $cookie['id'],
                    'event'      => 'reset',
                    'ip_hash'    => ConsentCookieManager::get_ip_hash(),
                    'ua'         => ConsentCookieManager::get_user_agent(),
                    'lang'       => \get_locale(),
                    'rev'        => (int) $this->options->get( 'consent_revision', \FP\Privacy\Shared\Constants::CONSENT_REVISION_INITIAL ),
                )
            );
        }

        \do_action( 'fp_consent_update', array(), 'reset', (int) $this->options->get( 'consent_revision', \FP\Privacy\Shared\Constants::CONSENT_REVISION_INITIAL ) );
    }
}

<?php
/**
 * Service detector.
 *
 * @package FP\Privacy\Integrations
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Integrations;

/**
 * Executes service detection logic.
 */
class ServiceDetector {
	/**
	 * Service registry reference.
	 *
	 * @var ServiceRegistry|\FP\Privacy\Domain\Services\ServiceRegistry
	 */
	private $service_registry;

	/**
	 * Constructor.
	 *
	 * @param ServiceRegistry|\FP\Privacy\Domain\Services\ServiceRegistry $service_registry Service registry.
	 */
	public function __construct( $service_registry ) {
		// Accept both ServiceRegistry implementations (they have the same interface)
		if ( ! ( $service_registry instanceof ServiceRegistry ) && ! ( $service_registry instanceof \FP\Privacy\Domain\Services\ServiceRegistry ) ) {
			throw new \InvalidArgumentException( 'Service registry must be an instance of FP\Privacy\Integrations\ServiceRegistry or FP\Privacy\Domain\Services\ServiceRegistry' );
		}
		$this->service_registry = $service_registry;
	}

	/**
	 * Execute registry detectors.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function run_detectors() {
		$results  = array();
		$registry = $this->service_registry->get_registry();

		foreach ( $registry as $slug => $service ) {
			$detector = isset( $service['detector'] ) ? $service['detector'] : null;
			$detected = false;

			if ( is_callable( $detector ) ) {
				try {
					// Handle array callables that reference $this
					if ( is_array( $detector ) && isset( $detector[0] ) && $detector[0] instanceof DetectorRegistry ) {
						// This is a method reference to DetectorRegistry, we need to handle it differently
						// For now, we'll skip these and handle them in the main class
						$detected = false;
					} else {
						$detected = (bool) \call_user_func( $detector );
					}
				} catch ( \Throwable $e ) {
					$detected = false;
				}
			}

			unset( $service['detector'] );
			$service['slug']     = $slug;
			$service['detected'] = (bool) $detected;
			$results[]           = $service;
		}

		return $results;
	}

	/**
	 * Detect YouTube embeds across persisted content.
	 *
	 * @return bool
	 */
	public function detect_youtube() {
		return $this->detect_embed(
			array( 'youtube.com', 'youtu.be' ),
			array( 'youtube' ),
			array( 'core-embed/youtube' )
		);
	}

	/**
	 * Detect Vimeo embeds across persisted content.
	 *
	 * @return bool
	 */
	public function detect_vimeo() {
		return $this->detect_embed(
			array( 'vimeo.com' ),
			array( 'vimeo' ),
			array( 'core-embed/vimeo' )
		);
	}

	/**
	 * Detect Wistia embeds across persisted content.
	 *
	 * @return bool
	 */
	public function detect_wistia() {
		return $this->detect_embed(
			array( 'wistia.com', 'wistia.net', 'wi.st' ),
			array( 'wistia' ),
			array()
		);
	}

	/**
	 * Detect Vidyard embeds across persisted content.
	 *
	 * @return bool
	 */
	public function detect_vidyard() {
		return $this->detect_embed(
			array( 'vidyard.com', 'play.vidyard.com' ),
			array( 'vidyard' ),
			array()
		);
	}

	/**
	 * Detect Instagram embeds across persisted content.
	 *
	 * @return bool
	 */
	public function detect_instagram() {
		return $this->detect_embed(
			array( 'instagram.com', 'instagr.am' ),
			array( 'instagram' ),
			array( 'core-embed/instagram' )
		);
	}

	/**
	 * Detect Twitter/X embeds across persisted content.
	 *
	 * @return bool
	 */
	public function detect_twitter_embed() {
		return $this->detect_embed(
			array( 'twitter.com', 'x.com', 't.co' ),
			array( 'twitter', 'tweet' ),
			array( 'core-embed/twitter' )
		);
	}

	/**
	 * Detect Spotify embeds across persisted content.
	 *
	 * @return bool
	 */
	public function detect_spotify() {
		return $this->detect_embed(
			array( 'open.spotify.com', 'spotify.com/embed' ),
			array( 'spotify' ),
			array( 'core-embed/spotify' )
		);
	}

	/**
	 * Detect SoundCloud embeds across persisted content.
	 *
	 * @return bool
	 */
	public function detect_soundcloud() {
		return $this->detect_embed(
			array( 'soundcloud.com', 'w.soundcloud.com' ),
			array( 'soundcloud' ),
			array( 'core-embed/soundcloud' )
		);
	}

	/**
	 * Detect Typeform embeds across persisted content.
	 *
	 * @return bool
	 */
	public function detect_typeform() {
		return $this->detect_embed(
			array( 'typeform.com/to/', 'form.typeform.com' ),
			array( 'typeform' ),
			array()
		);
	}

	/**
	 * Detect SurveyMonkey embeds across persisted content.
	 *
	 * @return bool
	 */
	public function detect_surveymonkey() {
		return $this->detect_embed(
			array( 'surveymonkey.com', 'www.surveymonkey.com/r/' ),
			array( 'surveymonkey' ),
			array()
		);
	}

	/**
	 * Detect Google Forms embeds across persisted content.
	 *
	 * @return bool
	 */
	public function detect_google_forms() {
		return $this->detect_embed(
			array( 'docs.google.com/forms' ),
			array(),
			array()
		);
	}

	/**
	 * Detect JotForm embeds across persisted content.
	 *
	 * @return bool
	 */
	public function detect_jotform() {
		return $this->detect_embed(
			array( 'jotform.com', 'form.jotform.com' ),
			array( 'jotform' ),
			array()
		);
	}

	/**
	 * Detect Calendly embeds across persisted content.
	 *
	 * @return bool
	 */
	public function detect_calendly() {
		return $this->detect_embed(
			array( 'calendly.com' ),
			array( 'calendly' ),
			array()
		);
	}

	/**
	 * Detect Acuity Scheduling embeds across persisted content.
	 *
	 * @return bool
	 */
	public function detect_acuity() {
		return $this->detect_embed(
			array( 'acuityscheduling.com' ),
			array( 'acuity' ),
			array()
		);
	}

	/**
	 * Detect Cal.com embeds across persisted content.
	 *
	 * @return bool
	 */
	public function detect_cal_com() {
		return $this->detect_embed(
			array( 'cal.com' ),
			array( 'cal' ),
			array()
		);
	}

	/**
	 * Detect embeds by scanning current and persisted content when necessary.
	 *
	 * @param array<int, string> $strings   Raw string needles.
	 * @param array<int, string> $shortcodes Shortcodes to inspect.
	 * @param array<int, string> $blocks    Gutenberg block slugs.
	 *
	 * @return bool
	 */
	private function detect_embed( array $strings, array $shortcodes = array(), array $blocks = array() ) {
		$post_id = function_exists( '\get_the_ID' ) ? \get_the_ID() : 0;

		if ( $post_id ) {
			$content = (string) \get_post_field( 'post_content', $post_id );

			if ( $this->content_matches_patterns( $content, $strings, $shortcodes, $blocks ) ) {
				return true;
			}
		}

		$doing_ajax = function_exists( '\wp_doing_ajax' ) ? \wp_doing_ajax() : ( defined( 'DOING_AJAX' ) && DOING_AJAX );
		$doing_cron = function_exists( '\wp_doing_cron' ) ? \wp_doing_cron() : ( defined( 'DOING_CRON' ) && DOING_CRON );
		$doing_rest = defined( 'REST_REQUEST' ) && REST_REQUEST;

		if ( function_exists( '\is_admin' ) && ! \is_admin() && ! defined( 'WP_CLI' ) && ! $doing_ajax && ! $doing_cron && ! $doing_rest ) {
			return false;
		}

		if ( ! class_exists( '\WP_Query' ) ) {
			return false;
		}

		$query = new \WP_Query(
			array(
				'post_type'      => 'any',
				'post_status'    => 'publish',
				'posts_per_page' => 10,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		if ( empty( $query->posts ) ) {
			return false;
		}

		foreach ( $query->posts as $id ) {
			$content = (string) \get_post_field( 'post_content', $id );

			if ( $this->content_matches_patterns( $content, $strings, $shortcodes, $blocks ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine whether content contains the provided patterns.
	 *
	 * @param string              $content    Post content.
	 * @param array<int, string>  $strings    Raw string needles.
	 * @param array<int, string>  $shortcodes Shortcode tags.
	 * @param array<int, string>  $blocks     Block slugs.
	 *
	 * @return bool
	 */
	private function content_matches_patterns( $content, array $strings, array $shortcodes, array $blocks ) {
		if ( '' === trim( (string) $content ) ) {
			return false;
		}

		foreach ( $strings as $needle ) {
			if ( '' !== $needle && false !== \stripos( $content, $needle ) ) {
				return true;
			}
		}

		if ( function_exists( '\has_shortcode' ) ) {
			foreach ( $shortcodes as $shortcode ) {
				if ( '' !== $shortcode && \has_shortcode( $content, $shortcode ) ) {
					return true;
				}
			}
		}

		if ( function_exists( '\has_block' ) ) {
			foreach ( $blocks as $block ) {
				if ( '' !== $block && \has_block( $block, $content ) ) {
					return true;
				}
			}
		}

		return false;
	}
}

















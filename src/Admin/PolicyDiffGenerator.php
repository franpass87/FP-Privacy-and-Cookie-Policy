<?php
/**
 * Policy diff generator.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Utils\Options;

/**
 * Handles generation of policy diff previews.
 */
class PolicyDiffGenerator {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Generator.
	 *
	 * @var PolicyGenerator
	 */
	private $generator;

	/**
	 * Constructor.
	 *
	 * @param Options         $options   Options handler.
	 * @param PolicyGenerator $generator Generator.
	 */
	public function __construct( Options $options, PolicyGenerator $generator ) {
		$this->options   = $options;
		$this->generator = $generator;
	}

	/**
	 * Diff preview.
	 *
	 * @param array<int, string>        $languages     Active languages.
	 * @param array<string, \WP_Post?> $privacy_posts Privacy posts keyed by language.
	 * @param array<string, \WP_Post?> $cookie_posts  Cookie posts keyed by language.
	 *
	 * @return string
	 */
	public function get_diff_preview( array $languages, array $privacy_posts, array $cookie_posts ) {
		$output = '';

		foreach ( $languages as $language ) {
			$language     = $this->options->normalize_language( $language );
			$privacy_post = isset( $privacy_posts[ $language ] ) ? $privacy_posts[ $language ] : null;
			$cookie_post  = isset( $cookie_posts[ $language ] ) ? $cookie_posts[ $language ] : null;

			$generated_privacy = $this->generator->generate_privacy_policy( $language );
			$generated_cookie  = $this->generator->generate_cookie_policy( $language );

			$privacy_current = $privacy_post ? $privacy_post->post_content : '';
			$cookie_current  = $cookie_post ? $cookie_post->post_content : '';

			$privacy_diff = \wp_text_diff( $privacy_current, $generated_privacy, array( 'title' => \sprintf( \__( 'Privacy policy diff (%s)', 'fp-privacy' ), $language ) ) );
			$cookie_diff  = \wp_text_diff( $cookie_current, $generated_cookie, array( 'title' => \sprintf( \__( 'Cookie policy diff (%s)', 'fp-privacy' ), $language ) ) );

			$output .= $privacy_diff . $cookie_diff;
		}

		return $output;
	}
}
















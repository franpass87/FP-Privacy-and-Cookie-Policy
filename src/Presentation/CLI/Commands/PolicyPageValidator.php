<?php
/**
 * Policy page validator.
 *
 * @package FP\Privacy\CLI
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\CLI\Commands;

use FP\Privacy\Utils\Options;
use WP_CLI;

/**
 * Handles validation of policy pages before generation.
 */
class PolicyPageValidator {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options handler.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Check if pages can be updated (not manually modified).
	 *
	 * @param int  $privacy_id Privacy policy page ID.
	 * @param int  $cookie_id  Cookie policy page ID.
	 * @param bool $force      Force update even if manually modified.
	 *
	 * @return bool True if pages can be updated.
	 */
	public function can_update_pages( $privacy_id, $cookie_id, $force = false ) {
		if ( $force ) {
			return true;
		}

		$privacy_post = \get_post( $privacy_id );
		$cookie_post  = \get_post( $cookie_id );

		if ( ! $privacy_post || ! $cookie_post ) {
			return true;
		}

		$privacy_managed = \get_post_meta( $privacy_id, Options::PAGE_MANAGED_META_KEY, true );
		$cookie_managed  = \get_post_meta( $cookie_id, Options::PAGE_MANAGED_META_KEY, true );

		$privacy_content = trim( (string) $privacy_post->post_content );
		$cookie_content  = trim( (string) $cookie_post->post_content );

		$privacy_is_shortcode = false !== strpos( $privacy_content, '[fp_privacy_policy' );
		$cookie_is_shortcode  = false !== strpos( $cookie_content, '[fp_cookie_policy' );

		if ( ! $privacy_is_shortcode && ! $privacy_managed && strlen( $privacy_content ) > 100 ) {
			WP_CLI::warning( '  Privacy Policy sembra modificata manualmente. Usa --force per sovrascrivere.' );
			return false;
		}

		if ( ! $cookie_is_shortcode && ! $cookie_managed && strlen( $cookie_content ) > 100 ) {
			WP_CLI::warning( '  Cookie Policy sembra modificata manualmente. Usa --force per sovrascrivere.' );
			return false;
		}

		return true;
	}
}
















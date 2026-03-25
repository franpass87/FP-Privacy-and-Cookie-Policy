<?php
declare( strict_types=1 );

/**
 * Renders Privacy Policy | Cookie Policy links in the site footer.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Utils\Options;

/**
 * Injects policy links at the bottom of all frontend pages.
 */
class FooterPolicyLinks {

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
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		\add_action( 'wp_footer', array( $this, 'render' ), 999 );
	}

	/**
	 * Render Privacy Policy | Cookie Policy links in the footer.
	 *
	 * Outputs a minimal block at the bottom of the page, after all content.
	 * Only renders if the feature is enabled and at least one policy URL exists.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! (bool) $this->options->get( 'footer_policy_links_enabled', true ) ) {
			return;
		}

		// Skip in admin, login, REST, AJAX, cron.
		if ( \is_admin() || \wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		$lang  = $this->options->detect_user_language();
		$norm  = $this->options->normalize_language( $lang );
		$texts = $this->options->get_banner_text( $lang );

		$privacy_page_id = $this->options->get_page_id( 'privacy_policy', $norm );
		$cookie_page_id  = $this->options->get_page_id( 'cookie_policy', $norm );

		// Fallback to WordPress native privacy page if FP Privacy pages not configured.
		if ( ! $privacy_page_id || $privacy_page_id <= 0 ) {
			$privacy_page_id = (int) \get_option( 'wp_page_for_privacy_policy', 0 );
		}

		$privacy_url = '';
		$cookie_url  = '';

		if ( $privacy_page_id && $privacy_page_id > 0 ) {
			$permalink = \get_permalink( $privacy_page_id );
			if ( $permalink ) {
				$privacy_url = $permalink;
			}
		}

		if ( $cookie_page_id && $cookie_page_id > 0 && $cookie_page_id !== $privacy_page_id ) {
			$permalink = \get_permalink( $cookie_page_id );
			if ( $permalink ) {
				$cookie_url = $permalink;
			}
		}

		// If both point to same page, show only one link.
		if ( $privacy_page_id === $cookie_page_id && $privacy_url ) {
			$cookie_url = '';
		}

		if ( ! $privacy_url && ! $cookie_url ) {
			return;
		}

		$privacy_label = $texts['link_privacy_policy'] ?? \__( 'Privacy Policy', 'fp-privacy' );
		$cookie_label  = $texts['link_cookie_policy'] ?? \__( 'Cookie Policy', 'fp-privacy' );

		$links = array();
		if ( $privacy_url ) {
			$links[] = '<a href="' . \esc_url( $privacy_url ) . '" class="fp-privacy-footer-link">' . \esc_html( $privacy_label ) . '</a>';
		}
		if ( $cookie_url ) {
			$links[] = '<a href="' . \esc_url( $cookie_url ) . '" class="fp-privacy-footer-link">' . \esc_html( $cookie_label ) . '</a>';
		}

		$separator   = ' <span class="fp-privacy-footer-sep" aria-hidden="true">|</span> ';
		$block_style = 'margin:12px 0;padding:8px 0;text-align:center;font-size:0.8125rem;color:#6b7280;';
		$html        = '<div class="fp-privacy-footer-links" role="contentinfo" style="' . \esc_attr( $block_style ) . '">' . implode( $separator, $links ) . '</div>';

		/**
		 * Filter the footer policy links HTML.
		 *
		 * @param string $html Rendered HTML.
		 * @param array  $links Array of link HTML strings.
		 */
		$html = \apply_filters( 'fp_privacy_footer_links_html', $html, $links );

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above, filter may add markup
	}
}

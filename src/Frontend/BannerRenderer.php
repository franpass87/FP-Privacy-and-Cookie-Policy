<?php
/**
 * Banner renderer.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

/**
 * Handles banner rendering.
 */
class BannerRenderer {
	/**
	 * Tracks whether the banner markup has been rendered.
	 *
	 * @var bool
	 */
	private $rendered = false;

	/**
	 * Render banner container.
	 *
	 * @return void
	 */
	public function render_banner() {
		if ( $this->rendered ) {
			return;
		}

		$this->rendered = true;

		echo '<div id="fp-privacy-banner-root" aria-live="polite"></div>';
	}
}
















<?php
/**
 * Block registration.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Utils\Options;

/**
 * Registers Gutenberg blocks.
 */
class Blocks {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Block registry.
	 *
	 * @var BlockRegistry
	 */
	private $registry;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options.
	 */
	public function __construct( Options $options ) {
		$this->options  = $options;
		$this->registry = new BlockRegistry( $options );
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		\add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register blocks.
	 *
	 * @return void
	 */
	public function register_blocks() {
		$blocks = array(
			'privacy-policy'     => array( BlockRenderer::class, 'render_privacy_policy_block' ),
			'cookie-policy'      => array( BlockRenderer::class, 'render_cookie_policy_block' ),
			'cookie-preferences' => array( BlockRenderer::class, 'render_preferences_block' ),
			'cookie-banner'      => array( BlockRenderer::class, 'render_banner_block' ),
		);

		$this->registry->register_blocks( $blocks );
	}
}

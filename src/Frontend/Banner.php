<?php
/**
 * Frontend banner.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Utils\Options;

/**
 * Handles banner rendering and assets.
 */
class Banner {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Consent state.
	 *
	 * @var ConsentState
	 */
	private $state;

	/**
	 * Asset manager.
	 *
	 * @var BannerAssetManager
	 */
	private $asset_manager;

	/**
	 * Renderer.
	 *
	 * @var BannerRenderer
	 */
	private $renderer;

	/**
	 * Constructor.
	 *
	 * @param Options      $options Options.
	 * @param ConsentState $state   Consent state.
	 */
	public function __construct( Options $options, ConsentState $state ) {
		$this->options = $options;
		$this->state   = $state;

		$palette_builder = new BannerPaletteBuilder();
		$this->asset_manager = new BannerAssetManager( $options, $state, $palette_builder );
		$this->renderer      = new BannerRenderer();
	}

/**
 * Hooks.
 *
 * @return void
 */
    public function hooks() {
        \add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        \add_action( 'fp_privacy_enqueue_banner_assets', array( $this, 'enqueue_assets_forced' ), 10, 1 );
        \add_action( 'wp_body_open', array( $this, 'render_banner' ), 20 );
        
        // Compatibilità con tema Salient (usa hook personalizzato invece di wp_body_open)
        \add_action( 'nectar_hook_after_body_open', array( $this, 'render_banner' ), 20 );
        
        \add_action( 'wp_footer', array( $this, 'render_banner' ), 5 );
    }

	/**
	 * Enqueue assets when necessary.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$this->asset_manager->maybe_enqueue_assets();
	}

	/**
	 * Enqueue assets when a shortcode renders after wp_enqueue_scripts.
	 *
	 * @param string $lang Language override.
	 *
	 * @return void
	 */
	public function enqueue_assets_forced( $lang = '' ) {
		$this->asset_manager->maybe_enqueue_assets( $lang );
	}

	/**
	 * Render banner container.
	 *
	 * @return void
	 */
	public function render_banner() {
		$this->renderer->render_banner();
	}
}

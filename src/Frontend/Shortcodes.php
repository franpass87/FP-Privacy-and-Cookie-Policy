<?php
/**
 * Shortcodes.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Utils\Options;
use FP\Privacy\Utils\View;

/**
 * Registers frontend shortcodes.
 */
class Shortcodes {
/**
 * Options handler.
 *
 * @var Options
 */
private $options;

    /**
     * View renderer.
     *
     * @var View
     */
    private $view;

    /**
     * Policy generator.
     *
     * @var PolicyGenerator
     */
    private $generator;

	/**
	 * Consent state.
	 *
	 * @var ConsentState
	 */
	private $state;

	/**
	 * Renderer.
	 *
	 * @var ShortcodeRenderer
	 */
	private $renderer;

	/**
	 * Asset manager.
	 *
	 * @var ShortcodeAssetManager
	 */
	private $asset_manager;

/**
 * Whether to force enqueue banner assets.
 *
 * @var bool
 */
private $force_enqueue = false;

/**
 * Constructor.
 *
     * @param Options         $options   Options handler.
     * @param View            $view      View renderer.
     * @param PolicyGenerator $generator Policy generator.
     */
	public function __construct( Options $options, View $view, PolicyGenerator $generator ) {
		$this->options       = $options;
		$this->view          = $view;
		$this->generator     = $generator;
		$this->asset_manager = new ShortcodeAssetManager( $options );
		$this->renderer      = new ShortcodeRenderer( $options, $generator, $this->asset_manager );
		$this->renderer->set_force_enqueue_callback( array( $this, 'set_force_enqueue' ) );
	}

	/**
	 * Set force enqueue flag.
	 *
	 * @param bool $value Value.
	 *
	 * @return void
	 */
	public function set_force_enqueue( $value ) {
		$this->force_enqueue = $value;
	}

/**
 * Inject consent state dependency.
 *
 * @param ConsentState $state State.
 *
 * @return void
 */
	public function set_state( ConsentState $state ) {
		$this->state = $state;
		$this->renderer->set_state( $state );
	}

/**
 * Hooks.
 *
 * @return void
 */
public function hooks() {
\add_action( 'init', array( $this, 'register_shortcodes' ) );
\add_filter( 'fp_privacy_force_enqueue_banner', array( $this, 'maybe_force_enqueue' ) );
}

	/**
	 * Register shortcodes.
	 *
	 * @return void
	 */
	public function register_shortcodes() {
		\add_shortcode( 'fp_privacy_policy', array( $this->renderer, 'render_privacy_policy' ) );
		\add_shortcode( 'fp_cookie_policy', array( $this->renderer, 'render_cookie_policy' ) );
		\add_shortcode( 'fp_cookie_preferences', array( $this->renderer, 'render_preferences_button' ) );
		\add_shortcode( 'fp_cookie_banner', array( $this->renderer, 'render_cookie_banner' ) );
	}

/**
 * Force enqueue when needed.
 *
 * @param bool $value Current value.
 *
 * @return bool
 */
public function maybe_force_enqueue( $value ) {
return $value || $this->force_enqueue;
}

}

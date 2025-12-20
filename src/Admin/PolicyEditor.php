<?php
/**
 * Policy editor page.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Utils\Options;

/**
 * Handles policy editing and regeneration.
 */
class PolicyEditor {
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
	 * Renderer.
	 *
	 * @var PolicyEditorRenderer
	 */
	private $renderer;

	/**
	 * Handler.
	 *
	 * @var PolicyEditorHandler
	 */
	private $handler;

	/**
	 * Document generator.
	 *
	 * @var PolicyDocumentGenerator
	 */
	private $document_generator;

	/**
	 * Constructor.
	 *
	 * @param Options         $options   Options handler.
	 * @param PolicyGenerator $generator Generator.
	 */
	public function __construct( Options $options, PolicyGenerator $generator ) {
		$this->options   = $options;
		$this->generator = $generator;

		$diff_generator      = new PolicyDiffGenerator( $options, $generator );
		$this->renderer      = new PolicyEditorRenderer( $options, $diff_generator );
		$this->document_generator = new PolicyDocumentGenerator( $options, $generator );
		$snapshot_manager    = new PolicySnapshotManager( $options );
		$this->handler       = new PolicyEditorHandler( $options, $generator, $this->document_generator, $snapshot_manager );
	}

/**
 * Hooks.
 *
 * @return void
 */
	public function hooks() {
		\add_action( 'fp_privacy_admin_page_policy_editor', array( $this, 'render_page' ) );
		\add_action( 'admin_post_fp_privacy_save_policy', array( $this->handler, 'handle_save' ) );
		\add_action( 'admin_post_fp_privacy_regenerate_policy', array( $this->handler, 'handle_regenerate' ) );
	}

/**
 * Render page.
 *
 * @return void
 */
	public function render_page() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
		}

		$this->options->ensure_pages_exist();

		$languages = $this->options->get_languages();
		if ( empty( $languages ) ) {
			$languages = array( \get_locale() );
		}

		$this->document_generator->maybe_generate_documents( $languages );

		$privacy_posts = array();
		$cookie_posts  = array();

		foreach ( $languages as $language ) {
			$privacy_id              = $this->options->get_page_id( 'privacy_policy', $language );
			$cookie_id               = $this->options->get_page_id( 'cookie_policy', $language );
			$privacy_posts[ $language ] = $privacy_id ? \get_post( $privacy_id ) : null;
			$cookie_posts[ $language ]  = $cookie_id ? \get_post( $cookie_id ) : null;
		}

		$this->renderer->render( $languages, $privacy_posts, $cookie_posts );
	}

}

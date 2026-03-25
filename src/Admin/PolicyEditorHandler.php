<?php
/**
 * Policy editor handler.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Utils\Options;

/**
 * Handles policy editor actions (save, regenerate).
 */
class PolicyEditorHandler {
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
	 * Snapshot manager.
	 *
	 * @var PolicySnapshotManager
	 */
	private $snapshot_manager;

	/**
	 * Constructor.
	 *
	 * @param Options                 $options          Options handler.
	 * @param PolicyGenerator         $generator        Generator.
	 * @param PolicySnapshotManager   $snapshot_manager Snapshot manager.
	 */
	public function __construct( Options $options, PolicyGenerator $generator, PolicySnapshotManager $snapshot_manager ) {
		$this->options          = $options;
		$this->generator        = $generator;
		$this->snapshot_manager   = $snapshot_manager;
	}

	/**
	 * Handle manual save.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
		}

		\check_admin_referer( 'fp_privacy_save_policy', 'fp_privacy_policy_nonce' );

		$privacy_contents = isset( $_POST['privacy_content'] ) ? \wp_unslash( $_POST['privacy_content'] ) : array();
		$cookie_contents  = isset( $_POST['cookie_content'] ) ? \wp_unslash( $_POST['cookie_content'] ) : array();

		$languages = $this->options->get_languages();
		if ( empty( $languages ) ) {
			$languages = array( \get_locale() );
		}

		foreach ( $languages as $language ) {
			$language     = $this->options->normalize_language( $language );
			$privacy_id   = $this->options->get_page_id( 'privacy_policy', $language );
			$cookie_id    = $this->options->get_page_id( 'cookie_policy', $language );
			$privacy_body = isset( $privacy_contents[ $language ] ) ? $privacy_contents[ $language ] : '';
			$cookie_body  = isset( $cookie_contents[ $language ] ) ? $cookie_contents[ $language ] : '';

			if ( $privacy_id ) {
				\wp_update_post(
					array(
						'ID'           => $privacy_id,
						'post_content' => $privacy_body,
					)
				);
			}

			if ( $cookie_id ) {
				\wp_update_post(
					array(
						'ID'           => $cookie_id,
						'post_content' => $cookie_body,
					)
				);
			}
		}

		\wp_safe_redirect( \add_query_arg( 'policy-updated', '1', \wp_get_referer() ) );
		exit;
	}

	/**
	 * Handle regeneration.
	 *
	 * @return void
	 */
	public function handle_regenerate() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
		}

		\check_admin_referer( 'fp_privacy_regenerate_policy', 'fp_privacy_regenerate_nonce' );

		$this->options->ensure_pages_exist();

		$languages = $this->options->get_languages();
		if ( empty( $languages ) ) {
			$languages = array( \get_locale() );
		}

		$generated_privacy = array();
		$generated_cookie  = array();
		$doc_placeholders  = new PolicyDocumentGenerator( $this->options );

		foreach ( $languages as $language ) {
			$language   = $this->options->normalize_language( $language );
			$privacy_id = $this->options->get_page_id( 'privacy_policy', $language );
			$cookie_id  = $this->options->get_page_id( 'cookie_policy', $language );

			$privacy = $this->generator->generate_privacy_policy( $language );
			$cookie  = $this->generator->generate_cookie_policy( $language );

			$generated_privacy[ $language ] = $privacy;
			$generated_cookie[ $language ]  = $cookie;

			$placeholder_privacy = $doc_placeholders->get_page_placeholder( 'privacy', $language );
			$placeholder_cookie  = $doc_placeholders->get_page_placeholder( 'cookie', $language );

			if ( $privacy_id ) {
				\wp_update_post(
					array(
						'ID'           => $privacy_id,
						'post_content' => $placeholder_privacy,
					)
				);
				\update_post_meta( $privacy_id, Options::PAGE_MANAGED_META_KEY, \hash( 'sha256', $placeholder_privacy ) );
			}

			if ( $cookie_id ) {
				\wp_update_post(
					array(
						'ID'           => $cookie_id,
						'post_content' => $placeholder_cookie,
					)
				);
				\update_post_meta( $cookie_id, Options::PAGE_MANAGED_META_KEY, \hash( 'sha256', $placeholder_cookie ) );
			}
		}

		$this->options->bump_revision();

		$timestamp = time();
		$services  = $this->generator->snapshot( true );
		$this->options->prime_script_rules_from_services( $services );

		$this->snapshot_manager->save_snapshot( $services, $generated_privacy, $generated_cookie, $timestamp );

		\wp_safe_redirect( \add_query_arg( 'policy-regenerated', '1', \wp_get_referer() ) );
		exit;
	}
}
















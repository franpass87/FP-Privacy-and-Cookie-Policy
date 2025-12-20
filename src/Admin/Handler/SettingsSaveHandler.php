<?php
/**
 * Settings save handler.
 *
 * @package FP\Privacy\Admin\Handler
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin\Handler;

use FP\Privacy\Utils\Options;

/**
 * Handles settings save operations.
 */
class SettingsSaveHandler {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Policy links auto-populator.
	 *
	 * @var PolicyLinksAutoPopulator
	 */
	private $policy_links_populator;

	/**
	 * Constructor.
	 *
	 * @param Options                  $options                Options handler.
	 * @param PolicyLinksAutoPopulator $policy_links_populator Policy links populator.
	 */
	public function __construct( Options $options, PolicyLinksAutoPopulator $policy_links_populator ) {
		$this->options                = $options;
		$this->policy_links_populator  = $policy_links_populator;
	}

	/**
	 * Prepare payload from POST data.
	 *
	 * @return array<string, mixed>
	 */
	public function prepare_payload() {
		// Safely extract languages - handle both string (comma-separated) and array inputs
		$languages_raw = isset( $_POST['languages_active'] ) ? \wp_unslash( $_POST['languages_active'] ) : '';
		if ( \is_array( $languages_raw ) ) {
			// If already an array, just trim each value
			$languages = array_filter( array_map( 'trim', $languages_raw ) );
		} elseif ( \is_string( $languages_raw ) && '' !== $languages_raw ) {
			// If string, sanitize and split by comma
			$languages_raw = \sanitize_text_field( $languages_raw );
			$languages     = array_filter( array_map( 'trim', explode( ',', $languages_raw ) ) );
		} else {
			$languages = array();
		}

		if ( empty( $languages ) ) {
			$languages = array( \get_locale() );
		}

		$payload = array(
			'languages_active'       => $languages,
			'banner_texts'           => isset( $_POST['banner_texts'] ) ? \wp_unslash( $_POST['banner_texts'] ) : array(),
			'banner_layout'          => isset( $_POST['banner_layout'] ) ? \wp_unslash( $_POST['banner_layout'] ) : array(),
			'consent_mode_defaults'  => isset( $_POST['consent_mode_defaults'] ) ? \wp_unslash( $_POST['consent_mode_defaults'] ) : array(),
			'gpc_enabled'            => isset( $_POST['gpc_enabled'] ),
			'preview_mode'           => isset( $_POST['preview_mode'] ),
			'org_name'               => isset( $_POST['org_name'] ) ? \wp_unslash( $_POST['org_name'] ) : '',
			'vat'                    => isset( $_POST['vat'] ) ? \wp_unslash( $_POST['vat'] ) : '',
			'address'                => isset( $_POST['address'] ) ? \wp_unslash( $_POST['address'] ) : '',
			'dpo_name'               => isset( $_POST['dpo_name'] ) ? \wp_unslash( $_POST['dpo_name'] ) : '',
			'dpo_email'              => isset( $_POST['dpo_email'] ) ? \wp_unslash( $_POST['dpo_email'] ) : '',
			'privacy_email'          => isset( $_POST['privacy_email'] ) ? \wp_unslash( $_POST['privacy_email'] ) : '',
			'categories'             => $this->options->get( 'categories' ),
			'retention_days'         => isset( $_POST['retention_days'] ) ? (int) $_POST['retention_days'] : $this->options->get( 'retention_days' ),
			'scripts'                => isset( $_POST['scripts'] ) ? \wp_unslash( $_POST['scripts'] ) : array(),
			'detector_notifications' => array(
				'email'      => isset( $_POST['detector_notifications']['email'] ),
				'recipients' => isset( $_POST['detector_notifications']['recipients'] ) ? \wp_unslash( $_POST['detector_notifications']['recipients'] ) : '',
			),
			'auto_update_services'   => isset( $_POST['auto_update_services'] ),
			'auto_update_policies'   => isset( $_POST['auto_update_policies'] ),
		);

		// Auto-populate link_policy fields before saving
		$this->policy_links_populator->auto_populate_before_save( $payload, $languages );

		return $payload;
	}

	/**
	 * Save settings.
	 *
	 * @return void
	 */
	public function save() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
		}

		\check_admin_referer( 'fp_privacy_save_settings', 'fp_privacy_nonce' );

		$payload = $this->prepare_payload();
		$this->options->set( $payload );

		\wp_safe_redirect( \add_query_arg( 'updated', 'true', \wp_get_referer() ) );
		exit;
	}
}













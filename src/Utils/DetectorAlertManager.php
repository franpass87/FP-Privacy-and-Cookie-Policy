<?php
/**
 * Detector alert manager.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

/**
 * Manages detector alerts and notifications.
 */
class DetectorAlertManager {
	/**
	 * Options handler reference.
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
	 * Get the detector alert payload.
	 *
	 * @return array<string, mixed>
	 */
	public function get_detector_alert() {
		$all_options = $this->options->all();
		if ( isset( $all_options['detector_alert'] ) && \is_array( $all_options['detector_alert'] ) ) {
			return $all_options['detector_alert'];
		}

		return $this->get_default_detector_alert();
	}

	/**
	 * Persist detector alert payload.
	 *
	 * @param array<string, mixed> $payload Alert payload.
	 *
	 * @return void
	 */
	public function set_detector_alert( array $payload ) {
		$this->options->set(
			array(
				'detector_alert' => $payload,
			)
		);
	}

	/**
	 * Reset detector alert to defaults.
	 *
	 * @return void
	 */
	public function clear_detector_alert() {
		$this->set_detector_alert( $this->get_default_detector_alert() );
	}

	/**
	 * Get default detector alert payload.
	 *
	 * @return array<string, mixed>
	 */
	public function get_default_detector_alert() {
		return array(
			'active'       => false,
			'detected_at'  => 0,
			'last_checked' => 0,
			'added'        => array(),
			'removed'      => array(),
		);
	}

	/**
	 * Get default detector notification settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_default_detector_notifications() {
		return array(
			'email'      => true,
			'recipients' => array(),
			'last_sent'  => 0,
		);
	}

	/**
	 * Retrieve detector notification settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_detector_notifications() {
		$all_options = $this->options->all();
		if ( isset( $all_options['detector_notifications'] ) && \is_array( $all_options['detector_notifications'] ) ) {
			return \array_merge( $this->get_default_detector_notifications(), $all_options['detector_notifications'] );
		}

		return $this->get_default_detector_notifications();
	}

	/**
	 * Persist detector notification settings.
	 *
	 * @param array<string, mixed> $settings Settings to merge.
	 *
	 * @return void
	 */
	public function update_detector_notifications( array $settings ) {
		$current = $this->get_detector_notifications();
		$merged  = \array_merge( $current, $settings );

		$this->options->set(
			array(
				'detector_notifications' => $merged,
			)
		);
	}
}

















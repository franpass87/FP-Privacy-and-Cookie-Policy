<?php
/**
 * Settings controller (new Presentation layer).
 *
 * @package FP\Privacy\Presentation\Admin\Controllers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\Admin\Controllers;

use FP\Privacy\Application\Settings\GetSettingsHandler;
use FP\Privacy\Application\Settings\UpdateSettingsHandler;
use FP\Privacy\Admin\SettingsRenderer;
use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Utils\Options;

/**
 * Settings controller in Presentation layer.
 * 
 * This is the new controller structure. The old Admin\SettingsController is kept for backward compatibility.
 */
class SettingsController {
	/**
	 * Get settings handler.
	 *
	 * @var GetSettingsHandler
	 */
	private $get_handler;

	/**
	 * Update settings handler.
	 *
	 * @var UpdateSettingsHandler
	 */
	private $update_handler;

	/**
	 * Settings renderer (legacy, will be moved to Views later).
	 *
	 * @var SettingsRenderer
	 */
	private $renderer;

	/**
	 * Constructor.
	 *
	 * @param GetSettingsHandler    $get_handler Get settings handler.
	 * @param UpdateSettingsHandler $update_handler Update settings handler.
	 * @param SettingsRenderer      $renderer Settings renderer.
	 */
	public function __construct(
		GetSettingsHandler $get_handler,
		UpdateSettingsHandler $update_handler,
		SettingsRenderer $renderer
	) {
		$this->get_handler    = $get_handler;
		$this->update_handler = $update_handler;
		$this->renderer       = $renderer;
	}

	/**
	 * Handle settings save.
	 *
	 * @param array<string, mixed> $settings Settings data.
	 * @return bool True on success.
	 */
	public function handleSave( array $settings ): bool {
		return $this->update_handler->handle( $settings );
	}

	/**
	 * Get all settings.
	 *
	 * @return array<string, mixed> All settings.
	 */
	public function getAllSettings(): array {
		return $this->get_handler->all();
	}

	/**
	 * Get a setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed Setting value.
	 */
	public function getSetting( string $key, $default = null ) {
		return $this->get_handler->get( $key, $default );
	}
}











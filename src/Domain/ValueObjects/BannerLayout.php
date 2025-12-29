<?php
/**
 * Banner layout value object.
 *
 * @package FP\Privacy\Domain\ValueObjects
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Domain\ValueObjects;

/**
 * Immutable banner layout value object.
 *
 * Represents the layout configuration for the consent banner.
 */
class BannerLayout {
	/**
	 * Banner type.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Banner position.
	 *
	 * @var string
	 */
	private $position;

	/**
	 * Color palette.
	 *
	 * @var ColorPalette
	 */
	private $palette;

	/**
	 * Sync modal and button colors.
	 *
	 * @var bool
	 */
	private $sync_modal_and_button;

	/**
	 * Valid banner types.
	 *
	 * @var array<string>
	 */
	private const VALID_TYPES = array( 'floating', 'inline', 'modal' );

	/**
	 * Valid banner positions.
	 *
	 * @var array<string>
	 */
	private const VALID_POSITIONS = array( 'top', 'bottom', 'left', 'right' );

	/**
	 * Constructor.
	 *
	 * @param string       $type                  Banner type.
	 * @param string       $position              Banner position.
	 * @param ColorPalette $palette               Color palette.
	 * @param bool         $sync_modal_and_button Sync modal and button colors.
	 */
	public function __construct(
		$type = 'floating',
		$position = 'bottom',
		ColorPalette $palette = null,
		$sync_modal_and_button = true
	) {
		$this->type                  = $this->validate_type( $type );
		$this->position              = $this->validate_position( $position );
		$this->palette               = $palette ?? new ColorPalette();
		$this->sync_modal_and_button = (bool) $sync_modal_and_button;
	}

	/**
	 * Create from array.
	 *
	 * @param array<string, mixed> $data Layout data.
	 *
	 * @return self
	 */
	public static function from_array( array $data ) {
		$palette = null;
		if ( isset( $data['palette'] ) && is_array( $data['palette'] ) ) {
			$palette = ColorPalette::from_array( $data['palette'] );
		}

		return new self(
			$data['type'] ?? 'floating',
			$data['position'] ?? 'bottom',
			$palette,
			$data['sync_modal_and_button'] ?? true
		);
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array() {
		return array(
			'type'                  => $this->type,
			'position'              => $this->position,
			'palette'               => $this->palette->to_array(),
			'sync_modal_and_button' => $this->sync_modal_and_button,
		);
	}

	/**
	 * Get banner type.
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Get banner position.
	 *
	 * @return string
	 */
	public function get_position() {
		return $this->position;
	}

	/**
	 * Get color palette.
	 *
	 * @return ColorPalette
	 */
	public function get_palette() {
		return $this->palette;
	}

	/**
	 * Check if modal and button colors are synced.
	 *
	 * @return bool
	 */
	public function is_sync_modal_and_button() {
		return $this->sync_modal_and_button;
	}

	/**
	 * Validate banner type.
	 *
	 * @param string $type Banner type.
	 *
	 * @return string
	 */
	private function validate_type( $type ) {
		if ( ! is_string( $type ) || ! in_array( $type, self::VALID_TYPES, true ) ) {
			return 'floating';
		}

		return $type;
	}

	/**
	 * Validate banner position.
	 *
	 * @param string $position Banner position.
	 *
	 * @return string
	 */
	private function validate_position( $position ) {
		if ( ! is_string( $position ) || ! in_array( $position, self::VALID_POSITIONS, true ) ) {
			return 'bottom';
		}

		return $position;
	}
}








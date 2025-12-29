<?php
/**
 * Color palette value object.
 *
 * @package FP\Privacy\Domain\ValueObjects
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Domain\ValueObjects;

/**
 * Immutable color palette value object.
 *
 * Represents the color palette configuration for the consent banner.
 */
class ColorPalette {
	/**
	 * Surface background color.
	 *
	 * @var string
	 */
	private $surface_bg;

	/**
	 * Surface text color.
	 *
	 * @var string
	 */
	private $surface_text;

	/**
	 * Primary button background color.
	 *
	 * @var string
	 */
	private $button_primary_bg;

	/**
	 * Primary button text color.
	 *
	 * @var string
	 */
	private $button_primary_tx;

	/**
	 * Secondary button background color.
	 *
	 * @var string
	 */
	private $button_secondary_bg;

	/**
	 * Secondary button text color.
	 *
	 * @var string
	 */
	private $button_secondary_tx;

	/**
	 * Link color.
	 *
	 * @var string
	 */
	private $link;

	/**
	 * Border color.
	 *
	 * @var string
	 */
	private $border;

	/**
	 * Focus color.
	 *
	 * @var string
	 */
	private $focus;

	/**
	 * Constructor.
	 *
	 * @param string $surface_bg          Surface background color.
	 * @param string $surface_text        Surface text color.
	 * @param string $button_primary_bg   Primary button background color.
	 * @param string $button_primary_tx   Primary button text color.
	 * @param string $button_secondary_bg Secondary button background color.
	 * @param string $button_secondary_tx Secondary button text color.
	 * @param string $link                Link color.
	 * @param string $border              Border color.
	 * @param string $focus               Focus color.
	 */
	public function __construct(
		$surface_bg = '#F9FAFB',
		$surface_text = '#1F2937',
		$button_primary_bg = '#2563EB',
		$button_primary_tx = '#FFFFFF',
		$button_secondary_bg = '#FFFFFF',
		$button_secondary_tx = '#1F2937',
		$link = '#1D4ED8',
		$border = '#D1D5DB',
		$focus = '#2563EB'
	) {
		$this->surface_bg          = $this->sanitize_color( $surface_bg );
		$this->surface_text        = $this->sanitize_color( $surface_text );
		$this->button_primary_bg   = $this->sanitize_color( $button_primary_bg );
		$this->button_primary_tx   = $this->sanitize_color( $button_primary_tx );
		$this->button_secondary_bg = $this->sanitize_color( $button_secondary_bg );
		$this->button_secondary_tx = $this->sanitize_color( $button_secondary_tx );
		$this->link                = $this->sanitize_color( $link );
		$this->border              = $this->sanitize_color( $border );
		$this->focus               = $this->sanitize_color( $focus );
	}

	/**
	 * Create from array.
	 *
	 * @param array<string, string> $data Palette data.
	 *
	 * @return self
	 */
	public static function from_array( array $data ) {
		return new self(
			$data['surface_bg'] ?? '#F9FAFB',
			$data['surface_text'] ?? '#1F2937',
			$data['button_primary_bg'] ?? '#2563EB',
			$data['button_primary_tx'] ?? '#FFFFFF',
			$data['button_secondary_bg'] ?? '#FFFFFF',
			$data['button_secondary_tx'] ?? '#1F2937',
			$data['link'] ?? '#1D4ED8',
			$data['border'] ?? '#D1D5DB',
			$data['focus'] ?? '#2563EB'
		);
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, string>
	 */
	public function to_array() {
		return array(
			'surface_bg'          => $this->surface_bg,
			'surface_text'        => $this->surface_text,
			'button_primary_bg'   => $this->button_primary_bg,
			'button_primary_tx'   => $this->button_primary_tx,
			'button_secondary_bg' => $this->button_secondary_bg,
			'button_secondary_tx' => $this->button_secondary_tx,
			'link'                => $this->link,
			'border'              => $this->border,
			'focus'               => $this->focus,
		);
	}

	/**
	 * Get surface background color.
	 *
	 * @return string
	 */
	public function get_surface_bg() {
		return $this->surface_bg;
	}

	/**
	 * Get surface text color.
	 *
	 * @return string
	 */
	public function get_surface_text() {
		return $this->surface_text;
	}

	/**
	 * Get primary button background color.
	 *
	 * @return string
	 */
	public function get_button_primary_bg() {
		return $this->button_primary_bg;
	}

	/**
	 * Get primary button text color.
	 *
	 * @return string
	 */
	public function get_button_primary_tx() {
		return $this->button_primary_tx;
	}

	/**
	 * Get secondary button background color.
	 *
	 * @return string
	 */
	public function get_button_secondary_bg() {
		return $this->button_secondary_bg;
	}

	/**
	 * Get secondary button text color.
	 *
	 * @return string
	 */
	public function get_button_secondary_tx() {
		return $this->button_secondary_tx;
	}

	/**
	 * Get link color.
	 *
	 * @return string
	 */
	public function get_link() {
		return $this->link;
	}

	/**
	 * Get border color.
	 *
	 * @return string
	 */
	public function get_border() {
		return $this->border;
	}

	/**
	 * Get focus color.
	 *
	 * @return string
	 */
	public function get_focus() {
		return $this->focus;
	}

	/**
	 * Sanitize color value.
	 *
	 * @param string $color Color value.
	 *
	 * @return string
	 */
	private function sanitize_color( $color ) {
		if ( ! is_string( $color ) ) {
			return '#000000';
		}

		// Validate hex color format.
		if ( preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color ) ) {
			return $color;
		}

		return '#000000';
	}
}








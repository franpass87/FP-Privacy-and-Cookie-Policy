<?php
/**
 * Plugin constants.
 *
 * @package FP\Privacy\Shared
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Shared;

/**
 * Plugin-wide constants.
 */
final class Constants {
	/**
	 * Option keys.
	 */
	public const OPTION_KEY = 'fp_privacy_options';
	public const PAGE_MANAGED_META_KEY = '_fp_privacy_managed_signature';

	/**
	 * Consent revision constants.
	 */
	public const CONSENT_REVISION_INITIAL = 1;
	public const CONSENT_REVISION_MINIMUM = 1;

	/**
	 * Retention constants (in days).
	 */
	public const RETENTION_DAYS_DEFAULT = 180;
	public const RETENTION_DAYS_MINIMUM = 1;
	public const RETENTION_DAYS_CLEANUP_DEFAULT = 365;

	/**
	 * Cookie duration constants (in days).
	 */
	public const COOKIE_DURATION_DAYS_DEFAULT = 180;
	public const COOKIE_DURATION_DAYS_MINIMUM = 1;

	/**
	 * Banner layout types.
	 */
	public const BANNER_LAYOUT_FLOATING = 'floating';
	public const BANNER_LAYOUT_BAR = 'bar';

	/**
	 * Banner positions.
	 */
	public const BANNER_POSITION_TOP = 'top';
	public const BANNER_POSITION_BOTTOM = 'bottom';

	/**
	 * Default locale.
	 */
	public const DEFAULT_LOCALE = 'it_IT';

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {
	}
}








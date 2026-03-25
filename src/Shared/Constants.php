<?php
/**
 * Plugin constants.
 *
 * @package FP\Privacy\Shared
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

declare(strict_types=1);

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
	 * Default body text for the banner "Info / About" tab (canonical strings for locale fixes).
	 */
	public const BANNER_INFO_ABOUT_EN_UK = 'We use cookies to ensure the proper functioning of the site and to improve your browsing experience. Cookies allow us to store your preferences, analyze traffic and personalise content. For more details on which cookies we use and how to manage them, please refer to our Cookie Policy and Privacy Policy.';

	public const BANNER_INFO_ABOUT_EN_US = 'We use cookies to ensure the proper functioning of the site and to improve your browsing experience. Cookies allow us to store your preferences, analyze traffic and personalize content. For more details on which cookies we use and how to manage them, please refer to our Cookie Policy and Privacy Policy.';

	public const BANNER_INFO_ABOUT_IT = "Utilizziamo i cookie per garantire il corretto funzionamento del sito e per migliorare la tua esperienza di navigazione. I cookie ci consentono di memorizzare le tue preferenze, analizzare il traffico e personalizzare i contenuti. Per maggiori dettagli su quali cookie utilizziamo e come gestirli, consulta la nostra Cookie Policy e l'Informativa sulla Privacy.";

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {
	}
}









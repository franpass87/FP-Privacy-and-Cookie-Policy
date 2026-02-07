<?php
/**
 * Additional services config bootstrap.
 * Returns a callable that loads LinkedIn, TikTok, Matomo, Pinterest, HubSpot,
 * WooCommerce and embed services (Wistia, Calendly, etc.) for the detector registry.
 *
 * @package FP\Privacy\Integrations\Config
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return \FP\Privacy\Integrations\Config\AdditionalServicesLoader::get_loader();

<?php
/**
 * Multisite manager interface.
 *
 * @package FP\Privacy\Infrastructure\Multisite
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Infrastructure\Multisite;

/**
 * Interface for multisite manager implementations.
 */
interface MultisiteManagerInterface {
	/**
	 * Handle plugin activation.
	 *
	 * @param bool $network_wide Whether network-wide activation.
	 * @return void
	 */
	public function activate( bool $network_wide ): void;

	/**
	 * Handle plugin deactivation.
	 *
	 * @return void
	 */
	public function deactivate(): void;

	/**
	 * Provision a new site.
	 *
	 * @param int $blog_id Blog ID.
	 * @return void
	 */
	public function provision_new_site( int $blog_id ): void;
}















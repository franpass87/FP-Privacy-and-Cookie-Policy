<?php
/**
 * Frontend service provider.
 *
 * @package FP\Privacy\Providers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Providers;

use FP\Privacy\Core\ContainerInterface;
use FP\Privacy\Core\ServiceProviderInterface;
use FP\Privacy\Frontend\Banner;
use FP\Privacy\Frontend\Blocks;
use FP\Privacy\Frontend\ConsentState;
use FP\Privacy\Frontend\Shortcodes;
use FP\Privacy\Frontend\ScriptBlocker;
use FP\Privacy\Utils\Options;
use FP\Privacy\Utils\View;
use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Integrations\DetectorRegistry;

/**
 * Frontend service provider - registers frontend services.
 */
class FrontendServiceProvider implements ServiceProviderInterface {
	use ServiceProviderHelper;
	/**
	 * Register services in the container.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	public function register( ContainerInterface $container ): void {
		// Consent state.
		$container->singleton(
			ConsentState::class,
			function( ContainerInterface $c ) {
				$provider = new self();
				$options = $provider->getOptions( $c );
				$log_model = $c->get( \FP\Privacy\Consent\LogModel::class );
				return new ConsentState( $options, $log_model );
			}
		);

		// Banner.
		$container->singleton(
			Banner::class,
			function( ContainerInterface $c ) {
				$provider = new self();
				$options = $provider->getOptions( $c );
				$state = $c->get( ConsentState::class );
				return new Banner( $options, $state );
			}
		);

		// Shortcodes.
		$container->singleton(
			Shortcodes::class,
			function( ContainerInterface $c ) {
				$provider = new self();
				$options = $provider->getOptions( $c );
				$view = new View();
				$detector = $c->get( DetectorRegistry::class );
				$generator = new PolicyGenerator( $options, $detector, $view );
				$shortcodes = new Shortcodes( $options, $view, $generator );
				$shortcodes->set_state( $c->get( ConsentState::class ) );
				return $shortcodes;
			}
		);

		// Blocks.
		$container->singleton(
			Blocks::class,
			function( ContainerInterface $c ) {
				$provider = new self();
				$options = $provider->getOptions( $c );
				return new Blocks( $options );
			}
		);

		// Script blocker.
		$container->singleton(
			ScriptBlocker::class,
			function( ContainerInterface $c ) {
				$provider = new self();
				$options = $provider->getOptions( $c );
				$state = $c->get( ConsentState::class );
				return new ScriptBlocker( $options, $state );
			}
		);
	}

	/**
	 * Boot services after all providers are registered.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	public function boot( ContainerInterface $container ): void {
		// Register frontend hooks.
		$banner = $container->get( Banner::class );
		if ( method_exists( $banner, 'hooks' ) ) {
			$banner->hooks();
		}

		$shortcodes = $container->get( Shortcodes::class );
		if ( method_exists( $shortcodes, 'hooks' ) ) {
			$shortcodes->hooks();
		}

		$blocks = $container->get( Blocks::class );
		if ( method_exists( $blocks, 'hooks' ) ) {
			$blocks->hooks();
		}

		$script_blocker = $container->get( ScriptBlocker::class );
		if ( method_exists( $script_blocker, 'hooks' ) ) {
			$script_blocker->hooks();
		}
	}
}

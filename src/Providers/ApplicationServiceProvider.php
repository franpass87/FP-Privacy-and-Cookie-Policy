<?php
/**
 * Application service provider.
 *
 * @package FP\Privacy\Providers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Providers;

use FP\Privacy\Application\Consent\CleanupConsentHandler;
use FP\Privacy\Application\Consent\ExportConsentHandler;
use FP\Privacy\Application\Consent\GetConsentQuery;
use FP\Privacy\Application\Consent\GetConsentStateQuery;
use FP\Privacy\Application\Consent\GetConsentSummaryQuery;
use FP\Privacy\Application\Consent\LogConsentHandler;
use FP\Privacy\Application\Consent\RevokeConsentHandler;
use FP\Privacy\Application\Policy\GeneratePolicyHandler;
use FP\Privacy\Application\Policy\UpdatePolicyHandler;
use FP\Privacy\Application\Settings\GetSettingsHandler;
use FP\Privacy\Application\Settings\UpdateSettingsHandler;
use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Core\ContainerInterface;
use FP\Privacy\Core\ServiceProviderInterface;
use FP\Privacy\Consent\LogModel;
use FP\Privacy\Domain\Consent\ConsentRepositoryInterface;
use FP\Privacy\Domain\Consent\ConsentService;
use FP\Privacy\Domain\Policy\PolicyRepositoryInterface;
use FP\Privacy\Domain\Policy\PolicyService;
use FP\Privacy\Infrastructure\Options\OptionsRepositoryInterface;
use FP\Privacy\Services\Logger\LoggerInterface;
use FP\Privacy\Services\Sanitization\SanitizerInterface;
use FP\Privacy\Services\Validation\ValidatorInterface;

/**
 * Application service provider - registers use case handlers.
 */
class ApplicationServiceProvider implements ServiceProviderInterface {
	/**
	 * Register services in the container.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	public function register( ContainerInterface $container ): void {
		// Consent handlers.
		$container->singleton(
			LogConsentHandler::class,
			function( ContainerInterface $c ) {
				$service = $c->get( ConsentService::class );
				$logger = $c->get( LoggerInterface::class );
				return new LogConsentHandler( $service, $logger );
			}
		);

		$container->singleton(
			RevokeConsentHandler::class,
			function( ContainerInterface $c ) {
				$service = $c->get( ConsentService::class );
				$logger = $c->get( LoggerInterface::class );
				return new RevokeConsentHandler( $service, $logger );
			}
		);

		$container->singleton(
			GetConsentQuery::class,
			function( ContainerInterface $c ) {
				$repository = $c->get( ConsentRepositoryInterface::class );
				return new GetConsentQuery( $repository );
			}
		);

		$container->singleton(
			GetConsentSummaryQuery::class,
			function( ContainerInterface $c ) {
				$repository = $c->get( ConsentRepositoryInterface::class );
				$log_model = $c->get( LogModel::class );
				$options = $c->get( \FP\Privacy\Services\Options\OptionsInterface::class );
				return new GetConsentSummaryQuery( $repository, $log_model, $options );
			}
		);

		// Policy handlers.
		$container->singleton(
			GeneratePolicyHandler::class,
			function( ContainerInterface $c ) {
				// PolicyGenerator is still in Admin namespace, will be moved to Domain later.
				$generator = $c->has( PolicyGenerator::class ) 
					? $c->get( PolicyGenerator::class )
					: new PolicyGenerator(
						$c->get( \FP\Privacy\Services\Options\OptionsInterface::class ),
						$c->get( \FP\Privacy\Integrations\DetectorRegistry::class ),
						new \FP\Privacy\Utils\View()
					);
				$logger = $c->get( LoggerInterface::class );
				return new GeneratePolicyHandler( $generator, $logger );
			}
		);

		$container->singleton(
			UpdatePolicyHandler::class,
			function( ContainerInterface $c ) {
				$service = $c->get( PolicyService::class );
				$repository = $c->get( PolicyRepositoryInterface::class );
				$validator = $c->get( ValidatorInterface::class );
				$sanitizer = $c->get( SanitizerInterface::class );
				$logger = $c->get( LoggerInterface::class );
				return new UpdatePolicyHandler( $service, $repository, $validator, $sanitizer, $logger );
			}
		);

		// Settings handlers.
		$container->singleton(
			GetSettingsHandler::class,
			function( ContainerInterface $c ) {
				$options = $c->get( OptionsRepositoryInterface::class );
				return new GetSettingsHandler( $options );
			}
		);

		$container->singleton(
			UpdateSettingsHandler::class,
			function( ContainerInterface $c ) {
				$options = $c->get( OptionsRepositoryInterface::class );
				$validator = $c->get( ValidatorInterface::class );
				$sanitizer = $c->get( SanitizerInterface::class );
				$logger = $c->get( LoggerInterface::class );
				return new UpdateSettingsHandler( $options, $validator, $sanitizer, $logger );
			}
		);

		// Consent cleanup handler.
		$container->singleton(
			CleanupConsentHandler::class,
			function( ContainerInterface $c ) {
				$repository = $c->get( ConsentRepositoryInterface::class );
				$options = $c->get( \FP\Privacy\Services\Options\OptionsInterface::class );
				$logger = $c->get( LoggerInterface::class );
				return new CleanupConsentHandler( $repository, $options, $logger );
			}
		);

		// Consent export handler.
		$container->singleton(
			ExportConsentHandler::class,
			function( ContainerInterface $c ) {
				$repository = $c->get( ConsentRepositoryInterface::class );
				$logger = $c->get( LoggerInterface::class );
				return new ExportConsentHandler( $repository, $logger );
			}
		);

		// Consent state query handler.
		$container->singleton(
			GetConsentStateQuery::class,
			function( ContainerInterface $c ) {
				$repository = $c->get( ConsentRepositoryInterface::class );
				$options = $c->get( OptionsRepositoryInterface::class );
				// Inject legacy Options for methods not in interface (normalize_language, get_banner_text, etc.).
				// Should always be available from CoreServiceProvider.
				$legacy_options = $c->get( \FP\Privacy\Utils\Options::class );
				return new GetConsentStateQuery( $repository, $options, $legacy_options );
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
		// Application-specific initialization if needed.
	}
}


<?php
/**
 * Admin service provider.
 *
 * @package FP\Privacy\Providers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Providers;

use FP\Privacy\Core\ContainerInterface;
use FP\Privacy\Core\ServiceProviderInterface;
use FP\Privacy\Admin\Settings;
use FP\Privacy\Admin\Menu;
use FP\Privacy\Admin\PolicyEditor;
use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Admin\IntegrationAudit;
use FP\Privacy\Admin\ConsentLogTable;
use FP\Privacy\Admin\DashboardWidget;
use FP\Privacy\Admin\AnalyticsPage;
use FP\Privacy\Admin\DiagnosticTools;
use FP\Privacy\Utils\Options;
use FP\Privacy\Utils\View;
use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Consent\LogModel;
use FP\Privacy\Services\Policy\PolicyAutoUpdater;
use FP\Privacy\Core\PluginUpdater;

/**
 * Admin service provider - registers admin services.
 */
class AdminServiceProvider implements ServiceProviderInterface {
	use ServiceProviderHelper;
	/**
	 * Register services in the container.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	public function register( ContainerInterface $container ): void {
		// Policy generator.
		$container->singleton(
			PolicyGenerator::class,
			function( ContainerInterface $c ) {
				$options = self::resolveOptions( $c );
				$detector = $c->get( DetectorRegistry::class );
				$view = new View();
				return new PolicyGenerator( $options, $detector, $view );
			}
		);

		// Settings.
		$container->singleton(
			Settings::class,
			function( ContainerInterface $c ) {
				$options = self::resolveOptions( $c );
				$detector = $c->get( DetectorRegistry::class );
				$generator = $c->get( PolicyGenerator::class );
				return new Settings( $options, $detector, $generator );
			}
		);

		// Policy editor.
		$container->singleton(
			PolicyEditor::class,
			function( ContainerInterface $c ) {
				$options = self::resolveOptions( $c );
				$generator = $c->get( PolicyGenerator::class );
				return new PolicyEditor( $options, $generator );
			}
		);

		// Integration audit.
		$container->singleton(
			IntegrationAudit::class,
			function( ContainerInterface $c ) {
				$options = self::resolveOptions( $c );
				$generator = $c->get( PolicyGenerator::class );
				$auto_updater = $c->has( PolicyAutoUpdater::class ) ? $c->get( PolicyAutoUpdater::class ) : null;
				return new IntegrationAudit( $options, $generator, $auto_updater );
			}
		);

		// Consent log table.
		$container->singleton(
			ConsentLogTable::class,
			function( ContainerInterface $c ) {
				$log_model = $c->get( LogModel::class );
				$options = self::resolveOptions( $c );
				return new ConsentLogTable( $log_model, $options );
			}
		);

		// Dashboard widget.
		$container->singleton(
			DashboardWidget::class,
			function( ContainerInterface $c ) {
				$log_model = $c->get( LogModel::class );
				return new DashboardWidget( $log_model );
			}
		);

		// Analytics page.
		$container->singleton(
			AnalyticsPage::class,
			function( ContainerInterface $c ) {
				$log_model = $c->get( LogModel::class );
				$options = self::resolveOptions( $c );
				return new AnalyticsPage( $log_model, $options );
			}
		);

		// Diagnostic tools.
		$container->singleton(
			DiagnosticTools::class,
			function( ContainerInterface $c ) {
				$options = self::resolveOptions( $c );
				$log_model = $c->get( LogModel::class );
				return new DiagnosticTools( $options, $log_model );
			}
		);

		// Menu.
		$container->singleton(
			Menu::class,
			function() {
				return new Menu();
			}
		);

		// Policy auto-updater.
		$container->singleton(
			PolicyAutoUpdater::class,
			function( ContainerInterface $c ) {
				$options = self::resolveOptions( $c );
				$generator = $c->get( PolicyGenerator::class );
				return new PolicyAutoUpdater( $options, $generator );
			}
		);

		// Plugin updater.
		$container->singleton(
			PluginUpdater::class,
			function( ContainerInterface $c ) {
				$auto_updater = $c->get( PolicyAutoUpdater::class );
				return new PluginUpdater( $auto_updater );
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
		// Register admin hooks.
		$menu = $container->get( Menu::class );
		if ( method_exists( $menu, 'hooks' ) ) {
			$menu->hooks();
		}

		$settings = $container->get( Settings::class );
		if ( method_exists( $settings, 'hooks' ) ) {
			$settings->hooks();
		}

		$policy_editor = $container->get( PolicyEditor::class );
		if ( method_exists( $policy_editor, 'hooks' ) ) {
			$policy_editor->hooks();
		}

		$integration_audit = $container->get( IntegrationAudit::class );
		if ( method_exists( $integration_audit, 'hooks' ) ) {
			$integration_audit->hooks();
		}

		$consent_log_table = $container->get( ConsentLogTable::class );
		if ( method_exists( $consent_log_table, 'hooks' ) ) {
			$consent_log_table->hooks();
		}

		$dashboard_widget = $container->get( DashboardWidget::class );
		if ( method_exists( $dashboard_widget, 'hooks' ) ) {
			$dashboard_widget->hooks();
		}

		$analytics_page = $container->get( AnalyticsPage::class );
		if ( method_exists( $analytics_page, 'hooks' ) ) {
			$analytics_page->hooks();
		}

		$diagnostic_tools = $container->get( DiagnosticTools::class );
		if ( method_exists( $diagnostic_tools, 'hooks' ) ) {
			$diagnostic_tools->hooks();
		}

		// Hook for auto-updating policies when settings are saved.
		$auto_updater = $container->get( PolicyAutoUpdater::class );
		\add_action( 'fp_privacy_settings_saved', function( $payload ) use ( $auto_updater ) {
			if ( $auto_updater->should_update() ) {
				$auto_updater->update_all_policies();
			}
		}, 10, 1 );

		// Register plugin updater hooks.
		$plugin_updater = $container->get( PluginUpdater::class );
		$plugin_updater->hooks();
	}
}

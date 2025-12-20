<?php
/**
 * Scheduled detector audit and admin notices.
 *
 * @package FP\Privacy\Admin
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Admin\Audit\IntegrationAuditor;
use FP\Privacy\Admin\Audit\EmailNotifier;
use FP\Privacy\Admin\Audit\ServiceFormatter;
use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Utils\Options;
use FP\Privacy\Utils\DetectorAlertManager;


/**
 * Monitors integration changes and alerts administrators.
 */
class IntegrationAudit {
    /**
     * Options handler.
     *
     * @var Options
     */
    private $options;

    /**
     * Policy generator.
     *
     * @var PolicyGenerator
     */
    private $generator;

    /**
     * Integration auditor.
     *
     * @var IntegrationAuditor
     */
    private $auditor;

    /**
     * Email notifier.
     *
     * @var EmailNotifier
     */
    private $email_notifier;

	/**
	 * Service formatter.
	 *
	 * @var ServiceFormatter
	 */
	private $formatter;

	/**
	 * Notice renderer.
	 *
	 * @var IntegrationAuditNoticeRenderer
	 */
	private $notice_renderer;

	/**
	 * Constructor.
	 *
	 * @param Options         $options   Options handler.
	 * @param PolicyGenerator $generator Policy generator.
	 */
	public function __construct( Options $options, PolicyGenerator $generator ) {
		$this->options   = $options;
		$this->generator = $generator;

		// Initialize components
		$alert_manager = new DetectorAlertManager( $options );
		$this->formatter = new ServiceFormatter();
		$this->auditor = new IntegrationAuditor( $options, $generator, $alert_manager );
		$this->email_notifier = new EmailNotifier( $options, $alert_manager, $this->formatter );
		$this->notice_renderer = new IntegrationAuditNoticeRenderer( $this->formatter );
	}

    /**
     * Register hooks.
     *
     * @return void
     */
    public function hooks() {
        \add_action( 'fp_privacy_detector_audit', array( $this, 'run_audit' ) );
        \add_action( 'admin_notices', array( $this, 'render_notice' ) );
        \add_action( 'network_admin_notices', array( $this, 'render_notice' ) );
        \add_action( 'fp_privacy_snapshots_refreshed', array( $this, 'handle_snapshots_refreshed' ) );
    }

    /**
     * Execute scheduled audit and persist alert metadata.
     * Delegates to IntegrationAuditor.
     *
     * @return void
     */
    public function run_audit() {
        $this->auditor->run_audit();
        
        // Send email notification if needed
        $alert_manager = new DetectorAlertManager( $this->options );
        $alert = $alert_manager->get_detector_alert();
        if ( ! empty( $alert['active'] ) ) {
            $this->email_notifier->maybe_send_email_alert( $alert );
        }
    }

	/**
	 * Render the integration change notice when required.
	 *
	 * @return void
	 */
	public function render_notice() {
		$alert_manager = new DetectorAlertManager( $this->options );
		$this->notice_renderer->render_notice( $alert_manager );
	}

    /**
     * Reset alert metadata once snapshots are refreshed manually.
     *
     * @return void
     */
    public function handle_snapshots_refreshed() {
        $alert_manager = new DetectorAlertManager( $this->options );
        $alert                = $alert_manager->get_default_detector_alert();
        $alert['last_checked'] = time();
        $alert_manager->set_detector_alert( $alert );
    }
}

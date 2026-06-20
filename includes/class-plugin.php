<?php
/**
 * Plugin orchestrator.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 * @since   0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

/**
 * Main plugin class.
 *
 * @since 0.1.0
 */
class Alynt_Drime_WPvivid_Uploader_Plugin {
	use Alynt_Drime_WPvivid_Uploader_Plugin_Admin_Actions;

	/**

	 * Settings.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Settings
	 */

	private $settings;

	/**

	 * Logger.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Logger
	 */

	private $logger;

	/**

	 * Detector.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_WPvivid_Detector
	 */

	private $detector;

	/**

	 * Scanner.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Scanner
	 */

	private $scanner;

	/**

	 * Registry.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Backup_Registry
	 */

	private $registry;

	/**

	 * Queue.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Queue
	 */

	private $queue;

	/**

	 * Client.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Drime_Client
	 */

	private $client;

	/**

	 * Uploader.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Uploader
	 */

	private $uploader;

	/**

	 * Cron.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Cron
	 */

	private $cron;

	/**
	 * Remote retention.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Remote_Retention
	 */
	private $retention;

	/**

	 * Admin page.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Admin_Page
	 */

	private $admin_page;

	/**

	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {

		$this->settings = new Alynt_Drime_WPvivid_Uploader_Settings();

		$this->logger = new Alynt_Drime_WPvivid_Uploader_Logger( $this->settings );

		$this->detector = new Alynt_Drime_WPvivid_Uploader_WPvivid_Detector();

		$this->scanner = new Alynt_Drime_WPvivid_Uploader_Scanner( $this->settings, $this->detector, $this->logger );

		$this->registry = new Alynt_Drime_WPvivid_Uploader_Backup_Registry();

		$this->queue = new Alynt_Drime_WPvivid_Uploader_Queue();

		$this->client = new Alynt_Drime_WPvivid_Uploader_Drime_Client( $this->settings, $this->logger );

		$this->uploader = new Alynt_Drime_WPvivid_Uploader_Uploader( $this->settings, $this->client, $this->queue, $this->registry, $this->logger );

		$this->cron = new Alynt_Drime_WPvivid_Uploader_Cron( $this );

		$this->retention = new Alynt_Drime_WPvivid_Uploader_Remote_Retention( $this->settings, $this->client, $this->registry, $this->logger );

		$this->admin_page = new Alynt_Drime_WPvivid_Uploader_Admin_Page( $this );

		$this->hooks();
	}

	/**

	 * Registers hooks.
	 *
	 * @return void
	 *
	 * @since 0.1.0
	 */
	private function hooks() {

		add_action( 'admin_menu', array( $this->admin_page, 'register_menu' ) );

		add_action( 'admin_enqueue_scripts', array( $this->admin_page, 'enqueue_assets' ) );

		add_action( 'admin_post_alynt_drime_wpvivid_save_settings', array( $this, 'handle_save_settings' ) );

		add_action( 'admin_post_alynt_drime_wpvivid_test_connection', array( $this, 'handle_test_connection' ) );

		add_action( 'admin_post_alynt_drime_wpvivid_scan_now', array( $this, 'handle_scan_now' ) );

		add_action( 'admin_post_alynt_drime_wpvivid_upload_next', array( $this, 'handle_upload_next' ) );

		add_action( 'admin_post_alynt_drime_wpvivid_preview_remote_retention', array( $this, 'handle_preview_remote_retention' ) );

		add_action( 'admin_post_alynt_drime_wpvivid_run_remote_retention', array( $this, 'handle_run_remote_retention' ) );

		add_action( 'admin_post_alynt_drime_wpvivid_clear_active_upload', array( $this, 'handle_clear_active_upload' ) );

		add_action( 'admin_post_alynt_drime_wpvivid_export_diagnostics', array( $this, 'handle_export_diagnostics' ) );

		add_action( 'admin_post_alynt_drime_wpvivid_clear_diagnostics', array( $this, 'handle_clear_diagnostics' ) );

		$this->cron->hooks();
	}

	/**
	 * Scans and queues stable files.
	 *
	 * @return array<string,mixed>
	 *
	 * @since 0.1.0
	 */
	public function scan_and_queue() {
		$result = $this->scanner->scan();

		if ( ! empty( $result['errors'] ) ) {
			$this->log_scan_errors( $result['errors'] );
			return $result;
		}

		$queued = $this->queue_scan_candidates( $result['candidates'] );
		$this->logger->event(
			'scanner',
			'info',
			'scan_finished',
			'Backup scan finished.',
			array(
				'found'  => count( $result['candidates'] ),
				'queued' => $queued,
			)
		);

		return $result;
	}

	/**
	 * Logs scan errors.
	 *
	 * @param array<int,string> $errors Errors.
	 * @return void
	 *
	 * @since 0.1.0
	 */
	private function log_scan_errors( array $errors ) {
		foreach ( $errors as $error ) {
			$this->logger->event( 'scanner', 'error', 'scan_error', $error );
		}
	}

	/**
	 * Queues candidates that have not already uploaded.
	 *
	 * @param array<int,array<string,mixed>> $candidates Candidates.
	 * @return int
	 */
	private function queue_scan_candidates( array $candidates ) {
		return $this->queue->add_many( $candidates, $this->registry->get_uploaded() );
	}

	/**

	 * Settings getter.
	 *
	 * @return Alynt_Drime_WPvivid_Uploader_Settings
	 *
	 * @since 0.1.0
	 */
	public function settings() {

		return $this->settings;
	}

	/**

	 * Logger getter.
	 *
	 * @return Alynt_Drime_WPvivid_Uploader_Logger
	 *
	 * @since 0.1.0
	 */
	public function logger() {

		return $this->logger;
	}

	/**

	 * Detector getter.
	 *
	 * @return Alynt_Drime_WPvivid_Uploader_WPvivid_Detector
	 *
	 * @since 0.1.0
	 */
	public function detector() {

		return $this->detector;
	}

	/**

	 * Registry getter.
	 *
	 * @return Alynt_Drime_WPvivid_Uploader_Backup_Registry
	 *
	 * @since 0.1.0
	 */
	public function registry() {

		return $this->registry;
	}

	/**

	 * Queue getter.
	 *
	 * @return Alynt_Drime_WPvivid_Uploader_Queue
	 *
	 * @since 0.1.0
	 */
	public function queue() {

		return $this->queue;
	}

	/**

	 * Uploader getter.
	 *
	 * @return Alynt_Drime_WPvivid_Uploader_Uploader
	 *
	 * @since 0.1.0
	 */
	public function uploader() {

		return $this->uploader;
	}

	/**
	 * Remote retention getter.
	 *
	 * @return Alynt_Drime_WPvivid_Uploader_Remote_Retention
	 *
	 * @since 0.1.0
	 */
	public function retention() {

		return $this->retention;
	}
}

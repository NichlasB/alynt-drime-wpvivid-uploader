<?php

/**

 * Plugin orchestrator.

 *

 * @package Alynt_Drime_WPvivid_Uploader

 */

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

/**

 * Main plugin class.

 */

class Alynt_Drime_WPvivid_Uploader_Plugin {

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

	 * Admin page.

	 *

	 * @var Alynt_Drime_WPvivid_Uploader_Admin_Page

	 */

	private $admin_page;

	/**

	 * Constructor.

	 */

	public function __construct() {

		$this->settings   = new Alynt_Drime_WPvivid_Uploader_Settings();

		$this->logger     = new Alynt_Drime_WPvivid_Uploader_Logger( $this->settings );

		$this->detector   = new Alynt_Drime_WPvivid_Uploader_WPvivid_Detector();

		$this->scanner    = new Alynt_Drime_WPvivid_Uploader_Scanner( $this->settings, $this->detector, $this->logger );

		$this->registry   = new Alynt_Drime_WPvivid_Uploader_Backup_Registry();

		$this->queue      = new Alynt_Drime_WPvivid_Uploader_Queue();

		$this->client     = new Alynt_Drime_WPvivid_Uploader_Drime_Client( $this->settings, $this->logger );

		$this->uploader   = new Alynt_Drime_WPvivid_Uploader_Uploader( $this->settings, $this->client, $this->queue, $this->registry, $this->logger );

		$this->cron       = new Alynt_Drime_WPvivid_Uploader_Cron( $this );

		$this->admin_page = new Alynt_Drime_WPvivid_Uploader_Admin_Page( $this );

		$this->hooks();

	}

	/**

	 * Registers hooks.

	 *

	 * @return void

	 */

	private function hooks() {

		add_action( 'admin_menu', array( $this->admin_page, 'register_menu' ) );

		add_action( 'admin_enqueue_scripts', array( $this->admin_page, 'enqueue_assets' ) );

		add_action( 'admin_post_alynt_drime_wpvivid_save_settings', array( $this, 'handle_save_settings' ) );

		add_action( 'admin_post_alynt_drime_wpvivid_test_connection', array( $this, 'handle_test_connection' ) );

		add_action( 'admin_post_alynt_drime_wpvivid_scan_now', array( $this, 'handle_scan_now' ) );

		add_action( 'admin_post_alynt_drime_wpvivid_upload_next', array( $this, 'handle_upload_next' ) );

		add_action( 'admin_post_alynt_drime_wpvivid_export_diagnostics', array( $this, 'handle_export_diagnostics' ) );

		add_action( 'admin_post_alynt_drime_wpvivid_clear_diagnostics', array( $this, 'handle_clear_diagnostics' ) );

		$this->cron->hooks();

	}

	/**

	 * Saves settings.

	 *

	 * @return void

	 */

	public function handle_save_settings() {

		$this->verify_admin_action( 'alynt_drime_wpvivid_save_settings' );

		$raw = isset( $_POST['alynt_drime_wpvivid_settings'] ) && is_array( $_POST['alynt_drime_wpvivid_settings'] ) ? wp_unslash( $_POST['alynt_drime_wpvivid_settings'] ) : array();

		$this->settings->update( $raw );

		$this->logger->event( 'admin_action', 'info', 'settings_saved', 'Settings saved.' );

		$this->redirect( 'settings_saved' );

	}

	/**

	 * Tests Drime connection.

	 *

	 * @return void

	 */

	public function handle_test_connection() {

		$this->verify_admin_action( 'alynt_drime_wpvivid_test_connection' );

		$result = $this->client->test_connection();

		if ( is_wp_error( $result ) ) {

			$this->logger->event( 'external_api', 'error', 'connection_test_failed', 'Drime connection test failed.', array( 'reason' => $result->get_error_message() ) );

			$this->redirect( 'action_failed' );

		}

		$this->logger->event( 'external_api', 'info', 'connection_test_succeeded', 'Drime connection test succeeded.' );

		$this->redirect( 'connected' );

	}

	/**

	 * Scans now.

	 *

	 * @return void

	 */

	public function handle_scan_now() {

		$this->verify_admin_action( 'alynt_drime_wpvivid_scan_now' );

		$this->scan_and_queue();

		$this->redirect( 'scan_complete' );

	}

	/**

	 * Uploads next queued backup.

	 *

	 * @return void

	 */

	public function handle_upload_next() {

		$this->verify_admin_action( 'alynt_drime_wpvivid_upload_next' );

		$result = $this->uploader->upload_next();

		if ( is_wp_error( $result ) ) {

			$this->logger->event( 'upload', 'error', 'manual_upload_failed', 'Manual upload failed.', array( 'reason' => $result->get_error_message() ) );

			$this->redirect( 'action_failed' );

		}

		$this->redirect( 'upload_done' );

	}

	/**

	 * Scans and queues stable files.

	 *

	 * @return array<string,mixed>

	 */

	public function scan_and_queue() {

		$result = $this->scanner->scan();

		$queued = 0;

		if ( ! empty( $result['errors'] ) ) {

			foreach ( $result['errors'] as $error ) {

				$this->logger->event( 'scanner', 'error', 'scan_error', $error );

			}

			return $result;

		}

		foreach ( $result['candidates'] as $candidate ) {

			if ( $this->registry->is_uploaded( (string) $candidate['signature'] ) ) {

				continue;

			}

			if ( $this->queue->add( $candidate ) ) {

				$queued++;

			}

		}

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

	 * Exports diagnostics as JSON.

	 *

	 * @return void

	 */

	public function handle_export_diagnostics() {

		$this->verify_admin_action( 'alynt_drime_wpvivid_export_diagnostics' );

		nocache_headers();

		header( 'Content-Type: application/json; charset=utf-8' );

		header( 'Content-Disposition: attachment; filename="alynt-drime-wpvivid-diagnostics-' . gmdate( 'Ymd-His' ) . '.json"' );

		echo wp_json_encode( $this->logger->export_payload(), JSON_PRETTY_PRINT );

		exit;

	}

	/**

	 * Clears diagnostics events.

	 *

	 * @return void

	 */

	public function handle_clear_diagnostics() {

		$this->verify_admin_action( 'alynt_drime_wpvivid_clear_diagnostics' );

		$this->logger->clear();

		$this->redirect( 'diagnostics_cleared' );

	}

	/**

	 * Settings getter.

	 *

	 * @return Alynt_Drime_WPvivid_Uploader_Settings

	 */

	public function settings() {

		return $this->settings;

	}

	/**

	 * Logger getter.

	 *

	 * @return Alynt_Drime_WPvivid_Uploader_Logger

	 */

	public function logger() {

		return $this->logger;

	}

	/**

	 * Detector getter.

	 *

	 * @return Alynt_Drime_WPvivid_Uploader_WPvivid_Detector

	 */

	public function detector() {

		return $this->detector;

	}

	/**

	 * Registry getter.

	 *

	 * @return Alynt_Drime_WPvivid_Uploader_Backup_Registry

	 */

	public function registry() {

		return $this->registry;

	}

	/**

	 * Queue getter.

	 *

	 * @return Alynt_Drime_WPvivid_Uploader_Queue

	 */

	public function queue() {

		return $this->queue;

	}

	/**

	 * Uploader getter.

	 *

	 * @return Alynt_Drime_WPvivid_Uploader_Uploader

	 */

	public function uploader() {

		return $this->uploader;

	}

	/**

	 * Cron getter.

	 *

	 * @return Alynt_Drime_WPvivid_Uploader_Cron

	 */

	public function cron() {

		return $this->cron;

	}

	/**

	 * Verifies an admin action.

	 *

	 * @param string $action Action.

	 * @return void

	 */

	private function verify_admin_action( $action ) {

		if ( ! current_user_can( 'manage_options' ) ) {

			wp_die( esc_html__( 'You do not have permission to manage this plugin.', 'alynt-drime-wpvivid-uploader' ) );

		}

		check_admin_referer( $action );

	}

	/**

	 * Redirects to admin page.

	 *

	 * @param string $notice Notice key.

	 * @return void

	 */

	private function redirect( $notice ) {

		wp_safe_redirect(

			add_query_arg(

				array(

					'page'         => 'alynt-drime-wpvivid-uploader',

					'alynt_notice' => sanitize_key( $notice ),

				),

				admin_url( 'tools.php' )

			)

		);

		exit;

	}

}

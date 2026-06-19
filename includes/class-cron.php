<?php
/**
 * Cron integration.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and handles WP-Cron events.
 */
class Alynt_Drime_WPvivid_Uploader_Cron {
	const SCAN_EVENT   = 'alynt_drime_wpvivid_scan_event';
	const UPLOAD_EVENT = 'alynt_drime_wpvivid_upload_event';

	/**
	 * Plugin.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Alynt_Drime_WPvivid_Uploader_Plugin $plugin Plugin.
	 */
	public function __construct( Alynt_Drime_WPvivid_Uploader_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds cron hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_filter( 'cron_schedules', array( $this, 'add_schedules' ) );
		add_action( self::SCAN_EVENT, array( $this, 'scan' ) );
		add_action( self::UPLOAD_EVENT, array( $this, 'upload' ) );
		add_action( 'init', array( $this, 'maybe_schedule' ) );
	}

	/**
	 * Adds schedules.
	 *
	 * @param array<string,array<string,mixed>> $schedules Schedules.
	 * @return array<string,array<string,mixed>>
	 */
	public function add_schedules( $schedules ) {
		$schedules['fifteen_minutes'] = array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every 15 minutes', 'alynt-drime-wpvivid-uploader' ),
		);

		return $schedules;
	}

	/**
	 * Schedules or clears events.
	 *
	 * @return void
	 */
	public function maybe_schedule() {
		$settings = $this->plugin->settings()->get();

		if ( ! empty( $settings['auto_scan_enabled'] ) ) {
			if ( ! wp_next_scheduled( self::SCAN_EVENT ) ) {
				wp_schedule_event( time() + MINUTE_IN_SECONDS, 'fifteen_minutes', self::SCAN_EVENT );
			}

			if ( ! wp_next_scheduled( self::UPLOAD_EVENT ) ) {
				wp_schedule_event( time() + ( 2 * MINUTE_IN_SECONDS ), 'fifteen_minutes', self::UPLOAD_EVENT );
			}
			return;
		}

		$this->clear();
	}

	/**
	 * Clears scheduled events.
	 *
	 * @return void
	 */
	public function clear() {
		wp_clear_scheduled_hook( self::SCAN_EVENT );
		wp_clear_scheduled_hook( self::UPLOAD_EVENT );
	}

	/**
	 * Runs a scan.
	 *
	 * @return void
	 */
	public function scan() {
		$this->plugin->logger()->event( 'cron', 'info', 'scan_started', 'Scheduled scan started.' );
		$this->plugin->scan_and_queue();
		$this->plugin->logger()->event( 'cron', 'info', 'scan_finished', 'Scheduled scan finished.' );
	}

	/**
	 * Uploads next queued file.
	 *
	 * @return void
	 */
	public function upload() {
		$this->plugin->logger()->event( 'cron', 'info', 'upload_worker_started', 'Scheduled upload worker started.' );
		$result = $this->plugin->uploader()->upload_next();
		if ( is_wp_error( $result ) ) {
			$this->plugin->logger()->event( 'cron', 'warning', 'upload_worker_no_upload', 'Scheduled upload worker did not complete an upload.', array( 'reason' => $result->get_error_message() ) );
			return;
		}
		$this->plugin->logger()->event( 'cron', 'info', 'upload_worker_finished', 'Scheduled upload worker finished.' );
	}
}

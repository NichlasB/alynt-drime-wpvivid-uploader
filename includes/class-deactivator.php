<?php
/**
 * Deactivation tasks.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivation handler.
 */
class Alynt_Drime_WPvivid_Uploader_Deactivator {
	/**
	 * Runs on deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( Alynt_Drime_WPvivid_Uploader_Cron::SCAN_EVENT );
		wp_clear_scheduled_hook( Alynt_Drime_WPvivid_Uploader_Cron::UPLOAD_EVENT );
	}
}

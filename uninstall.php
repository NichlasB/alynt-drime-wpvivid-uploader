<?php
/**
 * Uninstall cleanup.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$alynt_drime_wpvivid_options = array(
	'alynt_drime_wpvivid_settings',
	'alynt_drime_wpvivid_uploaded_files',
	'alynt_drime_wpvivid_failed_uploads',
	'alynt_drime_wpvivid_upload_queue',
	'alynt_drime_wpvivid_active_upload',
	'alynt_drime_wpvivid_logs',
	'alynt_drime_wpvivid_file_snapshots',
);

foreach ( $alynt_drime_wpvivid_options as $alynt_drime_wpvivid_option ) {
	delete_option( $alynt_drime_wpvivid_option );
}

wp_clear_scheduled_hook( 'alynt_drime_wpvivid_scan_event' );
wp_clear_scheduled_hook( 'alynt_drime_wpvivid_upload_event' );

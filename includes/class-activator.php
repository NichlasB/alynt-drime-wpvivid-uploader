<?php
/**
 * Activation tasks.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation handler.
 */
class Alynt_Drime_WPvivid_Uploader_Activator {
	/**
	 * Runs on activation.
	 *
	 * @return void
	 */
	public static function activate() {
		Alynt_Drime_WPvivid_Uploader_Settings::maybe_install();
	}
}

<?php
/**
 * Activation tasks.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 * @since   0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation handler.
 *
 * @since 0.1.0
 */
class Alynt_Drime_WPvivid_Uploader_Activator {
	/**
	 * Runs on activation.
	 *
	 * @return void
	 *
	 * @since 0.1.0
	 */
	public static function activate() {
		Alynt_Drime_WPvivid_Uploader_Settings::maybe_install();
	}
}

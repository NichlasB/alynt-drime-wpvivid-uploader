<?php
/**
 * Plugin Name:       Alynt Drime WPvivid Uploader
 * Plugin URI:        https://alynt.com/
 * Description:       Upload completed WPvivid local backup archives to Drime.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Alynt
 * Author URI:        https://alynt.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       alynt-drime-wpvivid-uploader
 * Domain Path:       /languages
 *
 * @package Alynt_Drime_WPvivid_Uploader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ALYNT_DRIME_WPVIVID_UPLOADER_VERSION', '0.1.0' );
define( 'ALYNT_DRIME_WPVIVID_UPLOADER_FILE', __FILE__ );
define( 'ALYNT_DRIME_WPVIVID_UPLOADER_PATH', plugin_dir_path( __FILE__ ) );
define( 'ALYNT_DRIME_WPVIVID_UPLOADER_URL', plugin_dir_url( __FILE__ ) );
define( 'ALYNT_DRIME_WPVIVID_UPLOADER_BASENAME', plugin_basename( __FILE__ ) );

$alynt_drime_wpvivid_uploader_includes = array(
	'includes/class-settings.php',
	'includes/class-logger.php',
	'includes/class-wpvivid-detector.php',
	'includes/class-scanner.php',
	'includes/class-backup-registry.php',
	'includes/class-queue.php',
	'includes/class-drime-client.php',
	'includes/class-uploader.php',
	'includes/class-cron.php',
	'includes/class-admin-page.php',
	'includes/class-activator.php',
	'includes/class-deactivator.php',
	'includes/class-plugin.php',
);

foreach ( $alynt_drime_wpvivid_uploader_includes as $alynt_drime_wpvivid_uploader_include ) {
	require_once ALYNT_DRIME_WPVIVID_UPLOADER_PATH . $alynt_drime_wpvivid_uploader_include;
}

register_activation_hook( __FILE__, array( 'Alynt_Drime_WPvivid_Uploader_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Alynt_Drime_WPvivid_Uploader_Deactivator', 'deactivate' ) );

/**
 * Returns the plugin singleton.
 *
 * @return Alynt_Drime_WPvivid_Uploader_Plugin
 */
function alynt_drime_wpvivid_uploader() {
	static $alynt_drime_wpvivid_uploader_plugin = null;

	if ( null === $alynt_drime_wpvivid_uploader_plugin ) {
		$alynt_drime_wpvivid_uploader_plugin = new Alynt_Drime_WPvivid_Uploader_Plugin();
	}

	return $alynt_drime_wpvivid_uploader_plugin;
}

add_action( 'plugins_loaded', 'alynt_drime_wpvivid_uploader' );

<?php
/**
 * Admin page.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 * @since   0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the plugin admin UI.
 *
 * @since 0.1.0
 */
class Alynt_Drime_WPvivid_Uploader_Admin_Page {
	use Alynt_Drime_WPvivid_Uploader_Admin_Page_Notices;
	use Alynt_Drime_WPvivid_Uploader_Admin_Page_Settings;
	use Alynt_Drime_WPvivid_Uploader_Admin_Page_Status;

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
	 *
	 * @since 0.1.0
	 */
	public function __construct( Alynt_Drime_WPvivid_Uploader_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Registers the admin menu.
	 *
	 * @return void
	 *
	 * @since 0.1.0
	 */
	public function register_menu() {
		add_management_page(
			__( 'Drime WPvivid Uploader', 'alynt-drime-wpvivid-uploader' ),
			__( 'Drime WPvivid', 'alynt-drime-wpvivid-uploader' ),
			'manage_options',
			'alynt-drime-wpvivid-uploader',
			array( $this, 'render' )
		);
	}

	/**
	 * Enqueues admin assets.
	 *
	 * @param string $hook Hook suffix.
	 * @return void
	 *
	 * @since 0.1.0
	 */
	public function enqueue_assets( $hook ) {
		if ( 'tools_page_alynt-drime-wpvivid-uploader' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'alynt-drime-wpvivid-uploader-admin',
			ALYNT_DRIME_WPVIVID_UPLOADER_URL . 'assets/admin.css',
			array(),
			ALYNT_DRIME_WPVIVID_UPLOADER_VERSION
		);

		wp_enqueue_script(
			'alynt-drime-wpvivid-uploader-admin',
			ALYNT_DRIME_WPVIVID_UPLOADER_URL . 'assets/admin.js',
			array(),
			ALYNT_DRIME_WPVIVID_UPLOADER_VERSION,
			true
		);
	}

	/**
	 * Renders the page.
	 *
	 * @return void
	 *
	 * @since 0.1.0
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage this plugin.', 'alynt-drime-wpvivid-uploader' ) );
		}

		$settings      = $this->plugin->settings()->get();
		$detected_path = $this->plugin->detector()->get_backup_dir( $settings );
		$queue         = $this->plugin->queue()->all();
		$active        = $this->plugin->queue()->get_active();
		$uploaded      = $this->plugin->registry()->get_uploaded();
		$failed        = $this->plugin->registry()->get_failed();
		$retention     = $this->plugin->retention()->preview();
		$events        = $this->plugin->logger()->get_events();
		$diagnostics   = $this->plugin->logger()->stats();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Notice rendering is read-only.
		$notice = isset( $_GET['alynt_notice'] ) ? sanitize_key( wp_unslash( $_GET['alynt_notice'] ) ) : '';

		?>
		<div class="wrap alynt-drime-wpvivid">
			<h1><?php esc_html_e( 'Drime WPvivid Uploader', 'alynt-drime-wpvivid-uploader' ); ?></h1>
			<?php $this->render_notice( $notice ); ?>
			<hr class="wp-header-end">
			<?php
			$this->render_settings_form( $settings, $detected_path );
			$this->render_manual_actions();
			$this->render_status_summary( $queue, $uploaded, $failed );
			$this->render_active_upload_state( $active );
			$this->render_remote_retention_status( $settings, $retention );
			$this->render_diagnostics_panel( $settings, $diagnostics );
			$this->render_recent_events( $events );
			?>
		</div>
		<?php
	}
}

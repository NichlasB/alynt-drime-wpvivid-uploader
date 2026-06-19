<?php

/**

 * Admin page.

 *

 * @package Alynt_Drime_WPvivid_Uploader

 */

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

/**

 * Renders the plugin admin UI.

 */

class Alynt_Drime_WPvivid_Uploader_Admin_Page {

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

	 * Registers the admin menu.

	 *

	 * @return void

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

	 */

	public function render() {

		if ( ! current_user_can( 'manage_options' ) ) {

			wp_die( esc_html__( 'You do not have permission to manage this plugin.', 'alynt-drime-wpvivid-uploader' ) );

		}

		$settings       = $this->plugin->settings()->get();

		$detected_path  = $this->plugin->detector()->get_backup_dir( $settings );

		$queue          = $this->plugin->queue()->all();

		$active         = $this->plugin->queue()->get_active();

		$uploaded       = $this->plugin->registry()->get_uploaded();

		$failed         = $this->plugin->registry()->get_failed();

		$events         = $this->plugin->logger()->get_events();
		$diagnostics    = $this->plugin->logger()->stats();

		$notice         = isset( $_GET['alynt_notice'] ) ? sanitize_key( wp_unslash( $_GET['alynt_notice'] ) ) : '';

		?>

		<div class="wrap alynt-drime-wpvivid">

			<h1><?php esc_html_e( 'Drime WPvivid Uploader', 'alynt-drime-wpvivid-uploader' ); ?></h1>

			<?php $this->render_notice( $notice ); ?>

			<hr class="wp-header-end">

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

				<input type="hidden" name="action" value="alynt_drime_wpvivid_save_settings">

				<?php wp_nonce_field( 'alynt_drime_wpvivid_save_settings' ); ?>

				<h2><?php esc_html_e( 'Drime', 'alynt-drime-wpvivid-uploader' ); ?></h2>

				<table class="form-table" role="presentation">

					<tr>

						<th scope="row"><label for="alynt-api-token"><?php esc_html_e( 'API Token', 'alynt-drime-wpvivid-uploader' ); ?></label></th>

						<td>

							<input id="alynt-api-token" name="alynt_drime_wpvivid_settings[api_token]" type="password" class="regular-text" value="<?php echo esc_attr( '' === $settings['api_token'] ? '' : '************' ); ?>" autocomplete="off">

							<p class="description"><?php esc_html_e( 'Enter a Drime bearer token. Leave masked value unchanged to keep the saved token.', 'alynt-drime-wpvivid-uploader' ); ?></p>

						</td>

					</tr>

					<tr>

						<th scope="row"><label for="alynt-workspace-id"><?php esc_html_e( 'Workspace ID', 'alynt-drime-wpvivid-uploader' ); ?></label></th>

						<td>

							<input id="alynt-workspace-id" name="alynt_drime_wpvivid_settings[workspace_id]" type="number" min="0" value="<?php echo esc_attr( (string) $settings['workspace_id'] ); ?>">

							<p class="description"><?php esc_html_e( 'Use 0 for your personal/default Drime workspace.', 'alynt-drime-wpvivid-uploader' ); ?></p>

						</td>

					</tr>

					<tr>

						<th scope="row"><label for="alynt-parent-folder-id"><?php esc_html_e( 'Parent Folder ID', 'alynt-drime-wpvivid-uploader' ); ?></label></th>

						<td>

							<input id="alynt-parent-folder-id" name="alynt_drime_wpvivid_settings[parent_folder_id]" type="number" min="0" value="<?php echo esc_attr( (string) $settings['parent_folder_id'] ); ?>">

							<p class="description"><?php esc_html_e( 'Leave empty for the Drime root folder. You can also use a relative path below.', 'alynt-drime-wpvivid-uploader' ); ?></p>

						</td>

					</tr>

					<tr>

						<th scope="row"><label for="alynt-relative-path"><?php esc_html_e( 'Relative Path', 'alynt-drime-wpvivid-uploader' ); ?></label></th>

						<td>

							<input id="alynt-relative-path" name="alynt_drime_wpvivid_settings[relative_path]" type="text" class="regular-text" value="<?php echo esc_attr( (string) $settings['relative_path'] ); ?>" placeholder="/WPvivid Backups">

							<p class="description"><?php esc_html_e( 'Optional Drime folder path. Drime can auto-create missing folders when this is provided.', 'alynt-drime-wpvivid-uploader' ); ?></p>

						</td>

					</tr>

				</table>

				<h2><?php esc_html_e( 'WPvivid Source', 'alynt-drime-wpvivid-uploader' ); ?></h2>

				<table class="form-table" role="presentation">

					<tr>

						<th scope="row"><?php esc_html_e( 'Detected Backup Path', 'alynt-drime-wpvivid-uploader' ); ?></th>

						<td><code><?php echo esc_html( $detected_path ); ?></code></td>

					</tr>

					<tr>

						<th scope="row"><label for="alynt-backup-path-override"><?php esc_html_e( 'Backup Path Override', 'alynt-drime-wpvivid-uploader' ); ?></label></th>

						<td>

							<input id="alynt-backup-path-override" name="alynt_drime_wpvivid_settings[backup_path_override]" type="text" class="large-text code" value="<?php echo esc_attr( (string) $settings['backup_path_override'] ); ?>">

							<p class="description"><?php esc_html_e( 'Optional. Use only if WPvivid stores local backups outside the detected path.', 'alynt-drime-wpvivid-uploader' ); ?></p>

						</td>

					</tr>

				</table>

				<h2><?php esc_html_e( 'Behavior', 'alynt-drime-wpvivid-uploader' ); ?></h2>

				<table class="form-table" role="presentation">

					<tr>

						<th scope="row"><label for="alynt-duplicate-mode"><?php esc_html_e( 'Duplicate Handling', 'alynt-drime-wpvivid-uploader' ); ?></label></th>

						<td>

							<select id="alynt-duplicate-mode" name="alynt_drime_wpvivid_settings[duplicate_mode]">

								<option value="skip" <?php selected( $settings['duplicate_mode'], 'skip' ); ?>><?php esc_html_e( 'Skip existing files', 'alynt-drime-wpvivid-uploader' ); ?></option>

								<option value="rename" <?php selected( $settings['duplicate_mode'], 'rename' ); ?>><?php esc_html_e( 'Rename new uploads', 'alynt-drime-wpvivid-uploader' ); ?></option>

							</select>

						</td>

					</tr>

					<tr>

						<th scope="row"><label for="alynt-auto-scan"><?php esc_html_e( 'Automatic Scanning', 'alynt-drime-wpvivid-uploader' ); ?></label></th>

						<td>

							<label><input id="alynt-auto-scan" name="alynt_drime_wpvivid_settings[auto_scan_enabled]" type="checkbox" value="1" <?php checked( ! empty( $settings['auto_scan_enabled'] ) ); ?>> <?php esc_html_e( 'Scan with WP-Cron every 15 minutes.', 'alynt-drime-wpvivid-uploader' ); ?></label>

						</td>

					</tr>

					<tr>

						<th scope="row"><label for="alynt-min-file-age"><?php esc_html_e( 'Minimum File Age', 'alynt-drime-wpvivid-uploader' ); ?></label></th>

						<td>

							<input id="alynt-min-file-age" name="alynt_drime_wpvivid_settings[min_file_age_seconds]" type="number" min="60" step="60" value="<?php echo esc_attr( (string) $settings['min_file_age_seconds'] ); ?>">

							<p class="description"><?php esc_html_e( 'Files must also keep the same size across scans before they are queued.', 'alynt-drime-wpvivid-uploader' ); ?></p>

						</td>

					</tr>

					<tr>

						<th scope="row"><label for="alynt-delete-local"><?php esc_html_e( 'Delete Local Files', 'alynt-drime-wpvivid-uploader' ); ?></label></th>

						<td>

							<label><input id="alynt-delete-local" name="alynt_drime_wpvivid_settings[delete_local_after_upload]" type="checkbox" value="1" <?php checked( ! empty( $settings['delete_local_after_upload'] ) ); ?>> <?php esc_html_e( 'Delete local backup files after confirmed Drime upload.', 'alynt-drime-wpvivid-uploader' ); ?></label>

							<p class="description"><?php esc_html_e( 'Keep this off until end-to-end testing is complete.', 'alynt-drime-wpvivid-uploader' ); ?></p>

						</td>

					</tr>

					<tr>

						<th scope="row"><label for="alynt-max-retries"><?php esc_html_e( 'Maximum Retries', 'alynt-drime-wpvivid-uploader' ); ?></label></th>

						<td><input id="alynt-max-retries" name="alynt_drime_wpvivid_settings[max_retries]" type="number" min="0" max="10" value="<?php echo esc_attr( (string) $settings['max_retries'] ); ?>"></td>

					</tr>

				</table>

				<?php submit_button( __( 'Save Settings', 'alynt-drime-wpvivid-uploader' ) ); ?>

			</form>

			<h2><?php esc_html_e( 'Manual Actions', 'alynt-drime-wpvivid-uploader' ); ?></h2>

			<div class="alynt-drime-wpvivid-actions">

				<?php $this->render_action_button( 'alynt_drime_wpvivid_test_connection', __( 'Test Drime Connection', 'alynt-drime-wpvivid-uploader' ) ); ?>

				<?php $this->render_action_button( 'alynt_drime_wpvivid_scan_now', __( 'Scan Backup Folder', 'alynt-drime-wpvivid-uploader' ) ); ?>

				<?php $this->render_action_button( 'alynt_drime_wpvivid_upload_next', __( 'Upload Next Queued Backup', 'alynt-drime-wpvivid-uploader' ) ); ?>

			</div>

			<h2><?php esc_html_e( 'Status', 'alynt-drime-wpvivid-uploader' ); ?></h2>

			<div class="alynt-drime-wpvivid-status-grid">

				<?php $this->render_status_box( __( 'Queued', 'alynt-drime-wpvivid-uploader' ), count( $queue ) ); ?>

				<?php $this->render_status_box( __( 'Uploaded', 'alynt-drime-wpvivid-uploader' ), count( $uploaded ) ); ?>

				<?php $this->render_status_box( __( 'Failed', 'alynt-drime-wpvivid-uploader' ), count( $failed ) ); ?>

			</div>

			<?php if ( ! empty( $active ) ) : ?>

				<h3><?php esc_html_e( 'Active Upload', 'alynt-drime-wpvivid-uploader' ); ?></h3>

				<p><code><?php echo esc_html( wp_json_encode( $active ) ); ?></code></p>

			<?php endif; ?>

			<h3><?php esc_html_e( 'Diagnostics', 'alynt-drime-wpvivid-uploader' ); ?></h3>
			<div class="alynt-drime-wpvivid-diagnostics-actions">
				<?php $this->render_action_button( 'alynt_drime_wpvivid_export_diagnostics', __( 'Export Diagnostics', 'alynt-drime-wpvivid-uploader' ) ); ?>
				<?php $this->render_action_button( 'alynt_drime_wpvivid_clear_diagnostics', __( 'Clear Diagnostics', 'alynt-drime-wpvivid-uploader' ), true ); ?>
			</div>
			<table class="widefat striped alynt-drime-wpvivid-health">
				<tbody>
					<tr><th><?php esc_html_e( 'Plugin Version', 'alynt-drime-wpvivid-uploader' ); ?></th><td><?php echo esc_html( ALYNT_DRIME_WPVIVID_UPLOADER_VERSION ); ?></td></tr>
					<tr><th><?php esc_html_e( 'WordPress Version', 'alynt-drime-wpvivid-uploader' ); ?></th><td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'PHP Version', 'alynt-drime-wpvivid-uploader' ); ?></th><td><?php echo esc_html( PHP_VERSION ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Diagnostics Enabled', 'alynt-drime-wpvivid-uploader' ); ?></th><td><?php echo ! empty( $settings['diagnostics_enabled'] ) ? esc_html__( 'Yes', 'alynt-drime-wpvivid-uploader' ) : esc_html__( 'No', 'alynt-drime-wpvivid-uploader' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Stored Events', 'alynt-drime-wpvivid-uploader' ); ?></th><td><?php echo esc_html( number_format_i18n( (int) $diagnostics['count'] ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Last Event', 'alynt-drime-wpvivid-uploader' ); ?></th><td><?php echo '' === $diagnostics['last_event'] ? esc_html__( 'None', 'alynt-drime-wpvivid-uploader' ) : esc_html( $diagnostics['last_event'] ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Scan Cron Scheduled', 'alynt-drime-wpvivid-uploader' ); ?></th><td><?php echo wp_next_scheduled( Alynt_Drime_WPvivid_Uploader_Cron::SCAN_EVENT ) ? esc_html__( 'Yes', 'alynt-drime-wpvivid-uploader' ) : esc_html__( 'No', 'alynt-drime-wpvivid-uploader' ); ?></td></tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Recent Events', 'alynt-drime-wpvivid-uploader' ); ?></h3>

			<?php if ( empty( $events ) ) : ?>

				<div class="alynt-drime-wpvivid-empty">

					<h2><?php esc_html_e( 'No events yet', 'alynt-drime-wpvivid-uploader' ); ?></h2>

					<p><?php esc_html_e( 'Scans, uploads, and connection checks will appear here.', 'alynt-drime-wpvivid-uploader' ); ?></p>

				</div>

			<?php else : ?>

				<table class="widefat striped">

					<thead>

						<tr>

							<th><?php esc_html_e( 'Time', 'alynt-drime-wpvivid-uploader' ); ?></th>

							<th><?php esc_html_e( 'Level', 'alynt-drime-wpvivid-uploader' ); ?></th>

							<th><?php esc_html_e( 'Category', 'alynt-drime-wpvivid-uploader' ); ?></th>

							<th><?php esc_html_e( 'Code', 'alynt-drime-wpvivid-uploader' ); ?></th>

							<th><?php esc_html_e( 'Message', 'alynt-drime-wpvivid-uploader' ); ?></th>

						</tr>

					</thead>

					<tbody>

						<?php foreach ( $events as $event ) : ?>

							<tr>

								<td><?php echo esc_html( wp_date( 'Y-m-d H:i:s', isset( $event['time'] ) ? (int) $event['time'] : time() ) ); ?></td>

								<td><?php echo esc_html( isset( $event['level'] ) ? (string) $event['level'] : '' ); ?></td>

								<td><?php echo esc_html( isset( $event['category'] ) ? (string) $event['category'] : '' ); ?></td>

								<td><?php echo esc_html( isset( $event['code'] ) ? (string) $event['code'] : '' ); ?></td>

								<td><?php echo esc_html( isset( $event['message'] ) ? (string) $event['message'] : '' ); ?></td>

							</tr>

						<?php endforeach; ?>

					</tbody>

				</table>

			<?php endif; ?>

		</div>

		<?php

	}

	/**

	 * Renders a notice.

	 *

	 * @param string $notice Notice key.

	 * @return void

	 */

	private function render_notice( $notice ) {

		$messages = array(

			'settings_saved' => array( 'success', __( 'Settings saved.', 'alynt-drime-wpvivid-uploader' ) ),

			'connected'      => array( 'success', __( 'Connected to Drime successfully.', 'alynt-drime-wpvivid-uploader' ) ),

			'scan_complete'  => array( 'success', __( 'Backup scan completed.', 'alynt-drime-wpvivid-uploader' ) ),

			'upload_done'    => array( 'success', __( 'Upload completed.', 'alynt-drime-wpvivid-uploader' ) ),

			'action_failed'  => array( 'error', __( 'The action could not be completed. Review the recent events for details.', 'alynt-drime-wpvivid-uploader' ) ),

		);

		if ( empty( $messages[ $notice ] ) ) {

			return;

		}

		list( $type, $message ) = $messages[ $notice ];

		printf(

			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',

			esc_attr( $type ),

			esc_html( $message )

		);

	}

	/**

	 * Renders an admin-post action button.

	 *

	 * @param string $action Action.

	 * @param string $label Label.

	 * @param bool   $confirm Whether to show confirmation.

	 * @return void

	 */

	private function render_action_button( $action, $label, $confirm = false ) {

		?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

			<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">

			<?php wp_nonce_field( $action ); ?>

			<button type="submit" class="button button-secondary" <?php echo $confirm ? 'data-alynt-confirm="' . esc_attr__( 'Clear all diagnostics events? This cannot be undone.', 'alynt-drime-wpvivid-uploader' ) . '"' : ''; ?>><?php echo esc_html( $label ); ?></button>

		</form>

		<?php

	}

	/**

	 * Renders a status box.

	 *

	 * @param string $label Label.

	 * @param int    $count Count.

	 * @return void

	 */

	private function render_status_box( $label, $count ) {

		?>

		<div class="alynt-drime-wpvivid-status-box">

			<strong><?php echo esc_html( number_format_i18n( $count ) ); ?></strong>

			<span><?php echo esc_html( $label ); ?></span>

		</div>

		<?php

	}

}

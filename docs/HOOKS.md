# Hooks

## Public Custom Hooks

Version `0.3.0` does not expose public custom actions or filters.

The plugin currently integrates with WordPress through core hooks only. Public extension hooks should be added deliberately when there is a stable use case and a documented backward-compatibility contract.

## WordPress Hooks Used Internally

| Hook | Type | Callback | Purpose |
| --- | --- | --- | --- |
| `plugins_loaded` | action | `alynt_drime_wpvivid_uploader_load_textdomain()` | Loads translations early. |
| `plugins_loaded` | action | `alynt_drime_wpvivid_uploader()` | Boots the plugin singleton. |
| `admin_notices` | action | `alynt_drime_wpvivid_uploader_requirements_notice()` | Shows minimum WordPress/PHP requirement failures. |
| `admin_menu` | action | `Alynt_Drime_WPvivid_Uploader_Admin_Page::register_menu()` | Registers the Tools admin page. |
| `admin_enqueue_scripts` | action | `Alynt_Drime_WPvivid_Uploader_Admin_Page::enqueue_assets()` | Loads admin CSS and JavaScript on the plugin page. |
| `admin_post_alynt_drime_wpvivid_save_settings` | action | `Alynt_Drime_WPvivid_Uploader_Plugin::handle_save_settings()` | Saves plugin settings. |
| `admin_post_alynt_drime_wpvivid_test_connection` | action | `Alynt_Drime_WPvivid_Uploader_Plugin::handle_test_connection()` | Tests the Drime API token. |
| `admin_post_alynt_drime_wpvivid_send_test_failure_email` | action | `Alynt_Drime_WPvivid_Uploader_Plugin::handle_send_test_failure_email()` | Sends a test failure-notification email through WordPress mail. |
| `admin_post_alynt_drime_wpvivid_scan_now` | action | `Alynt_Drime_WPvivid_Uploader_Plugin::handle_scan_now()` | Runs a manual WPvivid backup scan. |
| `admin_post_alynt_drime_wpvivid_upload_next` | action | `Alynt_Drime_WPvivid_Uploader_Plugin::handle_upload_next()` | Uploads the next queued backup. |
| `admin_post_alynt_drime_wpvivid_preview_remote_retention` | action | `Alynt_Drime_WPvivid_Uploader_Plugin::handle_preview_remote_retention()` | Previews eligible plugin-owned remote-retention candidates. |
| `admin_post_alynt_drime_wpvivid_run_remote_retention` | action | `Alynt_Drime_WPvivid_Uploader_Plugin::handle_run_remote_retention()` | Moves eligible plugin-owned Drime files to trash. |
| `admin_post_alynt_drime_wpvivid_clear_active_upload` | action | `Alynt_Drime_WPvivid_Uploader_Plugin::handle_clear_active_upload()` | Clears active upload state and aborts the remote multipart upload when possible. |
| `admin_post_alynt_drime_wpvivid_export_diagnostics` | action | `Alynt_Drime_WPvivid_Uploader_Plugin::handle_export_diagnostics()` | Exports redacted diagnostics as JSON. |
| `admin_post_alynt_drime_wpvivid_clear_diagnostics` | action | `Alynt_Drime_WPvivid_Uploader_Plugin::handle_clear_diagnostics()` | Clears stored diagnostics events. |
| `wp_ajax_alynt_drime_wpvivid_list_folders` | action | `Alynt_Drime_WPvivid_Uploader_Plugin::handle_ajax_list_folders()` | Lists Drime folders for the read-only admin folder browser. |
| `wp_ajax_alynt_drime_wpvivid_preview_destination` | action | `Alynt_Drime_WPvivid_Uploader_Plugin::handle_ajax_preview_destination()` | Previews the selected base folder plus relative subpath without creating folders. |
| `cron_schedules` | filter | `Alynt_Drime_WPvivid_Uploader_Cron::add_schedules()` | Adds the internal fifteen-minute scan interval. |
| `alynt_drime_wpvivid_scan_event` | action | `Alynt_Drime_WPvivid_Uploader_Cron::scan()` | Runs scheduled scanning. |
| `alynt_drime_wpvivid_upload_event` | action | `Alynt_Drime_WPvivid_Uploader_Cron::upload()` | Runs scheduled upload processing. |
| `init` | action | `Alynt_Drime_WPvivid_Uploader_Cron::maybe_schedule()` | Schedules or clears the scan hook based on settings. |

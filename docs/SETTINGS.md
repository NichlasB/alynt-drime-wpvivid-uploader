# Settings And Option Schema

## User Settings

Option name: `alynt_drime_wpvivid_settings`

| Option Key | Type | Default | Sanitization | UI Area | Description |
| --- | --- | --- | --- | --- | --- |
| `api_token` | string | `''` | `sanitize_text_field`; masked value preserves existing token | Drime | Drime bearer token used for API requests. Stored with autoload disabled. |
| `workspace_id` | integer | `0` | `absint`, minimum `0` | Drime | Drime workspace ID. `0` means the personal/default workspace. |
| `parent_folder_id` | string | `''` | empty string or `absint` string | Drime | Optional concrete Drime parent folder ID. |
| `relative_path` | string | `''` | `sanitize_text_field`, slash normalization, rejects `..` | Drime | Optional Drime destination path. Missing folders may be created by Drime. |
| `backup_path_override` | string | `''` | `sanitize_text_field` | WPvivid Source | Optional local WPvivid backup path override. |
| `duplicate_mode` | string | `skip` | `sanitize_key`, allowlist `skip` or `rename` | Behavior | Controls whether existing Drime filenames are skipped or renamed. |
| `auto_scan_enabled` | boolean | `false` | boolean cast from checkbox presence | Behavior | Enables scheduled WP-Cron scanning. |
| `scan_interval` | string | `fifteen_minutes` | internal fixed value | Internal | WP-Cron schedule key used by automatic scanning. |
| `min_file_age_seconds` | integer | `900` | `absint`, minimum `60` | Behavior | Minimum modified age before a file can be queued. |
| `multipart_chunk_size_mb` | integer | `32` | `absint`, range `5` to `64` | Behavior | Multipart upload part size in MB. `32` is recommended for large backups. |
| `delete_local_after_upload` | boolean | `false` | boolean cast from checkbox presence | Behavior | Deletes local backup files after confirmed upload when enabled. |
| `remote_retention_enabled` | boolean | `false` | boolean cast from checkbox presence | Behavior | Allows manual cleanup of old Drime files uploaded by this plugin. |
| `remote_retention_days` | integer | `60` | `absint`, range `1` to `365` | Behavior | Uploaded registry records older than this many days are eligible for manual remote cleanup. |
| `max_retries` | integer | `3` | `absint`, range `0` to `10` | Behavior | Failed upload attempts allowed before a queued item is removed. |
| `diagnostics_enabled` | boolean | `false` | boolean cast from checkbox presence | Behavior | Enables redacted diagnostics storage. |
| `diagnostics_min_level` | string | `warning` | `sanitize_key`, allowlist severity level | Behavior | Minimum diagnostics severity to store. |
| `diagnostics_retention` | integer | `100` | `absint`, range `25` to `500` | Behavior | Maximum diagnostics events retained locally. |

## Operational Options

These options are owned by the plugin and are removed on uninstall.

| Option Key | Type | Default | Writer | Description |
| --- | --- | --- | --- | --- |
| `alynt_drime_wpvivid_upload_queue` | array | `array()` | `Alynt_Drime_WPvivid_Uploader_Queue` | Pending backup upload items keyed by signature. |
| `alynt_drime_wpvivid_active_upload` | array | `array()` | `Alynt_Drime_WPvivid_Uploader_Queue` | Current multipart upload state used for resume and recovery. |
| `alynt_drime_wpvivid_uploaded_files` | array | `array()` | `Alynt_Drime_WPvivid_Uploader_Backup_Registry` | Uploaded backup records keyed by signature. Remote retention preserves these records and records `remote_status`, `remote_updated`, and optional `remote_status_context`. |
| `alynt_drime_wpvivid_failed_uploads` | array | `array()` | `Alynt_Drime_WPvivid_Uploader_Backup_Registry` | Failed upload records keyed by signature. |
| `alynt_drime_wpvivid_drime_locations` | array | `array()` | `Alynt_Drime_WPvivid_Uploader_Backup_Registry` | Cached Drime parent folder IDs for configured relative paths. |
| `alynt_drime_wpvivid_file_snapshots` | array | `array()` | `Alynt_Drime_WPvivid_Uploader_Scanner` | File size/modified-time snapshots used to verify stability across scans. |
| `alynt_drime_wpvivid_logs` | array | `array()` | `Alynt_Drime_WPvivid_Uploader_Logger` | Redacted diagnostics events when diagnostics are enabled. |
| `alynt_drime_wpvivid_upload_lock` | array | `array()` | `Alynt_Drime_WPvivid_Uploader_Uploader` | Short-lived upload worker lock to prevent concurrent queue processing. |

## External Options Read

The plugin reads these WPvivid options and does not write them:

| Option Key | Purpose |
| --- | --- |
| `wpvivid_common_setting` | Detects custom local backup folder configuration. |
| `wpvivid_local_setting` | Detects Free/Pro local backup path and Pro outside-folder mode. |
| `wpvivid_backup_list` | Reads WPvivid backup-set metadata for complete-set scanning. |

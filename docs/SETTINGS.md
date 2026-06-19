# Settings Schema

Option name: `alynt_drime_wpvivid_settings`

| Key | Type | Default | Notes |
| --- | --- | --- | --- |
| `api_token` | string | empty | Drime bearer token. Masked in UI. |
| `workspace_id` | integer | 0 | Drime personal/default workspace uses 0. |
| `parent_folder_id` | integer/null | null | Destination folder ID. |
| `relative_path` | string | empty | Optional Drime folder path. |
| `backup_path_override` | string | empty | Optional WPvivid local backup path. |
| `duplicate_mode` | string | skip | `skip` or `rename`. |
| `auto_scan_enabled` | boolean | false | Enables scheduled scanning. |
| `scan_interval` | string | fifteen_minutes | Internal WP-Cron schedule key. |
| `min_file_age_seconds` | integer | 900 | Minimum modified age before queueing. |
| `delete_local_after_upload` | boolean | false | Disabled by default. |
| `max_retries` | integer | 3 | Per-file retry cap. |
| `diagnostics_enabled` | boolean | false | Enables redacted diagnostics logging. |
| `diagnostics_min_level` | string | warning | Minimum event severity to store. |
| `diagnostics_retention` | integer | 100 | Bounded number of retained diagnostics events. |

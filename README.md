# Alynt Drime WPvivid Uploader

Companion WordPress plugin that scans completed local WPvivid backup archives and uploads them to Drime.

## Features

- Detects the local WPvivid backup folder, including verified Free/Pro path options.
- Scans only stable backup files so in-progress archives are not queued.
- Handles WPvivid-listed split archives such as `.part001.zip` and `.part002.zip` as complete sets.
- Queues uploads, tracks attempts, enforces retry limits, and prevents duplicate queue entries.
- Uploads small files through Drime direct upload and larger files through resumable multipart upload.
- Caches resolved Drime parent folder IDs so remote duplicate checks work after relative-path uploads.
- Supports duplicate handling by skipping existing remote files or asking Drime for an available filename.
- Provides manual admin actions for connection testing, scanning, upload, diagnostics export, diagnostics clearing, and active-upload recovery.
- Provides manual remote-retention preview and cleanup for plugin-owned Drime uploads, moving eligible remote files to Drime trash only.
- Stores bounded, redacted diagnostics when diagnostics are explicitly enabled.
- Keeps local backups after upload by default; deletion requires explicit opt-in.

## Requirements

- WordPress 6.0 or later.
- PHP 7.4 or later.
- WPvivid Backup Plugin with local backup files available on the same site.
- A Drime API token.

## Installation

1. Upload the plugin folder to `wp-content/plugins/alynt-drime-wpvivid-uploader`.
2. Activate **Alynt Drime WPvivid Uploader** from the WordPress Plugins screen.
3. Open **Tools > Drime WPvivid**.
4. Enter a Drime API token and destination settings.
5. Use **Test Drime Connection** before scanning or uploading.

For development and release validation, use the packaged zip and the documented LocalWP confirmation gate before touching `plugin-tester.local`.

## Updates

The plugin includes `GitHub Plugin URI: NichlasB/alynt-drime-wpvivid-uploader` for Alynt Plugin Updater compatibility. Updates are distributed from public GitHub releases using the attached WordPress-installable zip asset.

## Configuration

The settings screen controls:

- Drime API token, workspace ID, parent folder ID, and optional relative path.
- Optional WPvivid backup path override.
- Duplicate handling mode: skip existing files or rename new uploads.
- Automatic WP-Cron scanning.
- Minimum file age before queueing.
- Multipart chunk size for large Drime uploads.
- Optional local deletion after confirmed upload.
- Optional manual remote retention for old plugin-owned Drime uploads.
- Maximum retry count.
- Diagnostics enablement, minimum severity, and retention.

See [docs/SETTINGS.md](docs/SETTINGS.md) for the full option schema.

## Diagnostics

Diagnostics are disabled by default. When enabled, the plugin stores a bounded event log in WordPress options and exposes a health summary, recent events table, JSON export, and clear action to administrators.

Diagnostics redact bearer tokens, authorization headers, cookies, nonces, passwords, request bodies, presigned URLs, and HTTP URLs embedded in scalar values.

## Frequently Asked Questions

### Does this delete local WPvivid backups?

No. Local deletion is disabled by default and only runs after a confirmed upload when the administrator enables **Delete Local Files**.

### Does this permanently delete Drime files?

No. Remote retention is disabled by default, runs only from manual admin actions, and moves eligible plugin-owned Drime uploads to trash. It does not permanently delete remote files.

### Does this upload incomplete WPvivid files?

The scanner waits until files are old enough and their size is stable across scans. WPvivid-listed split sets are queued only when every listed part is present and stable.

### Does this expose developer hooks?

No public custom actions or filters are exposed in `0.1.1`. See [docs/HOOKS.md](docs/HOOKS.md).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

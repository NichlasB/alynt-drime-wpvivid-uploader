=== Alynt Drime WPvivid Uploader ===
Contributors: alynt
Tags: backup, wpvivid, drime
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Upload completed WPvivid local backup archives to Drime.

== Description ==

Alynt Drime WPvivid Uploader is a companion plugin that scans completed local WPvivid backup archives, queues stable backup files, and uploads them to Drime.

The plugin includes Drime destination settings, WPvivid path detection, direct and multipart upload support, duplicate handling, retry tracking, active-upload recovery, and optional redacted diagnostics for support. Local deletion after upload is disabled by default.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/alynt-drime-wpvivid-uploader/`.
2. Activate the plugin through the Plugins menu in WordPress.
3. Open Tools > Drime WPvivid.
4. Enter a Drime API token and destination settings.
5. Run Test Drime Connection before scanning or uploading.

== Frequently Asked Questions ==

= Does this delete local WPvivid backups? =

No. Local deletion is disabled by default and must be explicitly enabled in settings.

= Does this upload incomplete backups? =

The scanner waits until files are old enough and their size is stable across scans. WPvivid-listed split sets are queued only when every listed part is present and stable.

= Does this store diagnostics? =

Diagnostics are disabled by default. When enabled, diagnostics are redacted and stored in a bounded WordPress option.

= Does this expose custom developer hooks? =

No public custom actions or filters are exposed in version 0.1.0.

== Changelog ==

= 0.1.0 =
* Initial development release with Drime settings, WPvivid local scanner, queue/registry storage, direct and multipart uploads, duplicate handling, retry limits, diagnostics, uninstall cleanup, and build/test tooling.

== Upgrade Notice ==

= 0.1.0 =
Initial development release.

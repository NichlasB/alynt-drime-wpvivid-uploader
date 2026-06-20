# Changelog

All notable changes to this project will be documented in this file.

## [0.1.1] - 2026-06-20

### Added

- Added `GitHub Plugin URI` metadata so Alynt Plugin Updater can detect the public GitHub release source.

### Changed

- Refreshed the GitHub Actions release packaging workflow to package only runtime plugin files and avoid development-only Composer/npm artifacts.

## [0.1.0] - 2026-06-20

### Added

- Initial WordPress plugin scaffold for uploading completed WPvivid local backups to Drime.
- Admin settings for Drime API token, workspace, destination folder, relative path, backup path override, duplicate handling, automatic scanning, retry limits, local deletion, and diagnostics.
- WPvivid local backup path detection for verified Free and Pro options.
- Stable-file scanner with WPvivid backup-list metadata and split-part completeness checks.
- Option-backed queue, uploaded registry, failed registry, active upload state, scan snapshots, resolved Drime folder cache, diagnostics log, and upload worker lock.
- Direct small-file upload and resumable multipart upload support for Drime.
- Remote duplicate validation using cached parent folder IDs after relative-path uploads.
- Manual admin actions for connection testing, scanning, uploading, diagnostics export, diagnostics clearing, and active-upload recovery.
- Redacted diagnostics logging with bounded retention.
- Multisite-aware uninstall cleanup for plugin-owned options and cron hooks.
- Composer, npm, PHPCS/WPCS, PHPUnit, build script, POT generation script, and CI placeholders.

### Changed

- Split large runtime classes into focused traits for admin rendering, Drime upload APIs, uploader helpers, scanner metadata, and plugin admin actions.
- Moved text-domain loading to early `plugins_loaded`.
- Shared verified array-option storage through `Alynt_Drime_WPvivid_Uploader_Option_Storage`.

### Fixed

- Settings, queue, registry, active-state, and upload-state writes now verify persisted WordPress option state before reporting success.
- Admin scan and upload failures now surface explicit notices and diagnostics.
- Diagnostics export now has a JSON fallback when encoding fails.
- Multipart signed upload URLs are validated before backup bytes are sent.
- Invalid-token uploads stop at connection preflight before duplicate checks or byte upload.
- HTTP `429` and malformed multipart response paths have regression coverage.

### Security

- Sensitive diagnostics values are redacted, including bearer tokens, authorization headers, cookies, nonces, passwords, request bodies, presigned URLs, and HTTP URLs embedded in scalar values.

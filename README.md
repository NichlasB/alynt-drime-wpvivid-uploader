# Alynt Drime WPvivid Uploader

Companion WordPress plugin that scans completed local WPvivid backup archives and uploads them to Drime.

## MVP Scope

- Stores Drime connection settings in WordPress options.
- Detects local WPvivid backup archives by scanning a configured or detected backup directory.
- Queues stable backup files for upload.
- Uploads large files through Drime multipart upload, then registers them with Drime S3 entries.
- Tracks uploaded, queued, failed, and active upload state in WordPress options.
- Provides a WordPress-native admin page under Tools.

## Current Notes

This is a development-repo scaffold. Install on the LocalWP site `plugin-tester.local` only after the implementation has passed local static checks and the site-operation confirmation gate.

The Drime API token is stored in an option with autoload disabled and is masked in the UI. Do not commit tokens, logs with tokens, or presigned URLs.

## Diagnostics

Diagnostics are disabled by default. When enabled, the plugin stores a bounded, redacted event log in WordPress options. The diagnostics panel is available only to administrators who can manage options and includes a health summary, recent events, JSON export, and clear action.

The logger redacts tokens, authorization headers, cookies, nonces, passwords, request bodies, and presigned URLs before storing or exporting events.

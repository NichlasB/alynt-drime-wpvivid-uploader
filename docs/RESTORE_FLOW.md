# Restore Flow Runbook

This runbook explains how to restore backups that were uploaded to Drime by Alynt Drime WPvivid Uploader.

The plugin is an upload and monitoring tool. It does not restore files or databases, and it does not run destructive recovery actions. Restores should be performed with the backup producer, WP-CLI, GridPane/server tools, or another tested restore process.

## Recommended Restore Policy

- Prefer a staging or fresh recovery site before touching production.
- Download the backup package from Drime before starting the restore.
- Keep the current broken site intact until the backup package is verified.
- Record the backup source, backup timestamp, Drime path, restore target, and operator before changing files or databases.
- Do not enable local or remote cleanup until the restored site is verified.

## Pre-Restore Checklist

Before restoring, collect:

- Site URL and intended restore target.
- Whether the restore is same-site, staging-site, or fresh-site.
- Backup source: WPvivid, future server-side outbox package, or another producer.
- Backup package names and timestamps.
- Whether the backup includes database, files, uploads, plugins, themes, and WordPress core.
- Current WordPress admin access, SSH access, database access, and Drime access.
- A current safety snapshot of the target site when possible.

Confirm:

- The backup package downloads completely from Drime.
- Every split part is present when the backup uses split archives.
- The package belongs to the intended site and date.
- There is enough local disk space for download, extraction, and restore work.
- Cache, CDN, security, and maintenance-mode behavior is understood for the target site.

## Restore From A WPvivid Backup

Use this flow when the backup was produced by WPvivid and uploaded to Drime by this plugin.

1. In Drime, download every file that belongs to the WPvivid backup set.
2. Confirm split archives are complete. A split set should include every listed part, such as `.part001.zip`, `.part002.zip`, and so on.
3. Place the downloaded files where WPvivid expects imported/local backups for the restore target, or use WPvivid's import/upload workflow when available.
4. Open WPvivid on the restore target and confirm the backup set is detected.
5. Use WPvivid's own restore flow to restore the selected database, files, or full-site package.
6. Leave Alynt Drime WPvivid Uploader automatic scanning disabled during the restore unless there is a specific reason to keep scanning active.
7. After the restore, run the post-restore validation checklist below.

Notes:

- If the original backup was split, do not restore until every part is present.
- If the restore target is a fresh site, install WPvivid first and confirm the PHP limits are sufficient for the package size.
- This plugin's uploaded registry is useful as evidence that a file reached Drime, but WPvivid remains the restore authority for WPvivid packages.

## Restore From A Server-Generated Backup Package

Use this flow for future backup packages created by a server-side automation tool rather than WPvivid.

Status: future-facing. The current stable plugin release uploads WPvivid backups; broader server-side backup automation is a separate backlog item.

1. Download the server-generated package from Drime.
2. Read the package manifest or naming convention to confirm what it contains.
3. Confirm whether the package is files-only, database-only, or full-site.
4. Restore to staging first unless the incident requires direct production recovery.
5. Restore database and files using the server-side backup tool's documented process.
6. If no restore tool exists, prepare a separate, explicit restore plan before touching production.
7. After restore, run the post-restore validation checklist below.

Minimum package metadata to require in a future server-side flow:

- Site identifier.
- Site URL or domain.
- Backup started and completed timestamps.
- Package type: database, files, uploads, full-site, or custom.
- WordPress path and database name at backup time.
- Producer name and version.
- Split-part count when applicable.
- Checksum or equivalent integrity signal when available.

## Same-Site Recovery

Use same-site recovery only when staging is unavailable or the production site must be repaired immediately.

1. Put the site into maintenance mode if users could hit inconsistent state during the restore.
2. Take a current emergency snapshot if the host or tool allows it.
3. Disable backup scans and upload workers during destructive restore work.
4. Download and verify the backup package from Drime.
5. Restore the database and files with the correct restore tool.
6. Flush object cache, page cache, CDN cache, and rewrite rules as appropriate.
7. Run the post-restore validation checklist.
8. Re-enable backup automation only after the restored site is confirmed healthy.

## Fresh Or Staging-Site Recovery

This is the preferred recovery rehearsal and the safest first attempt for uncertain backups.

1. Create a fresh WordPress install or staging clone.
2. Match PHP version, WordPress version, and major server settings as closely as practical.
3. Install the restore tool required by the backup producer.
4. Download the backup package from Drime to the restore target.
5. Restore the package.
6. Update domain/search-replace settings only when the restore target differs from production.
7. Validate the restored site.
8. Promote to production only after the staging result is accepted.

## GridPane-Oriented Notes

For GridPane VPS/Nginx sites:

- Prefer staging or a dummy test site for restore rehearsals.
- Use SSH and WP-CLI as the site user when running WordPress commands.
- Treat `/var/www/<site>/htdocs` as the WordPress web root only after verifying the actual site path.
- Check available disk space before downloading or extracting large backups.
- Be aware of GridPane cache, Nginx, SSL, and Secure WP Debug behavior during incident recovery.
- Do not rely on this plugin to inspect server cron files; the plugin reports runtime scan evidence only.

## Post-Restore Validation Checklist

Confirm:

- Frontend returns the expected status and content.
- WordPress admin login works.
- Permalinks work and rewrite rules are flushed if needed.
- Media library files load.
- Uploads directory is writable.
- Database connection and table prefix are correct.
- Forms, checkout, memberships, logins, or other critical workflows work.
- Scheduled tasks run as expected.
- Cache and CDN state are cleared or warmed as needed.
- Security, SMTP, and license-dependent plugins are in the expected state.
- Backup automation is re-enabled only after the restored site is healthy.
- Alynt Drime WPvivid Uploader settings still point to the intended Drime workspace and folder.
- A new small backup/upload test is run after the restored site is stable.

## What Not To Do

- Do not restore directly to production before verifying that all backup parts are present.
- Do not delete local backups or remote Drime backups during an active incident.
- Do not assume a backup uploaded successfully means the backup producer can restore it.
- Do not mix database and file packages from different timestamps unless the restore plan explicitly accounts for it.
- Do not expose Drime tokens, presigned URLs, database credentials, or absolute server paths in restore notes sent by email or chat.

## Future Plugin Ideas

The current plugin should remain non-destructive. Future restore-adjacent improvements could include:

- A read-only restore checklist inside the admin page.
- A downloaded-file inventory helper.
- A backup-set manifest viewer.
- A warning when a split set is incomplete.
- A link from uploaded registry records to operator restore notes.

Any actual restore execution should be planned as a separate feature with explicit approval gates, staging-first testing, rollback strategy, and destructive-action safeguards.

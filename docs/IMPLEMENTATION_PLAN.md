# Alynt Drime WPvivid Uploader Implementation Plan

Updated: 2026-06-27

## Current State

- Development repo exists at `C:\Development\WordPress\Plugins\alynt-drime-wpvivid-uploader`.
- Plugin scaffold is in place with settings, scanner, queue, registry, Drime client, uploader, cron, admin page, uninstall cleanup, README, changelog, and settings docs.
- Diagnostics and observability workflow is complete.
- Build tooling workflow is complete. Composer/npm dependencies are installed, PHPCS/WPCS is configured, and npm build/lint/test scripts pass.
- Pre-release Code Cleanup workflow is complete. Two unused runtime methods were removed: `Alynt_Drime_WPvivid_Uploader_Drime_Client::create_folder()` and `Alynt_Drime_WPvivid_Uploader_Plugin::cron()`.
- Pre-release File Structure Review workflow is complete. Oversized runtime classes were split into focused traits for admin rendering, Drime direct/multipart APIs, uploader multipart/active/retry helpers, scanner metadata, and plugin admin actions; current runtime method inventory has no methods over 50 lines.
- Pre-release Error Handling Review workflow is complete. Settings saves now verify persisted state, scan failures surface as explicit admin notices, diagnostics export has a JSON fallback, direct uploads use bounded cURL timeouts, and admin-post buttons show loading labels.
- Pre-release WP Best Practices Review workflow is complete. The plugin loader now checks minimum WordPress/PHP requirements before loading runtime files that use modern PHP syntax.
- Pre-release Database Review workflow is complete. The plugin has no custom tables or raw SQL; option-backed queue and registry writes now verify persisted state and return explicit failures when WordPress option storage does not update.
- Pre-release Performance Review workflow is complete. Scan queuing now batches uploaded-registry reads and queue writes, and cron clearing skips unscheduled hooks to avoid unnecessary idle-request work.
- Pre-release Edge Cases Review workflow is complete. Upload workers now use a short option-backed lock, clear-active reports abort/state failures honestly, and the delete-local-after-upload setting now performs post-upload local cleanup.
- Pre-release Uninstall Review workflow is complete. Uninstall cleanup now includes the upload worker lock option and removes plugin-owned per-site options and cron hooks across multisite installs.
- Pre-release I18N Review workflow is complete. Text-domain loading now runs early on `plugins_loaded`, all visible diagnostics severity labels and the relative-path placeholder are translatable, and a POT template was generated with WP-CLI.
- Pre-release Accessibility Review workflow is complete. Admin notices now expose live roles, diagnostic data tables have screen-reader captions and scoped headers, and the recent-events empty-state heading follows the page hierarchy.
- Pre-release Code Quality Review workflow is complete. Queue and registry array-option persistence now use a shared storage trait, runtime method inventory has no methods over 50 lines, and the final lint/test/build gate passes.
- Pre-release Documentation Review workflow is complete. Runtime PHPDoc now includes `@since 0.1.0` coverage for class/trait/public-method declarations, README/readme/changelog/settings docs are current, and `docs/HOOKS.md` documents that version `0.1.0` exposes no public custom hooks.
- Pre-release Security Audit workflow is complete. No critical, high, medium, or low blocking issues were found; admin actions are capability/nonce-gated, outputs are escaped, diagnostics are redacted, direct database SQL is absent, dangerous-function scans found only the intentional Drime cURL upload path, and Composer/npm audits are clean.
- Release validation/package refresh is complete for the current source. Final lint, tests, build, npm audit, Composer audit, PHP syntax sweep, distribution zip audit, LocalWP packaged install, and admin render probe passed.
- Restore flow documentation is now captured in `docs/RESTORE_FLOW.md`; the plugin remains upload-only and does not execute destructive restore actions.
- Drime API schema documentation pass is complete against current public docs, and live-token checks have verified connection, duplicate-validation response shape, available-name response key, direct small-file upload, and the parent-ID duplicate-validation workaround for relative-path uploads.
- WPvivid source verification is complete against installed Free/Pro source and sanitized runtime options; a real single-file database backup fixture has been tested, a WPvivid-list-backed split-part fixture has validated `.part001.zip` / `.part002.zip` scanner gating, and a full WPvivid backup-engine-generated 5-part split backup has scanned, queued, and uploaded successfully to Drime from `plugin-tester.local`.
- Feature security review is complete for the current changed code; multipart signed URLs are validated before upload and diagnostics redacts sensitive substrings in scalar values.
- LocalWP integration is active on `plugin-tester.local`: the plugin is installed/active, the admin page loads, settings save, diagnostics export works, a real WPvivid database backup queues after stable-file scans, that backup uploaded successfully to Drime, duplicate-skip behavior works with the cached Drime parent folder ID, fresh plus interrupted-resume multipart uploads succeeded against Drime, remote multipart abort succeeds through the clear-active path, invalid-token retry handling is verified after auth-preflight hardening, malformed multipart plus HTTP `429` response paths have regression coverage, and packaged release zips have installed/activated successfully through WordPress's upgrader API.
- PHP syntax checks pass across plugin PHP files excluding vendor and node_modules.
- The plugin is installed on `plugin-tester.local` through the release/update flow and is currently verified at `0.6.2`.
- The initial scaffold baseline exists at Git commit `7f4b5df`.
- Configurable multipart chunk-size support is implemented, committed, pushed to `origin/master`, and has passed post-feature review workflows plus LocalWP/Drime E2E testing.
- Remote Drime retention is implemented in the current working tree as a conservative manual-only feature: registry-owned uploads only, 60-day default, Drime trash only, and no permanent remote deletion path.
- Remote Drime Retention post-feature review sequence is complete. Feature Light Review, Feature Bloat and Structure Review, Feature UI/UX Implementation Review, and Feature Security Review found no blocking issues. LocalWP dry-run runtime verification and one approved live Drime trash verification are complete.
- v0.5.1 incident hardening is implemented in the current working tree after the first live DrMorses.TV failed upload: Drime control requests now allow a longer timeout, failed upload records preserve safe requeue context, administrators can retry readable failed files from the status UI, and local deletion waits for every WPvivid-listed split part before cleaning up the local set.
- v0.6.2 is accepted/stable. The release is published on GitHub, the canonical workflow-generated zip asset is attached, LocalWP updater validation passed, and Alynt Plugin Updater/WordPress detected and installed the update from `0.6.1` to `0.6.2` through the WordPress Plugins screen while preserving settings.

### WPvivid Split Backup E2E Rehearsal

Status: complete on `plugin-tester.local` as of 2026-06-27.

Validated:

- WPvivid generated a real full-site manual backup with split packaging enabled temporarily.
- Completed backup ID: `wpvivid-83ecc6db96748`.
- WPvivid registered a successful `backup_all` set containing five listed files:
  - `plugin-tester.local_wpvivid-83ecc6db96748_2026-06-26-21-59_backup_all.part001.zip`
  - `plugin-tester.local_wpvivid-83ecc6db96748_2026-06-26-21-59_backup_all.part002.zip`
  - `plugin-tester.local_wpvivid-83ecc6db96748_2026-06-26-21-59_backup_all.part003.zip`
  - `plugin-tester.local_wpvivid-83ecc6db96748_2026-06-26-21-59_backup_all.part004.zip`
  - `plugin-tester.local_wpvivid-83ecc6db96748_2026-06-26-21-59_backup_all.part005.zip`
- The plugin scanner held the new files on the first stability pass, then queued the five stable split parts on the next scan.
- The plugin uploaded all five split parts through the normal WordPress admin `Upload Next Queued Backup` flow.
- Final plugin state after the rehearsal: queue count `0`, failed count `0`, active upload state empty, and all five split parts present in the uploaded registry.
- Temporary test changes were restored after validation: WPvivid split size returned to `200`, current plugin settings returned to the accepted baseline, and the temporary LocalWP admin password hash was restored.

Follow-up:

- Legacy pre-rename Drime settings migration is intentionally out of scope. Existing sites that used the old WPvivid-specific plugin line should be treated as fresh installs of Alynt Drime Backups Uploader: configure Drime credentials, workspace, destination folder, relative path, scanning, and automation settings manually, then run connection and upload checks.
- No admin notice is planned for legacy option detection because manual reconfiguration is the accepted upgrade path and avoids carrying over stale or ambiguous values such as `workspace_id = 0`.
- The Drime workspace picker returned team workspaces `4442` (`Kitty`) and `4886` (`Alynt`) for the saved token. The successful rehearsal used workspace `4886` and temporarily cleared `relative_path` because Drime child-folder listing for the intended relative path returned a transient `504` from `/folders?workspaceId=4886`. Source hardening is now implemented: selected-base relative-path uploads fall back from searched child-folder lookup to the full child-folder list for transient Drime server errors, then fall back to Drime's broader user folder tree by parent ID before creating a folder. LocalWP runtime verification on `plugin-tester.local` confirmed the uploader reused the existing `/plugin-tester.local` folder and cached parent ID `762160507` after the relative-path cache was cleared.

### Feature Slice: v0.5.1 Multipart Failure Recovery

Status: implemented, released, and stable in the `0.5.1` and later release line. Kept here as incident-response evidence.

#### Trigger

- Live DrMorses.TV `v0.5.0` encountered a terminal failed upload for a large WPvivid split archive part.
- Diagnostics showed repeated slow Drime multipart signing/preflight responses and exposed a recovery hazard: local deletion had already removed the successfully uploaded `part001`, so scanner completeness checks could not naturally requeue the remaining split set.

#### Implemented

- Increased Drime JSON/control-request timeout from 45 seconds to 180 seconds.
- Stored safe failed-upload context in the failed registry: basename, path for local requeue, attempts, size, and sanitized WPvivid set metadata.
- Added an admin Failed Uploads table with per-file `Retry Upload` actions protected by `manage_options` and admin nonces.
- Requeued readable failed files at the front of the upload queue with attempts reset to zero, then cleared the failed record so any later failure is recorded fresh.
- Preserved WPvivid set metadata in uploaded registry records so local cleanup can reason about complete split sets.
- Changed `delete_local_after_upload` behavior for WPvivid-listed multi-file sets: individual parts are kept until all listed parts are uploaded, then the local set is deleted together.

#### Validation

- Added PHPUnit coverage for:
  - extended Drime API timeout;
  - failed registry requeue context;
  - queue prepend behavior;
  - split-set local deletion waiting for incomplete sets;
  - split-set cleanup after the final uploaded part.
- Full PHPUnit and PHPCS are required before release approval.

### Planned Feature Slice: Drime Multipart Chunk Size Validation Up To 256 MB

Status: implemented in source and validated on `plugin-tester.local`. PHP syntax, PHPUnit, PHPCS, npm test, npm build, npm lint, and diff whitespace checks passed. LocalWP/Drime E2E passed at 64 MB, 128 MB, and 192 MB; 256 MB was blocked safely by the new PHP memory guard under the current `256M` memory limit.

#### Trigger

- Current plugin release `0.5.1` intentionally supports Drime multipart chunk sizes from 5 MB through 64 MB.
- Live Morses Health Center testing showed the admin settings page correctly rejects `128 MB` with the current validation cap.
- The user wants to determine whether larger Drime multipart chunks, especially `128 MB` and `256 MB`, are stable enough to support in a future plugin release.
- This should be validated on `plugin-tester.local` before any release or live-site rollout.

#### Goal

- Safely test whether Drime multipart uploads work with larger chunk sizes up to 256 MB.
- If live LocalWP/Drime validation passes, raise the plugin's maximum supported multipart chunk size from 64 MB to 256 MB.
- Keep `64 MB` as a conservative known-good setting unless validation proves a larger recommendation is appropriate.

#### Recommended Flow

1. Create a fresh restore point with the wp-plugin-toolkit restore-point workflow before editing the source repo.
2. Modify the source repo first; do not hand-edit the LocalWP installed copy as the source of truth.
3. Update settings constants, validation copy, tests, and documentation to allow a 5-256 MB range.
4. Build/package or otherwise sync the source plugin into `plugin-tester.local`.
5. Use the saved Drime settings on `plugin-tester.local` only after confirming the target and that temporary Drime uploads are approved.
6. Generate disposable local `.zip` fixtures rather than asking the user for a large file.
7. Test staged chunk sizes:
   - 64 MB baseline.
   - 128 MB.
   - 192 MB, optional if useful for a midpoint.
   - 256 MB.
8. For each chunk size, validate:
   - Settings page accepts and persists the value.
   - Multipart upload starts and creates Drime multipart state.
   - Drime signs part URLs successfully.
   - Each part uploads successfully.
   - Multipart completion creates the final Drime file entry.
   - Diagnostics do not expose bearer tokens or presigned URLs.
   - Active upload state remains resumable and clearable.
9. Clean up temporary local and remote test artifacts only after explicit approval when remote deletion/trash is involved.

#### Test Fixture Guidance

- Prefer generated temporary `.zip` files around 600-900 MB so larger chunk sizes produce multiple multipart parts without wasting excessive time.
- For an 850 MB fixture:
  - 64 MB should produce roughly 14 Drime parts.
  - 128 MB should produce roughly 7 Drime parts.
  - 256 MB should produce roughly 4 Drime parts.
- Use fixture names that clearly mark them as disposable chunk-size validation artifacts.

#### Implementation Notes

- Current supported range is documented in `CHANGELOG.md` and `docs/DRIME_API_RESEARCH.md` as 5-64 MB based on prior live probing.
- Any raise above 64 MB needs fresh live LocalWP/Drime evidence before release.
- The plugin should continue clamping invalid saved values safely.
- If an active multipart upload exists and the chunk size changes, the current active-state invalidation/abort behavior must remain intact.
- Recommendation text should remain conservative even if 256 MB passes; likely recommended value is 64 MB or 128 MB unless repeated larger-file testing proves 256 MB is broadly stable.
- The uploader reads each multipart part into memory before sending it to the signed upload URL. A runtime memory guard now stops oversized chunk settings before creating a remote multipart session when the PHP memory limit cannot safely hold one part plus runtime overhead.

#### Tests And Verification

- Update PHPUnit settings-sanitization coverage for:
  - minimum below 5 MB clamps or rejects as currently expected;
  - accepted values at 64 MB, 128 MB, 192 MB, and 256 MB;
  - values above 256 MB clamp or reject as designed.
- Update admin UI tests/snapshots or runtime probes to confirm the number input advertises the new max.
- Run existing multipart uploader tests to confirm active-state chunk-size matching still works.
- Run `php -l`, PHPUnit, PHPCS, npm test/build/lint as applicable.
- Run `plugin-tester local-only` E2E with Novamira MCP available when possible.
- Do not treat Drime API state, LocalWP runtime state, or saved credentials as current until reverified in the implementation chat.

#### LocalWP/Drime E2E Findings

- `plugin-tester.local` runtime after source sync:
  - WordPress `7.0`.
  - PHP `8.2.27`.
  - PHP memory limit `256M`.
  - Alynt Drime WPvivid Uploader active at `0.5.1`.
  - Novamira MCP available.
- Settings persistence accepted and restored all target values: 64 MB, 128 MB, 192 MB, and 256 MB.
- The settings screen number input rendered `min="5"` and `max="256"`.
- Runtime upload validation used generated disposable local fixtures in the WPvivid backup directory, then removed the local fixture files after each probe.
- A 64 MB chunk setting uploaded a 96 MB fixture successfully to Drime through multipart upload.
- A 128 MB chunk setting initially exposed a destination-resolution edge case where a stale saved folder hash led to a duplicate folder-create attempt. Source was updated to prefer the cached concrete Drime destination folder ID, and the 128 MB retry uploaded a 160 MB fixture successfully.
- A 192 MB chunk setting uploaded a 224 MB fixture successfully.
- A 256 MB chunk setting with a 288 MB fixture failed gracefully with `alynt_drime_chunk_exceeds_memory` before creating a remote file entry or leaving active upload state.
- Queue, failed-upload state, active-upload state, and saved chunk setting were restored/clean after generated validation probes.
- Diagnostics scan after the probes found no bearer tokens, presigned URL markers, or HTTP URLs in stored plugin diagnostics.
- Remote Drime validation artifacts remain uploaded and were not trashed or deleted because remote cleanup requires separate explicit approval.

#### Feature-Stage Workflows After Implementation

Run the applicable wp-plugin-toolkit workflows in this order:

1. `C:\Users\Captain\Documents\AI Workflows\Toolkits\wp-plugin-toolkit\d4-prompts\ds2-feature\FEATURE_LIGHT_REVIEW_PROMPT.md`
2. `C:\Users\Captain\Documents\AI Workflows\Toolkits\wp-plugin-toolkit\d4-prompts\ds2-feature\FEATURE_BLOAT_AND_STRUCTURE_REVIEW_PROMPT.md`
3. `C:\Users\Captain\Documents\AI Workflows\Toolkits\wp-plugin-toolkit\d4-prompts\ds2-feature\FEATURE_UI_UX_IMPLEMENTATION_PROMPT.md`
4. `C:\Users\Captain\Documents\AI Workflows\Toolkits\wp-plugin-toolkit\d4-prompts\ds2-feature\FEATURE_SECURITY_REVIEW_PROMPT.md`
5. `C:\Users\Captain\Documents\AI Workflows\Toolkits\wp-plugin-toolkit\d4-prompts\ds6-maintenance\DOCUMENTATION_SYNC_AUDIT_PROMPT.md`

#### Release Workflow

- After implementation, tests, LocalWP/Drime E2E, and documentation sync pass, run `C:\Users\Captain\Documents\AI Workflows\Toolkits\wp-plugin-toolkit\d4-prompts\ds5-git\GIT_OPERATIONS_PROMPT.md` Option C.
- Stop at the mandatory release approval checkpoint before commit/tag/push/GitHub release publishing.

## Target Test Site

Use the LocalWP profile:

- Site key: `plugin-tester`
- Mode: `local-only`
- LocalWP site name: `Plugin Tester`
- LocalWP domain: `plugin-tester.local`
- WordPress path: `C:\Users\Captain\Local Sites\plugin-tester\app\public`

Before installing or testing on this LocalWP site, follow the Site Operations confirmation gate. Novamira MCP is available and reports both `WPvivid Backup Plugin` and `WPvivid Plugins Pro` active.

## Active Roadmap

This section is the current source for what remains after `v0.6.2` was accepted as stable. The older detailed implementation notes below are kept as a completion ledger, not as the active to-do list.

### 1. Current Maintenance Posture

Status: no active implementation slice remains after the `0.6.2` release was accepted as stable.

- Workspace picker closeout is complete and recorded in the completion ledger under `Feature Slice: Drime Workspace Picker`.
- Folder browser runtime AJAX verification is complete and recorded in the completion ledger under `Feature Slice: Drime Folder Browser And Destination Validator`.
- Continuing E2E and failure hardening is complete for the current stable line; the remaining risky or external-service-dependent items are captured below as accepted hardening decisions.

### 2. Current E2E Evidence

- Duplicate-validation evidence is current for Drime uploads that use cached concrete parent folder IDs. On `plugin-tester.local`, the saved base folder was `759829073`, the saved relative path was `/plugin-tester.local`, and the registry cache resolved the final concrete parent folder to `762160507`. A non-uploading Drime `/uploads/validate` probe against existing uploaded file `alynt-chunk-validation-64mb-20260622-190700.zip` returned one duplicate under parent `762160507`; a generated unique probe name under the same parent returned zero duplicates. The local plugin option fingerprint was unchanged before and after the probe.

### 3. Accepted Hardening Decisions

- Relative-path-only duplicate-validation variants remain documented as unreliable. Current Drime evidence shows duplicate detection is reliable when the resolved concrete destination folder is sent as top-level `parentId`, so the plugin should continue using cached concrete parent folder IDs after relative-path uploads. Do not spend more implementation energy on relative-path-only duplicate detection unless Drime API behavior changes or new evidence appears.
- Live Drime rate-limit induction remains deferred as an accepted safety decision. HTTP `429` handling already has unit/regression coverage and upload preflight failures stop before duplicate validation or byte upload. Do not intentionally trigger Drime rate limits during routine validation; revisit only for a specific incident or explicit approval where controlled probing is necessary.
- Broader arbitrary-folder remote cleanup remains out of scope for the current retention feature. Remote retention should stay limited to plugin-owned uploaded-registry entries with stored Drime file-entry IDs; any future folder-wide cleanup should be planned as a separate feature with its own preview, dry-run, ownership safeguards, and explicit approval gates.

### 4. Plan Hygiene

Needed:

- Keep this active roadmap short and decision-oriented. Completed for the 2026-06-27 hygiene pass.
- Move completed slice detail into the completion ledger below instead of leaving it as active work. Completed for the 2026-06-27 hygiene pass.
- Keep `CHANGELOG.md`, `readme.txt`, `README.md`, `docs/SETTINGS.md`, `docs/DRIME_API_RESEARCH.md`, and the toolkit pre-release checklist aligned whenever a new release changes behavior.

## Future Feature Backlog

These are uncommitted candidate slices. They are intentionally not part of the active roadmap until one is selected, planned, and approved as the next implementation target.

### Restore Flow Documentation

Status: documented in `docs/RESTORE_FLOW.md`. Future restore-assistant behavior remains uncommitted and would require a separate approved feature plan.

Purpose:

- Create an operator runbook for restoring backups that have been uploaded to Drime.
- Cover same-site recovery, fresh/staging-site recovery, database-only restores, files-only restores, and full-site restores.
- Keep the plugin out of destructive restore execution unless a later feature explicitly plans a safe restore assistant.

Notes:

- Treat restore docs as the recommended next planning slice because backup confidence depends on knowing how to recover.
- Include WPvivid-generated backups, server-generated backup packages, and GridPane-specific operator notes where verified.
- Include validation steps after restore: login, permalinks, media/uploads, cron, forms, cache, security plugin state, and backup automation state.

### Server-Side Backup Automation Support

Purpose:

- Support a server-generated backup workflow where cron or another server runner creates backup packages outside WPvivid, places them in a known outbox, and the plugin scans/uploads completed packages to Drime.
- Preserve the Model B boundary: server tooling creates the backup package; the WordPress plugin detects, queues, uploads, reports status, and helps with setup/diagnostics.

Notes:

- Keep plugin-side setup assistant read-only or narrowly scoped at first: show expected outbox path, folder readability, site UUID, scan state, runner status, and suggested commands.
- Require stable-file detection, minimum file age, and incomplete-package safeguards before queueing server-produced packages.
- Plan GridPane VPS/Nginx assumptions from verified server investigation rather than generic host assumptions.

### Central Dashboard Readiness

Purpose:

- Prepare this plugin to report status to a future separate control-center plugin without building that dashboard inside this plugin.
- Track whether the current site has queued, uploaded, failed, active, and stale backup/upload states in a shape that another plugin can consume.

Notes:

- Candidate surfaces could include a read-only status endpoint, signed site identity, explicit opt-in, redacted health payloads, and last-event timestamps.
- Do not send Drime tokens, presigned URLs, absolute server paths, raw request bodies, file contents, or stack traces.
- Treat the central dashboard itself as a separate project/plugin.

### Backup Producer Architecture

Purpose:

- Keep the upload pipeline extensible so future backup producers can be added without rewriting queue, registry, Drime upload, diagnostics, and admin status behavior.
- Treat WPvivid as the first producer rather than the only long-term producer.

Notes:

- Do not spend implementation energy on specific third-party backup plugin adapters until a producer is selected.
- A future slice should define a small producer contract for package discovery, completeness checks, backup-set metadata, display labels, and restore notes.
- Server-side outbox support can become the first non-WPvivid producer if selected before any third-party plugin adapter.

## Completed Implementation Ledger

The following sections preserve the original implementation roadmap and evidence. They are not the current active roadmap unless a subsection explicitly says it is still pending.

### 1. Source Control Baseline

- Review the untracked scaffold.
- Make an initial Git commit before larger feature changes.
- Keep the pre-observability restore point for emergency rollback:
  `C:\Users\Captain\Documents\AI Workflows\Toolkits\toolkit-snapshots\alynt-drime-wpvivid-uploader\alynt-drime-wpvivid-uploader-20260619-215013.zip`

### 2. Build And Lint Tooling

Run the toolkit workflow:

```text
@ADD_BUILD_TOOLING_PROMPT.md run
```

Goals:

- Install Composer and npm dependencies.
- Confirm PHPCS/WPCS configuration works on Windows.
- Add or refine test scripts.
- Run lint and fix safe violations.
- Record completion in `PRE_RELEASE_CHECKLIST.md` only after the workflow completes successfully.

### 3. WPvivid Source Verification

Inspect the installed WPvivid Free and Pro source on `plugin-tester.local`.

Status: complete for source/runtime option verification. See `docs/WPVIVID_RESEARCH.md`.

Verify:

- default and custom local backup path option names
- backup file naming patterns
- split archive naming patterns
- temporary/incomplete file markers
- backup history/task option structures
- any stable action/filter hooks that signal backup completion

Expected output:

- `class-wpvivid-detector.php` reads the verified WPvivid local path options, including Pro `outside_folder` mode.
- Scanner candidates include WPvivid backup-set metadata when `wpvivid_backup_list` is available.
- Verified assumptions are documented in `docs/WPVIVID_RESEARCH.md`.

### 4. Drime API Schema Verification

Confirm exact request/response schemas against current Drime docs and, when a token is available, safe live test calls.

Status: documentation pass complete against current public Drime docs. Live-token checks have verified connection, duplicate-validation response shape, available-name response key, relative-path direct upload, direct upload response shape, and cached parent-ID duplicate validation. See `docs/DRIME_API_RESEARCH.md`.

Verify:

- token test endpoint
- folder creation behavior
- duplicate validation response shape (verified; relative-path duplicate detection remains unreliable)
- available-name response shape (verified live key is `available`, not documented `name`)
- small upload endpoint behavior (verified with a real WPvivid database backup)
- multipart create response keys
- signed part URL response shape
- ETag formatting requirements
- complete multipart response shape
- abort multipart response shape
- `/s3/entries` registration body and response shape
- whether `relativePath`, `parentId`, and `workspaceId=0` behave as expected (partially verified; remote duplicate detection requires top-level `parentId`)

Expected output:

- update `class-drime-client.php` field names and parsers
- add defensive handling for malformed responses
- document verified schemas in `docs/DRIME_API_RESEARCH.md`
- store resolved Drime parent folder IDs after relative-path uploads so future duplicate validation can send top-level `parentId`

### 5. Scanner And Queue Hardening

Improve MVP scanner behavior after WPvivid verification.

Status: mostly complete. Scanner candidates now include WPvivid backup-set metadata, WPvivid-listed sets are queued only after every listed file is present and stable, valid `.partNNN.zip` split archives are preserved while raw temporary `.part` files are ignored, orphaned split archive parts are skipped, duplicate queue handling checks path and WPvivid backup-id/name identity, retry cap enforcement is implemented, and stale active-upload state is cleared after six hours.

Needed:

- group split backups into complete backup sets (done for WPvivid backup-list-backed sets; runtime WPvivid-list-backed split fixture validated; full generated split backup still untested)
- prevent queueing partial/incomplete backup sets (done for WPvivid backup-list-backed sets and orphaned split parts)
- store set-level metadata where needed (done for scanner candidates)
- improve duplicate queue handling (done for local path and WPvivid backup-id/name identity)
- add retry cap enforcement (done)
- add stale active-upload recovery behavior (done)

### 6. Upload Flow Hardening

Improve uploader reliability before real backup testing.

Status: mostly complete. Direct small-file upload is verified live with a real WPvivid database backup. Fresh multipart upload and interrupted multipart resume are verified live with temporary `.zip` fixtures over the multipart threshold. Multipart active state is persisted without presigned URLs, same-item active state can resume, uploaded parts are fetched with `get-uploaded-parts`, completed part numbers are skipped, signed upload URLs are validated as safe HTTPS URLs before sending backup bytes, malformed signed URL payloads fail before bytes are uploaded, every upload is gated by a JSON token preflight, invalid-token and HTTP `429` preflight handling stop before byte upload, the admin can clear local active upload state and abort the remote multipart upload when possible, cached relative-path parent IDs are used for reliable remote duplicate validation, and tests cover fresh/resumed/abort/auth-preflight/malformed-response/rate-limit multipart control flow.

Needed:

- verify small-file upload path or replace it with the documented Drime upload flow if needed (done with live direct upload)
- support multipart resume using saved state and `get-uploaded-parts` (implemented and live-validated with an interrupted upload)
- persist multipart state after each uploaded part (done)
- add abort/clear stale upload state (done; manual clear abort live-validated, stale clear covered by PHPUnit)
- ensure local deletion remains disabled by default and requires explicit admin opt-in
- avoid logging file contents, tokens, presigned URLs, or raw request bodies (diagnostics key-based and scalar substring redaction implemented)
- use top-level `parentId` for Drime duplicate validation after a relative-path upload has resolved the concrete parent folder (done)
- block uploads when the JSON token preflight fails, even if an upload endpoint would otherwise accept the request (done)
- reject malformed multipart create/sign responses before upload completion or Drime entry registration (done with regression coverage)
- handle HTTP `429` rate-limit responses without uploading backup bytes (done with regression coverage; live rate-limit induction intentionally not forced)

### Planned Feature Slice: Remote Drime Retention

Status: implemented in current working tree. Post-feature review sequence, LocalWP dry-run runtime verification, and one approved live Drime trash verification are complete.

Goal:

- Add optional controls for deleting old Drime files uploaded by this plugin.
- Keep the first implementation conservative: move files to Drime trash only, not permanent deletion.
- Limit cleanup candidates to plugin-owned uploaded-registry entries unless a later feature explicitly adds Drime-folder enumeration.
- Keep remote retention separate from the existing local post-upload delete setting.

Recommended first slice:

- Add settings for remote retention:
  - `remote_retention_enabled`, default disabled. Done.
  - `remote_retention_days`, default `60`, clamped to `1` through `365`. Done.
  - No permanent-delete setting in the first version. Hard-code `deleteForever=false`. Done.
- Add a Drime client method for `POST /file-entries/delete` after re-verifying the current request and response schema against live API behavior. Done.
- Add a focused retention service/class that selects eligible uploaded-registry records, calls the Drime delete endpoint, updates registry state, and writes diagnostics events. Done.
- Use local registry `uploaded_at` age as the authoritative retention clock for the first version. Done.
- Skip records without a verified Drime `fileEntry.id`, records newer than the configured age, and records already marked as trashed/deleted or `trash_failed`. Done.
- Preserve uploaded-registry records after remote trashing and mark them with a remote status such as `uploaded`, `trashed`, or `trash_failed` so local files are not silently re-uploaded. Done.
- Prefer a manual admin action with dry-run/preview output for the first implementation. Add scheduled automatic cleanup only after the behavior is proven locally and against Drime. Done; no scheduled cleanup was added.
- Add admin UI in the settings/admin page for enablement, retention days, dry-run status, and a manual cleanup action. Treat cleanup as destructive: capability gate, nonce gate, clear copy, and explicit action feedback. Done.
- Add diagnostics events such as `retention_started`, `retention_candidate_found`, `retention_file_trashed`, `retention_failed`, and `retention_finished`. Done.

Tests and verification:

- Unit-test settings sanitization for disabled state, day bounds, and default behavior. Done.
- Unit-test candidate selection: disabled retention, missing Drime ID, new files, old files, already-trashed files, and failed prior attempts. Done.
- Unit-test Drime delete request shape and response handling with `deleteForever=false`. Done.
- Unit-test success and failure registry updates, including preserving records after trashing. Done.
- Verify no code path performs permanent remote deletion in the first version. Done by static implementation, unit test coverage, and approved live runtime trash verification with `deleteForever=false`.
- Run the post-feature workflows after implementation: Feature Light Review, Feature Bloat and Structure Review, Feature UI/UX Implementation, and Feature Security Review. Done.
- Use the Site Operations confirmation gate and ask for Novamira MCP availability before any LocalWP runtime testing. Done for the LocalWP dry-run pass.

Open decisions before implementation:

- Confirm the default retention period. Implemented with the candidate default of 60 days.
- Confirm whether the first release should be manual-only with dry-run, or whether scheduled cleanup should ship in the same slice. Implemented as manual-only with preview; scheduled cleanup was intentionally deferred.
- Confirm whether existing Drime test uploads should be cleaned up as a separate manual maintenance task. Done for the known leftover E2E upload, which was moved to Drime trash after explicit approval.

### Feature Slice: Failed Upload Email Notifications

Status: implemented, runtime mail-stack verification complete, post-feature review workflows complete, and the path-redaction follow-up is released in `0.6.2`.

Goal:

- Notify administrators when a Drime upload reaches a meaningful failure state so unattended backup workflows do not fail silently.
- Use WordPress-native mail delivery through `wp_mail()` so SMTP plugins such as FluentSMTP, SureMail, WP Mail SMTP, and Post SMTP can handle transport automatically.
- Avoid notification spam from repeated cron retries, bad tokens, or transient Drime/API failures.

Recommended first slice:

- Add settings:
  - `failure_email_enabled`, default disabled. Done.
  - `failure_email_recipients`, default to `get_option( 'admin_email' )`. Done.
  - Optional `failure_email_test_recipient` is not needed if the test action uses the saved recipients. Done; no separate test-recipient setting was added.
- Add a notification service/class that:
  - builds plain-text email messages for failed uploads. Done.
  - sends with `wp_mail()`. Done.
  - records diagnostics for sent, skipped, and failed notification attempts. Done.
  - never includes Drime tokens, presigned URLs, raw request bodies, file contents, or stack traces. Done by limiting message fields and sanitizing/redacting failure reasons.
- Send notifications only when an upload becomes terminally failed:
  - manual upload action returns `WP_Error`. Done.
  - queue retry cap is reached and the item is marked failed/removed. Done.
- Do not send on every retry attempt. Done.
- Deduplicate notifications by backup signature plus failure state so repeated cron runs do not send the same alert repeatedly. Done.
- Add a manual admin action to send a test notification through the current WordPress mail stack. Done.
- Include a clear admin notice after the test action:
  - success when `wp_mail()` returns true. Done.
  - actionable failure copy when `wp_mail()` returns false. Done.
- Keep the first version plain text. Defer HTML templates unless there is a specific need. Done.

Email content:

- Subject: include site name and plugin context, such as `[Site Name] Drime backup upload failed`.
- Body should include:
  - site URL,
  - backup filename,
  - failure status/reason in sanitized plain language,
  - attempt count when available,
  - timestamp,
  - admin page URL for Tools > Drime WPvivid.
- Do not expose absolute server paths in the email unless explicitly approved later.

Tests and verification:

- Unit-test settings sanitization for enabled state and recipient list parsing.
- Unit-test notification dedupe behavior.
- Unit-test that `wp_mail()` is called with expected recipients, subject, message, and plain-text headers.
- Unit-test that disabled notifications do not send.
- Unit-test failure paths when `wp_mail()` returns false.
- Source unit coverage added for settings parsing, dedupe behavior, `wp_mail()` call shape, disabled notification behavior, failed mail results, and test-email delivery through saved recipients.
- Verify LocalWP runtime with the active site mail stack. Done on `plugin-tester.local` with SureMail/Emailit in temporary simulation mode:
  - sent a controlled failure notification through the plugin notifier and WordPress `wp_mail()`;
  - confirmed the duplicate notification path skipped the second identical send;
  - confirmed diagnostics recorded `failure_email_sent` and `failure_email_skipped` during the run;
  - confirmed SureMail log ID `13` recorded the simulated email without real delivery;
  - confirmed the email body used the backup basename only and redacted URL/path details from the failure reason.
- Run feature-stage workflows after implementation. Done:
  - `FEATURE_LIGHT_REVIEW_PROMPT.md` passed with one security/privacy follow-up.
  - `FEATURE_BLOAT_AND_STRUCTURE_REVIEW_PROMPT.md` Phase 1 measurement used the exact feature commit boundary; no feature-stage cleanup or split was needed. Existing oversized orchestration files remain pre-release structure-review territory.
  - `FEATURE_UI_UX_IMPLEMENTATION_PROMPT.md` passed for the settings fields, test-email action, and admin notices.
  - `FEATURE_SECURITY_REVIEW_PROMPT.md` found and fixed Unix-style absolute path leakage in failed-upload email reasons; regression coverage added.

Open decisions before implementation:

- Confirm whether notifications should default to disabled or enabled. Recommended: disabled for the first release.
- Confirm whether recipients should allow comma-separated emails, one email per line, or both. Implemented: both, normalized one per line internally.
- Confirm whether the first trigger should include manual upload failures, terminal cron failures, or both. Implemented: both, deduped by backup signature and failure state.
- Confirm whether to include absolute local backup paths in email. Implemented: no; emails use basenames only and redact URL/path substrings in failure reasons.

### Feature Slice: Drime Folder Browser And Destination Validator

Status: implemented in source. PHPUnit, PHPCS, build verification, and LocalWP/Drime runtime E2E are complete.

Runtime AJAX verification closeout recorded 2026-06-27 on `plugin-tester.local` with Novamira MCP:

- The installed runtime was Alynt Drime WPvivid Uploader `0.6.2` with a saved Drime token present.
- `alynt_drime_wpvivid_list_folders` rejected unauthenticated/unauthorized runtime dispatch and invalid-nonce administrator dispatches, then accepted an administrator dispatch with a valid nonce and returned `success=true` with `folders` and `page` keys.
- `alynt_drime_wpvivid_preview_destination` rejected unauthenticated/unauthorized runtime dispatch and invalid-nonce administrator dispatches, then accepted an administrator dispatch with a valid nonce and returned a sanitized Drime error for the currently saved destination because the saved base folder could not be resolved during the probe.
- All captured JSON responses were inspected against the saved Drime token and common sensitive markers such as `api_token`, `authorization`, `bearer`, `password`, `secret`, and `presigned`; none were found.
- No LocalWP settings, database rows, files, or live-site resources were changed during this verification.

Goal:

- Let administrators browse existing Drime folders directly from the plugin settings page instead of manually extracting folder IDs from Drime URLs.
- Keep the existing storage model backward-compatible: the selected base folder maps to `parent_folder_id`, and the typed site folder/subpath maps to `relative_path`.
- Support the practical workflow:
  - Select existing base folder: `General/Files/Backups`.
  - Type site folder: `site1.com`.
  - Upload destination resolves to `General/Files/Backups/site1.com`.
  - If `site1.com` already exists under the selected base folder, use it.
  - If `site1.com` does not exist, let the existing upload path creation flow create it before upload.
- Reduce destination mistakes by showing the resolved human-readable destination before uploads run.

Drime API references to verify during implementation:

- API base URL is `https://app.drime.cloud/api/v1`.
- Authentication uses the saved bearer token.
- `GET /cli/loggedUser` returns the authenticated user ID needed by the user-folder endpoint.
- `GET /users/{userId}/folders?workspaceId=0` returns a folder tree with `id`, `name`, `parent_id`, `path`, `workspace_id`, and often `hash`.
- `GET /drive/file-entries?workspaceId=0&type=folder&folderId={folderHash}` can list folder children, and supports pagination/search filters.
- `GET /folders/{folderHash}/path` returns a breadcrumb path for display.
- `POST /folders?workspaceId=0` creates a folder with `name` and `parentId`; keep this write path behind existing upload destination creation or an explicit future "Create Missing Folders" action.

Recommended first slice:

- Add Drime client methods:
  - `get_logged_user()`. Done.
  - `list_user_folders( $workspace_id )`. Done.
  - `list_folder_entries( $workspace_id, $folder_hash, $page, $query )`. Done.
  - `get_folder_path( $folder_hash )`. Done.
  - Reuse the existing create-folder/path-resolution code where possible instead of duplicating destination creation. Done; browsing and preview do not create folders.
- Add settings fields or metadata while preserving existing settings:
  - `parent_folder_id` remains the canonical upload anchor. Done.
  - `relative_path` remains the canonical subpath/site-folder field. Done.
  - Add optional non-secret display metadata such as `parent_folder_hash` and `parent_folder_display_path` if needed for breadcrumbs and validation. Done.
  - Existing installs with only a manually entered `parent_folder_id` must keep working. Done; preview can resolve the hash from the user-folder list when possible.
- Add a WordPress-native folder browser UI in the existing settings page:
  - Button: `Browse Drime Folders`. Done.
  - Browsing panel or modal lists folders with Name, Path/Breadcrumb, and actions. Done.
  - Row actions: `Open` and `Use as Base Folder`. Done.
  - Show selected base folder as human-readable text and keep the raw folder ID visible or available for advanced users. Done.
  - Keep the site folder field as normal text input, with help text explaining that missing folders are created during upload. Done.
  - Add `Preview Destination` / `Validate Destination` action that resolves the selected base folder plus relative path and reports existing vs missing folder segments without uploading backup bytes. Done.
- Use `wp_ajax_` admin actions for browser interactions:
  - Gate with `manage_options`. Done.
  - Verify nonces for every request. Done.
  - Return sanitized JSON only. Done.
  - Do not expose the saved Drime token in responses, diagnostics, browser data attributes, or JavaScript. Done.
  - Add visible loading states, disabled buttons during requests, and an `aria-live` status region. Done.
- Keep the first implementation read-safe by default:
  - Browsing and destination preview are read-only API calls. Done.
  - Do not create folders merely by browsing/selecting a base folder. Done.
  - Continue creating missing relative-path folders only when the upload path actually needs them, unless a later explicit manual "Create Missing Folders" action is approved. Done.
- Add diagnostics events:
  - `folder_browser_loaded`. Done.
  - `folder_browser_failed`. Done.
  - `destination_preview_started`. Done.
  - `destination_preview_finished`. Done.
  - `destination_preview_failed`. Done.
  - Redact URLs/tokens as the existing diagnostics layer already requires. Done.

Tests and verification:

- Unit-test Drime client request shapes and response parsing for logged-user, folder tree, file entries, and folder path endpoints. Done.
- Unit-test settings sanitization for any new display metadata fields. Done.
- Unit-test that old settings with only `parent_folder_id` and `relative_path` still resolve uploads the same way. Done for upload request/body behavior.
- Unit-test that AJAX handlers require `manage_options` and valid nonces. Pending; handlers are implemented with capability and nonce checks and need runtime/E2E verification.
- Unit-test that AJAX responses never include the API token. Done at service normalization level; runtime AJAX response inspection remains pending.
- Unit-test destination preview behavior for:
  - existing base folder and existing site folder. Done.
  - existing base folder and missing site folder. Done.
  - invalid/missing parent folder. Done.
  - Drime API error. Done.
  - empty relative path. Done.
- Verify in LocalWP with `plugin-tester local-only` after the Site Operations confirmation gate:
  - Confirm Novamira MCP is available before starting; if unavailable, stop and ask the user to enable it.
  - Browse folders with the saved Drime token.
  - Select `General/Files/Backups`-style base folder.
  - Type a site folder such as `site1.com`.
  - Preview the resolved destination.
  - Confirm upload behavior still creates/uses the final relative path correctly.

Feature-stage workflows to run after implementation, in this order:

1. `C:\Users\Captain\Documents\AI Workflows\Toolkits\wp-plugin-toolkit\d4-prompts\ds2-feature\FEATURE_LIGHT_REVIEW_PROMPT.md`
2. `C:\Users\Captain\Documents\AI Workflows\Toolkits\wp-plugin-toolkit\d4-prompts\ds2-feature\FEATURE_BLOAT_AND_STRUCTURE_REVIEW_PROMPT.md`
   - Applicable because this feature will likely add PHP and JavaScript for AJAX/admin UI.
3. `C:\Users\Captain\Documents\AI Workflows\Toolkits\wp-plugin-toolkit\d4-prompts\ds2-feature\FEATURE_UI_UX_IMPLEMENTATION_PROMPT.md`
   - Applicable because the feature changes admin settings UI and async interactions.
4. `C:\Users\Captain\Documents\AI Workflows\Toolkits\wp-plugin-toolkit\d4-prompts\ds2-feature\FEATURE_SECURITY_REVIEW_PROMPT.md`
   - Applicable because the feature uses saved tokens, AJAX, remote API calls, and settings writes.
5. `C:\Users\Captain\Documents\AI Workflows\Toolkits\wp-plugin-toolkit\d4-prompts\ds6-maintenance\DOCUMENTATION_SYNC_AUDIT_PROMPT.md`
   - Applicable after implementation because README/readme/settings docs and changelog will need to match the UI and behavior.

End-to-end testing workflow to run after implementation and feature-stage reviews:

- Run `C:\Users\Captain\Documents\AI Workflows\Task Workflows\WordPress\wordpress-component-testing-troubleshooting-debugging-workflow.md` against the plugin on `plugin-tester local-only`.
- Do a full plugin E2E pass, including the new Drime folder browser, destination preview, settings save/reload, WPvivid scan/upload path, diagnostics, and debug/runtime evidence.
- Ensure Novamira MCP is available before the E2E pass. If it is unavailable, stop and let the user enable it before continuing.

Release workflow after successful implementation and E2E:

- Run `C:\Users\Captain\Documents\AI Workflows\Toolkits\wp-plugin-toolkit\d4-prompts\ds5-git\GIT_OPERATIONS_PROMPT.md` Option C.
- Follow the mandatory release approval checkpoint before commit/tag/push/GitHub release publishing.

Implementation decisions:

- The browser starts from the user-folder list and supports child navigation by folder hash instead of creating folders or loading every nested child eagerly.
- The plugin stores non-secret `parent_folder_hash` and `parent_folder_display_path` metadata so saved selections can display and preview cleanly.
- The first version includes read-only preview only; there is no manual `Create Missing Folders` action.
- LocalWP/Drime E2E showed direct uploads do not reliably honor `relativePath` alongside `parentId`; upload processing now resolves or creates the final concrete destination folder when needed, then uploads to that folder ID.

### Feature Slice: Drime Workspace Picker

Status: shipped in the `0.5.0` release line and present in the current stable release. Closeout bookkeeping is complete.

Closeout evidence recorded 2026-06-27:

- Feature Light Review: passed as a source-evidence review of the `v0.5.0` workspace-picker surface. The feature touches admin UI, AJAX, settings persistence, and the Drime `/me/workspaces` API; it follows the existing settings/admin-page architecture and no significant non-security issues were found.
- Feature Bloat And Structure Review: passed for the workspace-picker surface. The explicit historical boundary was `v0.4.0` to `v0.5.0`; the helper was run with `-BaseRef v0.4.0`, then the tag diff was sanity-checked against `v0.5.0`. Workspace-specific files are within feature-stage thresholds: `includes/class-workspace-browser.php` is `123` total lines, `assets/admin-workspaces.js` and `assets/src/admin/workspaces.js` are `147` total lines each, `tests/WorkspaceBrowserTest.php` is `91` total lines, and `tests/Support/WorkspaceBrowserClient.php` is `33` total lines. Oversized orchestration/test files reported by the current-state measurement predate or outlive the workspace picker and remain pre-release structure-review territory, not workspace closeout blockers.
- Feature UI/UX Implementation Review: passed by source review against the design-system guide. The workspace picker uses the existing WordPress settings table, a native button/select/input pattern, visible loading state, `aria-busy`, spinner state, and an `aria-live` status region. It clears selected base-folder metadata when the workspace changes and tells the administrator to save settings.
- Feature Security Review: passed. The workspace AJAX action uses the shared `verify_ajax_action()` gate, which requires `manage_options` and `check_ajax_referer( 'alynt_drime_wpvivid_folder_browser', 'nonce', false )`; settings sanitization clears stale folder metadata on workspace changes; workspace responses are normalized through `Alynt_Drime_WPvivid_Uploader_Workspace_Browser` and do not return `api_token`.
- Documentation Sync Audit: passed for the workspace picker. `README.md`, `readme.txt`, `docs/SETTINGS.md`, and `CHANGELOG.md` document workspace loading, workspace ID behavior, and base-folder clearing when the workspace changes.
- Release verification: current source validation passed with `npm.cmd test` (`86` tests, `238` assertions), `npm.cmd run lint` (`43 / 43` PHPCS files), and `npm.cmd run build`.
- Novamira MCP availability was confirmed on `plugin-tester.local`; no LocalWP state changes or live-site operations were needed for this documentation closeout.

Goal:

- Let administrators retrieve and select a Drime workspace from the plugin settings screen instead of manually typing workspace IDs.
- Keep `workspace_id` as the canonical stored setting and keep `0` as the personal/default Drime workspace.
- Keep workspace browsing read-only. Loading workspaces must not create folders, change Drime account state, or expose the saved bearer token.
- Clear selected base-folder metadata when the workspace changes so a folder ID from one workspace is not accidentally reused in another workspace.

Verified API reference:

- `GET /me/workspaces` returns all workspaces available to the authenticated token.
- The API response includes a `workspaces` array with fields such as `id`, `name`, `members_count`, `currentUser.role_name`, and owner/current-user flags.
- Drime's workspace context rule remains: use `workspaceId=0` for the personal/default workspace and a specific workspace ID for team workspaces.

Implementation plan:

- Add a Drime client method:
  - `list_workspaces()`.
- Add a workspace browser service that normalizes the Drime response into sanitized, non-secret rows for the admin UI.
- Add a `wp_ajax_` admin action:
  - `alynt_drime_wpvivid_list_workspaces`.
  - Gate with `manage_options`.
  - Verify the existing folder-browser/admin nonce.
  - Return sanitized JSON only; never return the token, raw account payload, or sensitive account details.
- Add a WordPress-native settings UI:
  - Keep the manual numeric `Workspace ID` field for advanced/manual fallback.
  - Add `Load Drime Workspaces`.
  - Populate a native `<select>` with the personal/default workspace and returned team workspaces.
  - Selecting a workspace updates `workspace_id`, clears selected base-folder metadata, and tells the user to save settings.
- Add tests:
  - client endpoint coverage for `/me/workspaces`.
  - response normalization coverage.
  - settings coverage that changing workspaces clears stale parent-folder metadata.

Historical release workflow after successful implementation and E2E:

- Run the applicable feature-stage prompts in this order:
  - `FEATURE_LIGHT_REVIEW_PROMPT.md`.
  - `FEATURE_BLOAT_AND_STRUCTURE_REVIEW_PROMPT.md`.
  - `FEATURE_UI_UX_IMPLEMENTATION_PROMPT.md`.
  - `FEATURE_SECURITY_REVIEW_PROMPT.md`.
  - `DOCUMENTATION_SYNC_AUDIT_PROMPT.md`.
- Run `C:\Users\Captain\Documents\AI Workflows\Toolkits\wp-plugin-toolkit\d4-prompts\ds5-git\GIT_OPERATIONS_PROMPT.md` Option C.
- Follow the mandatory release approval checkpoint before commit/tag/push/GitHub release publishing.

### 7. Admin UX Pass

Run the UI workflow after the core feature paths are stable:

```text
@FEATURE_UI_UX_IMPLEMENTATION_PROMPT.md run
```

Status: complete for the current admin screen. The pass connected help text with `aria-describedby`, added missing action feedback notices, improved error copy, applied destructive button styling for clear actions, added diagnostics settings controls, and ensured disabled action buttons also set `aria-disabled`.

Focus:

- settings layout and labels
- diagnostics table readability
- manual action feedback
- empty states
- confirmation for destructive actions
- accessibility of buttons, form controls, notices, and status messages

### 8. Feature Security Review

Status: complete for the current changed feature code.

Findings/fixes:

- Admin form/action handlers are guarded by `manage_options` and `check_admin_referer()`.
- Diagnostics export and admin UI output are capability-gated and escaped/redacted.
- Drime multipart part uploads now use `wp_safe_remote_request()` and reject unsafe or non-HTTPS signed URLs before transmitting backup bytes.
- Diagnostics now redacts bearer tokens and HTTP(S) URLs when they appear inside scalar values such as transport error messages, not only when sensitive context keys are used.

Verification:

- `npm.cmd run lint` passed.
- `npm.cmd test` passed: 24 tests, 64 assertions.
- `npm.cmd run build` passed.
- Changed PHP files passed `php -l`.
- `git diff --check` passed with existing CRLF warnings only.

### 9. LocalWP Install And Integration Test

Status: mostly complete on `plugin-tester.local`; current stable release and updater validation are recorded in Current State. Remaining notes below are follow-up edge cases, not blockers for `0.6.2`.

Verified:

- Plugin runtime files were copied into `C:\Users\Captain\Local Sites\plugin-tester\app\public\wp-content\plugins\alynt-drime-wpvivid-uploader` and activated.
- Current Remote Drime Retention runtime files were copied into the LocalWP plugin directory after Novamira MCP became available.
- Remote Drime Retention dry-run verification passed through Novamira MCP: the class and plugin method load, defaults merge as disabled/60 days, preview selects only old plugin-owned uploaded-registry records with Drime file-entry IDs, admin UI renders retention controls and candidate output, options are restored exactly after probes, and no remote Drime delete/trash request was executed during dry-run.
- Remote Drime Retention live-trash verification passed after explicit approval: preview isolated only `alynt-e2e-chunk-20260620-144909.zip` with Drime entry id `760413126`, cleanup returned `candidates=1`, `trashed=1`, `failed=0`, `skipped=0`, settings were restored, the E2E upload is now marked `remote_status=trashed`, and the real WPvivid database backup remains `uploaded`.
- Remote retention-day sanitization now clamps signed input to the supported 1-365 range; LocalWP runtime confirmed `-5` becomes `1` and `999` becomes `365` without writing options.
- Default settings option was installed; auto-scan cron remains unscheduled while automatic scanning is disabled.
- Admin page loads in wp-admin with no console errors.
- Settings save works through `admin-post.php`.
- Diagnostics controls render, diagnostics settings persist through a normal save, and diagnostics export returns JSON without bearer tokens or `api_token`.
- WPvivid generated a real local database backup: `plugin-tester.local_wpvivid-0d2ea337c5c5b_2026-06-19-21-45_backup_db.zip`.
- Scanner first captured the new backup snapshot without queueing, then queued it on the second scan after size/age stability was established.
- Queued item includes `wpvivid_backup_list` metadata with `from_list: true`, backup id `wpvivid-0d2ea337c5c5b`, and one listed file.
- Upload without a Drime token fails safely, leaves active upload state empty, increments attempts to 1, records one failed item, and logs redacted diagnostics.
- Earlier live Drime upload test state verified the queue empty, uploaded registry count `1`, failed registry count `0`, active upload state empty, diagnostics enabled at debug level, minimum file age set to 60 seconds, and the resolved Drime parent folder ID cached for the configured relative path. Latest packaged install validation is recorded separately below.
- Early packaged zip validation passed after the pre-release closeout for `C:\Users\Captain\Desktop\alynt-drime-wpvivid-uploader-0.1.0.zip`: it contained 33 production files, had the expected top-level plugin folder and main plugin file, used forward-slash zip entries, excluded dev/tooling/handoff/generated files including `assets/dist`, installed over the LocalWP runtime copy through WordPress's `Plugin_Upgrader` with `overwrite_package=true`, activated successfully, rendered the admin page in a WordPress runtime probe, preserved plugin options, and left queue count `0`, failed count `0`, uploaded registry count `0`, and active upload empty.

Follow-up edge cases:

- Duplicate handling against existing Drime files is current for cached concrete parent-folder IDs. Local registry prevention is verified, earlier remote duplicate-skip behavior was validated using a copied backup with the same basename and cached top-level `parentId`, and the 2026-06-27 non-uploading runtime probe confirmed Drime duplicate validation returns one duplicate for an existing uploaded name under cached concrete parent `762160507` and zero duplicates for a generated unique name under that same parent. Relative-path-only request variants remain unreliable by accepted decision, not an active implementation target.
- Malformed multipart failure shapes have unit coverage for create/sign response failures; HTTP `429` rate-limit behavior has unit coverage. Live rate-limit induction remains intentionally deferred by accepted safety decision to avoid abusive API traffic; revisit only for a specific incident or explicit approval.
- Live Remote Drime Retention cleanup has been verified once against the approved leftover E2E test upload; broader arbitrary-folder cleanup is intentionally out of scope for this feature by accepted decision.

Use `plugin-tester local-only` after the confirmation gate.

Test sequence:

- install and activate plugin on `plugin-tester.local`
- confirm admin page loads
- save settings without token and verify validation behavior
- enable diagnostics and verify redacted logs
- scan WPvivid backup folder
- create a manual WPvivid backup
- confirm scanner waits for file stability
- queue backup or backup set
- upload tiny test file when safe
- upload real WPvivid backup archive
- upload a multipart-threshold test archive
- resume an interrupted multipart-threshold test archive
- confirm file appears visibly in Drime
- confirm duplicate handling prevents re-upload

### 10. Failure Testing

Test:

- missing token
- invalid token
- unreadable backup folder
- missing local file after queueing
- interrupted multipart upload
- Drime API error
- malformed Drime response (multipart create/sign response and HTTP `429` unit coverage added; broader live malformed cases pending if safe, live rate-limit induction intentionally deferred unless explicitly approved for a specific incident)
- duplicate remote filename
- disabled diagnostics
- diagnostics export and clear actions

### 11. Pre-Release Review Sequence

After MVP functionality works locally, run the pre-release sequence:

```text
@01-CODE_CLEANUP_PROMPT.md run
@02-FILE_STRUCTURE_REVIEW_PROMPT.md run
@03-ERROR_HANDLING_REVIEW_PROMPT.md run
@04-WP_BEST_PRACTICES_REVIEW_PROMPT.md run
@05-DATABASE_REVIEW_PROMPT.md run
@06-PERFORMANCE_REVIEW_PROMPT.md run
@07-EDGE_CASES_REVIEW_PROMPT.md run
@08-UNINSTALL_REVIEW_PROMPT.md run
@09-I18N_REVIEW_PROMPT.md run
@10-ACCESSIBILITY_REVIEW_PROMPT.md run
@11-CODE_QUALITY_REVIEW_PROMPT.md run
@12-DOCUMENTATION_REVIEW_PROMPT.md run
@13-SECURITY_AUDIT_PROMPT.md run
```

Record each completed workflow in `PRE_RELEASE_CHECKLIST.md` only after successful completion.

Status:

- `@01-CODE_CLEANUP_PROMPT.md run` through `@13-SECURITY_AUDIT_PROMPT.md run` completed on 2026-06-20 and are recorded in `PRE_RELEASE_CHECKLIST.md`.
- Final release validation lint/test/build rows completed on 2026-06-20 and are recorded in `PRE_RELEASE_CHECKLIST.md`.

## Acceptance Criteria For MVP

- Plugin settings save securely.
- Drime token test works.
- Scanner detects completed WPvivid local backups.
- Scanner avoids incomplete files and partial backup sets.
- Queue does not duplicate already uploaded files.
- At least one real WPvivid backup archive uploads to Drime.
- Uploaded file becomes visible in Drime.
- Multipart upload can resume or safely restart after interruption.
- Admin can view useful redacted diagnostics.
- No secrets are exposed in UI, logs, exports, or docs.
- Plugin passes lint, syntax checks, and relevant local integration tests.

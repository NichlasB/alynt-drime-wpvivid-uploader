# Alynt Drime WPvivid Uploader Implementation Plan

Updated: 2026-06-19

## Current State

- Development repo exists at `C:\Development\WordPress\Plugins\alynt-drime-wpvivid-uploader`.
- Plugin scaffold is in place with settings, scanner, queue, registry, Drime client, uploader, cron, admin page, uninstall cleanup, README, changelog, and settings docs.
- Diagnostics and observability workflow is complete.
- PHP syntax checks pass across all PHP files.
- The plugin has not been installed on `plugin-tester.local`.
- Composer/npm dependencies have not been installed, and PHPCS has not been run.
- No initial Git commit has been made.

## Target Test Site

Use the LocalWP profile:

- Site key: `plugin-tester`
- Mode: `local-only`
- LocalWP site name: `Plugin Tester`
- LocalWP domain: `plugin-tester.local`
- WordPress path: `C:\Users\Captain\Local Sites\plugin-tester\app\public`

Before installing or testing on this LocalWP site, follow the Site Operations confirmation gate. Novamira MCP is available and reports both `WPvivid Backup Plugin` and `WPvivid Plugins Pro` active.

## Remaining Implementation Work

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

Verify:

- default and custom local backup path option names
- backup file naming patterns
- split archive naming patterns
- temporary/incomplete file markers
- backup history/task option structures
- any stable action/filter hooks that signal backup completion

Expected output:

- update `class-wpvivid-detector.php` if reliable option names are found
- update scanner grouping logic if split backup sets need set-level handling
- document verified assumptions in `docs/WPVIVID_RESEARCH.md`

### 4. Drime API Schema Verification

Confirm exact request/response schemas against current Drime docs and, when a token is available, safe live test calls.

Verify:

- token test endpoint
- folder creation behavior
- duplicate validation response shape
- available-name response shape
- small upload endpoint behavior
- multipart create response keys
- signed part URL response shape
- ETag formatting requirements
- complete multipart response shape
- `/s3/entries` registration body and response shape
- whether `relativePath`, `parentId`, and `workspaceId=0` behave as expected

Expected output:

- update `class-drime-client.php` field names and parsers
- add defensive handling for malformed responses
- document verified schemas in `docs/DRIME_API_RESEARCH.md`

### 5. Scanner And Queue Hardening

Improve MVP scanner behavior after WPvivid verification.

Needed:

- group split backups into complete backup sets
- prevent queueing partial/incomplete backup sets
- store set-level metadata where needed
- improve duplicate queue handling
- add retry cap enforcement
- add stale active-upload recovery behavior

### 6. Upload Flow Hardening

Improve uploader reliability before real backup testing.

Needed:

- verify small-file upload path or replace it with the documented Drime upload flow if needed
- support multipart resume using saved state and `get-uploaded-parts`
- persist multipart state after each uploaded part
- add abort/clear stale upload state
- ensure local deletion remains disabled by default and requires explicit admin opt-in
- avoid logging file contents, tokens, presigned URLs, or raw request bodies

### 7. Admin UX Pass

Run the UI workflow after the core feature paths are stable:

```text
@FEATURE_UI_UX_IMPLEMENTATION_PROMPT.md run
```

Focus:

- settings layout and labels
- diagnostics table readability
- manual action feedback
- empty states
- confirmation for destructive actions
- accessibility of buttons, form controls, notices, and status messages

### 8. LocalWP Install And Integration Test

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
- confirm file appears visibly in Drime
- confirm duplicate handling prevents re-upload

### 9. Failure Testing

Test:

- missing token
- invalid token
- unreadable backup folder
- missing local file after queueing
- interrupted multipart upload
- Drime API error
- malformed Drime response
- duplicate remote filename
- disabled diagnostics
- diagnostics export and clear actions

### 10. Pre-Release Review Sequence

After MVP functionality works locally, run the pre-release sequence:

```text
@01-CODE_CLEANUP_PROMPT.md run
@02-FILE_STRUCTURE_REVIEW_PROMPT.md run
@03-DOCUMENTATION_REVIEW_PROMPT.md run
@04-I18N_REVIEW_PROMPT.md run
@05-ACCESSIBILITY_REVIEW_PROMPT.md run
@06-ERROR_HANDLING_REVIEW_PROMPT.md run
@07-WP_BEST_PRACTICES_REVIEW_PROMPT.md run
@08-PERFORMANCE_REVIEW_PROMPT.md run
@09-DATABASE_REVIEW_PROMPT.md run
@10-UNINSTALL_REVIEW_PROMPT.md run
@11-EDGE_CASES_REVIEW_PROMPT.md run
@12-CODE_QUALITY_REVIEW_PROMPT.md run
@13-SECURITY_AUDIT_PROMPT.md run
```

Record each completed workflow in `PRE_RELEASE_CHECKLIST.md` only after successful completion.

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

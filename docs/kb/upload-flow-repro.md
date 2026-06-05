# Upload Flow Reproduction Notes

Last checked: 2026-06-05

This page records local upload-flow reproduction checks for support reports about inactive upload buttons or failed uploads.

## 2026-06-05: Add/Import Files

GitHub issue: #9, "Reproduce upload flow reports on current local environment"

### Environment

| Item | Value |
| --- | --- |
| Local URL | `https://wp-dev.loc/wp-admin/admin.php?page=GrandMedia_AddMedia` |
| WordPress | 7.0 |
| Gmedia Gallery | 1.25.0 |
| PHP CLI | 8.4.3 |
| Upload UI | `jquery.plupload.queue` 2.3.9, WordPress core Plupload 2.1.9 |

### Steps

1. Open `Gmedia -> Add/Import Files`.
2. Confirm upload UI renders with `Add Files`, `Start Upload`, and the drag/drop area.
3. Select a valid PNG file from disk.
4. Confirm the selected file appears in the queue.
5. Confirm `Start Upload` no longer has the `plupload_disabled` class.
6. Click `Start Upload`.
7. Confirm final row class, footer text, console output, and generated files on disk.

### Result

Status: not reproduced for the reported inactive `Start Upload` behavior.

Evidence:

- Empty queue: `Start Upload` has class `plupload_button plupload_start plupload_disabled`, which is expected.
- After selecting `/private/tmp/gmedia-upload-test-64.png`, the file row appears as `plupload_delete`, and `Start Upload` changes to `plupload_button plupload_start`.
- After clicking `Start Upload`, the file row changes to `plupload_done`.
- Footer text becomes `Uploaded 1/1 files 100% 313 b`.
- Files are created:
  - `wp-content/grand-media/image/original/gmedia-upload-test-64.png`
  - `wp-content/grand-media/image/thumb/gmedia-upload-test-64.png`
  - `wp-content/grand-media/image/gmedia-upload-test-64.png`
- Browser console showed jQuery Migrate warnings only:
  - `jQuery.trim is deprecated; use String.prototype.trim`
  - `jQuery.fn.click() event shorthand is deprecated`
  - `jQuery.parseJSON is deprecated; use JSON.parse`

### Notes

- A tiny 1x1 PNG test file queued correctly and enabled `Start Upload`, but finished as `plupload_failed` while the footer still said `Uploaded 1/1 files 100% 69 b`. No file was created on disk for that tiny file. This was not treated as the main repro because a normal 64x64 PNG uploaded successfully.
- Current local `debug.log` shows WordPress 7.0 deprecation output for `addslashes_gpc()` in Gmedia code paths. This is separate from the upload-button report, but should become a compatibility issue because debug output can break JSON/header flows in some contexts.

### Follow-Up Candidates

- #10: Replace deprecated `addslashes_gpc()` usage for WordPress 7.0 compatibility.
- If users continue reporting disabled upload buttons, collect their browser console, WordPress version, PHP version, selected file type/size, and whether the file appears in the queue before `Start Upload`.
- If users report "Uploaded 1/1" but no file appears, reproduce with the exact file and inspect the upload AJAX response.

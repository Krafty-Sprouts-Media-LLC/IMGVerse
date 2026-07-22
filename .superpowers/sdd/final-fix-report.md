# Final Fix Report — IMGVerse 2.0 whole-branch Critical/Important

**Branch:** `feat/imgverse-editor-ux`  
**Worktree:** `.worktrees/feat-imgverse-editor-ux`  
**Date:** 22/07/2026  
**Status:** FIXED (automated tests green; Local WP browser smoke still MANUAL_REQUIRED)

---

## Summary of fixes

### Critical

1. **Import SSRF / unsafe download** (`IMGV_API::import_image`)
   - Switched download to `wp_safe_remote_get()` with limited redirects.
   - Require HTTP response code in 2xx range.
   - Allowlist https hosts for Openverse/Unsplash/Pixabay/Pexels/Wikimedia/iNaturalist (+ related CDN suffixes); filterable via `imgv_allowed_import_host_suffixes`.
   - Validate image MIME via `wp_get_image_mime()` with `getimagesize()` fallback; reject non-image MIME.
   - On MIME failure, resize failure, or attachment insert failure after `wp_upload_bits`, delete the orphaned file via `wp_delete_file` / `delete_upload_file()`.

### Important

2. **Media modal post-import flow** (`src/js/media-modal.js`, `Photo.js`)
   - Store active MediaFrame instance; expose `window.imgvSelectImportedAttachment`.
   - After successful modal import: switch to Browse (`#menu-item-browse` + `content.mode('browse')`), fetch attachment, `selection.reset(model)`.

3. **`edit_post` capability**
   - REST `POST /import` and AJAX `imgv_import`: if `post_id > 0`, require `IMGV_API::user_can_attach_to_post()` (`current_user_can( 'edit_post', $post_id )`); else 403 / failure JSON.

4. **API key settings**
   - Password inputs for Unsplash/Pixabay/Pexels render `value=""`; sanitize still preserves existing keys when submitted blank.

5. **Resize failure orphan**
   - Covered by delete-on-failure after `wp_upload_bits` when `maybe_resize_file` returns `WP_Error`.

6. **Per-image insert size UI**
   - Sidebar `Photo` shows size select (thumbnail/medium/large/full) defaulting to `imgvData.defaultInsertSize`; passed to `insertImage`.

---

## Files touched

- `includes/class-imgv-api.php` — URL allowlist, safe download, MIME, cleanup helpers, hardened `import_image`
- `includes/class-imgv-rest.php` — `edit_post` gate on import
- `imgverse.php` — AJAX import `edit_post` gate
- `includes/class-imgv-admin.php` — empty password field values
- `src/js/media-modal.js` — select imported attachment in frame
- `src/js/components/Photo.js` — modal select hook + sidebar size select
- `src/scss/style.scss` — select field styles
- `tests/php/test-class-imgv-api-resize.php` — URL/MIME/`edit_post` unit tests
- `CHANGELOG.md` — entries under `[2.0.0]` only
- `build/*` — rebuilt assets

---

## Test commands and output

### PHPUnit

```text
./vendor/bin/phpunit -c phpunit.xml.dist

PHPUnit 9.6.35 by Sebastian Bergmann and contributors.

.............                                                     13 / 13 (100%)

Time: 00:01.929, Memory: 32.00 MB

OK (13 tests, 57 assertions)
```

New/extended coverage:
- `test_is_allowed_import_url_rejects_unsafe_urls`
- `test_is_allowed_import_url_accepts_provider_hosts`
- `test_downloaded_image_mime_validation`
- `test_user_can_attach_to_post_requires_edit_post`

### Jest (thumbFallback)

```text
npx wp-scripts test-unit-js src/js/utils/thumbFallback.test.js --watchAll=false

PASS src/js/utils/thumbFallback.test.js
  thumbFallback
    √ uses thumb when present
    √ falls back to full when thumb empty
    √ on error swaps thumb to full once
    √ on error of full returns null

Test Suites: 1 passed, 1 total
Tests:       4 passed, 4 total
```

### npm build

```text
npm run build

webpack 5.108.4 compiled successfully in 4168 ms
```

Entrypoints: `media-modal`, `plugin-sidebar` (with shared `style-media-modal.css`).

---

## Remaining MANUAL_REQUIRED

Local WP Admin browser smoke was **not** run (per instructions). Still required manually:

- Media modal: search → import → confirm attachment is selected in Media Library / Insert ready
- Block editor sidebar: import → insert size select → Insert / Set featured
- Settings: API key fields empty on load; blank save preserves keys
- Import rejection: non-allowlisted / http URL fails safely
- Attach-to-post: user without `edit_post` for target post gets 403

---

## Commits

- `e2553a4` — fix: harden import download and media modal post-import UX

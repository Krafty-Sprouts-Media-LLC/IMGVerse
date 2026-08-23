<!--
/**
 * Release packaging notes for production plugin zips.
 *
 * @package IMGVerse
 */
-->

# Release packaging (production zip)

Use this checklist whenever you build a zip for upload to a live WordPress site (**Plugins → Add New → Upload Plugin**).

## Required zip layout

WordPress expects a **single top-level folder** whose name is the install directory slug. That folder name must **not** include the version.

| Correct | Incorrect |
|---------|-----------|
| Zip file may be named `imgverse.zip` or `imgverse-2.1.8.zip` | — |
| Inside the zip: `imgverse/imgverse.php` | Inside the zip: `imgverse-2.1.8/imgverse.php` |
| Paths use **forward slashes** (`imgverse/includes/...`) | Windows backslashes (`imgverse\includes\...`) |

If the top-level folder includes the version (or the archive uses Windows `Compress-Archive` backslash paths), WordPress may fail to detect or activate the plugin on Linux hosts.

## What to include

Runtime only:

- `imgverse.php`, `uninstall.php`
- `includes/`
- `assets/`
- `build/` (run `npm run build` first)
- `languages/` (if present)
- `readme.txt`, `README.md`, `CHANGELOG.md`

## What to exclude

- `node_modules/`, `vendor/`, `src/`, `tests/`, `docs/`
- `.git/`, `.agents/`, `.worktrees/`, `.superpowers/`
- `package.json`, `package-lock.json`, `composer.json`, `composer.lock`
- `webpack.config.js`, `phpunit.xml.dist`
- Local reference trees (e.g. `instant-images-main/`)
- Any `*.zip` artifacts

## Verify before upload

1. List zip entries and confirm the **only** top-level name is `imgverse`.
2. Confirm `imgverse/imgverse.php` exists and has a valid `Plugin Name:` header.
3. Prefer extracting the zip to a temp folder and checking the folder name is exactly `imgverse`.
4. On Windows, do **not** rely on `Compress-Archive` alone for production zips — it often writes backslash paths. Build entries with forward slashes (e.g. .NET `ZipArchive` with `/` in entry names, or `zip`/`bsdtar` with Unix paths).

## Suggested output location

Place the finished zip next to the plugin in the local WordPress plugins directory for easy upload, e.g.:

`wp-content/plugins/imgverse.zip`

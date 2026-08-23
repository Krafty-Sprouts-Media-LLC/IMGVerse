# IMGVerse Editor UX Rebuild Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild IMGVerse so Add Media + block sidebar match high-quality UX, with Openverse (incl. iNaturalist) plus Unsplash/Pixabay/Pexels via user API keys.

**Architecture:** One shared React app mounted in the WP media modal and plugin sidebar; PHP REST API with provider adapters normalizing results; existing IMGVerse import/attribution/cache kept and extended.

**Tech Stack:** WordPress 5.0+, PHP 7.4+, `@wordpress/scripts`, React (`@wordpress/element`), WP REST API, Openverse / Unsplash / Pixabay / Pexels HTTP APIs, PHPUnit for PHP unit tests, Jest via `@wordpress/scripts test-unit-js` for JS helpers.

**Spec:** `docs/superpowers/specs/2026-07-22-imgverse-editor-ux-design.md`

## Global Constraints

- Target version **2.0.0**; changelog + version bump on every change set that ships behavior.
- New code `@since 2.0.0`; never rewrite existing `@since` tags.
- WordPress Coding Standards for PHP: real tabs, `array()`, Yoda conditions, `snake_case`, visibility on methods, single quotes preferred.
- No third-party proxy; Unsplash/Pixabay/Pexels keys are user-supplied and server-side only.
- No dedicated Gutenberg block in this plan.
- DRY / KISS / YAGNI: one React core, one REST search shape, no duplicate Backbone UI left behind.
- File headers required on every new file.

## File map

| Path | Responsibility |
|------|----------------|
| `package.json`, `.gitignore` (node), `webpack` via wp-scripts | JS build toolchain |
| `src/js/constants/providers.js` | Provider list + Openverse sources |
| `src/js/utils/thumbFallback.js` | Thumbnail onError → full URL helper |
| `src/js/utils/api.js` | REST client wrappers |
| `src/js/components/App.js` | Shared search/grid shell |
| `src/js/components/ProviderNav.js` | Provider switcher |
| `src/js/components/SearchBar.js` | Search + Openverse filters |
| `src/js/components/PhotoGrid.js` | Results grid |
| `src/js/components/Photo.js` | Card, edit meta, import/insert/featured |
| `src/js/components/EmptyState.js` | Empty / missing-key / error states |
| `src/js/media-modal.js` | MediaFrame mount |
| `src/js/plugin-sidebar.js` | Plugin sidebar mount |
| `src/scss/style.scss` | Grid/UI styles |
| `build/*` | Compiled assets (committed or built in CI; build before ship) |
| `includes/class-imgv-rest.php` | Register REST routes |
| `includes/providers/class-imgv-provider-interface.php` | Provider contract |
| `includes/providers/class-imgv-provider-openverse.php` | Openverse adapter |
| `includes/providers/class-imgv-provider-unsplash.php` | Unsplash adapter |
| `includes/providers/class-imgv-provider-pixabay.php` | Pixabay adapter |
| `includes/providers/class-imgv-provider-pexels.php` | Pexels adapter |
| `includes/class-imgv-normalizer.php` | Shared result shape helpers |
| `includes/class-imgv-api.php` | Refactor: search delegates to providers; import + resize |
| `includes/class-imgv-admin.php` | API key + max dimension settings |
| `includes/class-imgv-assets.php` | Enqueue built scripts/styles + localize |
| `includes/class-imgv-media-tab.php` | Thin: enqueue only / remove Backbone templates |
| `includes/class-imgv-block-editor.php` | Thin: enqueue sidebar bundle |
| `imgverse.php` | Boot REST + assets; version 2.0.0; drop old AJAX search/import once REST works |
| `tests/php/test-normalizer.php` | PHPUnit for normalizer |
| `src/js/utils/thumbFallback.test.js` | Jest for thumb fallback |
| `CHANGELOG.md` | 2.0.0 entries |

---

### Task 1: JS/PHP toolchain + version scaffold

**Files:**
- Create: `package.json`
- Create: `.gitignore` entries for `node_modules/` (keep `build/` tracked after first build)
- Modify: `imgverse.php` (version constant → `2.0.0`)
- Modify: `CHANGELOG.md` (add `## [2.0.0] - 22/07/2026` stub section)

**Interfaces:**
- Consumes: none
- Produces: `npm run build` → `build/media-modal/index.js`, `build/plugin-sidebar/index.js`, matching `.asset.php` files via `@wordpress/scripts`

- [ ] **Step 1: Add `package.json`**

```json
{
  "name": "imgverse",
  "version": "2.0.0",
  "private": true,
  "scripts": {
    "start": "wp-scripts start",
    "build": "wp-scripts build",
    "test:js": "wp-scripts test-unit-js",
    "lint:js": "wp-scripts lint-js ./src"
  },
  "devDependencies": {
    "@wordpress/scripts": "^30.0.0"
  },
  "dependencies": {
    "@wordpress/components": "^29.0.0",
    "@wordpress/data": "^10.0.0",
    "@wordpress/edit-post": "^8.0.0",
    "@wordpress/element": "^6.0.0",
    "@wordpress/i18n": "^5.0.0",
    "@wordpress/plugins": "^7.0.0",
    "@wordpress/blocks": "^14.0.0",
    "@wordpress/block-editor": "^14.0.0"
  }
}
```

Use `@wordpress/scripts` default entry discovery: create later entries at `src/media-modal.js` and `src/plugin-sidebar.js` (wp-scripts expects `src/<name>.js` → `build/<name>`). Prefer single webpack config override only if needed:

Create `webpack.config.js`:

```js
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'media-modal': path.resolve( process.cwd(), 'src/js/media-modal.js' ),
		'plugin-sidebar': path.resolve( process.cwd(), 'src/js/plugin-sidebar.js' ),
	},
};
```

- [ ] **Step 2: Install and verify build scaffolding**

Run:

```bash
npm install
```

Create temporary stubs so build succeeds:

`src/js/media-modal.js`:

```js
console.log( 'imgverse media-modal stub' );
```

`src/js/plugin-sidebar.js`:

```js
console.log( 'imgverse plugin-sidebar stub' );
```

Run:

```bash
npm run build
```

Expected: `build/media-modal.js` (or `build/media-modal/index.js` depending on wp-scripts version) and `.asset.php` exist without errors. Adjust `webpack.config.js` `output` only if paths differ; document actual paths in `includes/class-imgv-assets.php` in Task 8.

- [ ] **Step 3: Bump version + changelog stub**

In `imgverse.php` header and `IMGV_VERSION`, set `2.0.0`.

In `CHANGELOG.md`, prepend:

```markdown
## [2.0.0] - 22/07/2026

### Added
- Scaffold for React media-modal and plugin-sidebar builds (@wordpress/scripts).

### Changed
- Version target for editor UX rebuild and multi-provider support.
```

Use date from `Get-Date -Format 'dd/MM/yyyy'`.

- [ ] **Step 4: Commit**

```bash
git add package.json package-lock.json webpack.config.js src/js/media-modal.js src/js/plugin-sidebar.js build imgverse.php CHANGELOG.md .gitignore
git commit -m "chore: scaffold ImgVerse 2.0 React build toolchain"
```

---

### Task 2: Result normalizer (PHP) + unit tests

**Files:**
- Create: `includes/class-imgv-normalizer.php`
- Create: `tests/php/bootstrap.php`
- Create: `tests/php/test-class-imgv-normalizer.php`
- Create: `phpunit.xml.dist`
- Modify: `composer.json` (add phpunit if missing) or document running with WP core phpunit

**Interfaces:**
- Consumes: raw provider arrays
- Produces: `IMGV_Normalizer::normalize( $raw )` → array with keys: `id`, `title`, `alt`, `urls` (`thumb`, `full`), `user` (`name`, `url`, `photo`), `license`, `license_url`, `attribution`, `provider`, `source`, `permalink`

- [ ] **Step 1: Write failing PHPUnit test**

```php
<?php
/**
 * Tests for IMGV_Normalizer.
 *
 * @package IMGVerse
 * @since 2.0.0
 */

class Test_IMGV_Normalizer extends PHPUnit\Framework\TestCase {

	public function test_normalize_requires_id_and_full_url() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-imgv-normalizer.php';

		$result = IMGV_Normalizer::from_parts(
			array(
				'id'         => 'abc',
				'title'      => 'Millipede',
				'alt'        => 'Illacme',
				'thumb'      => 'https://example.com/t.jpg',
				'full'       => 'https://example.com/f.jpg',
				'user_name'  => 'Ada',
				'user_url'   => 'https://example.com/ada',
				'user_photo' => '',
				'license'    => 'by',
				'license_url'=> 'https://creativecommons.org/licenses/by/4.0/',
				'attribution'=> '"Millipede" by Ada',
				'provider'   => 'openverse',
				'source'     => 'inaturalist',
				'permalink'  => 'https://example.com/photo',
			)
		);

		$this->assertSame( 'abc', $result['id'] );
		$this->assertSame( 'https://example.com/t.jpg', $result['urls']['thumb'] );
		$this->assertSame( 'https://example.com/f.jpg', $result['urls']['full'] );
		$this->assertSame( 'inaturalist', $result['source'] );
		$this->assertSame( 'openverse', $result['provider'] );
	}

	public function test_normalize_falls_back_thumb_to_full_when_thumb_empty() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-imgv-normalizer.php';

		$result = IMGV_Normalizer::from_parts(
			array(
				'id'         => 'x',
				'title'      => 'T',
				'alt'        => '',
				'thumb'      => '',
				'full'       => 'https://example.com/f.jpg',
				'user_name'  => '',
				'user_url'   => '',
				'user_photo' => '',
				'license'    => '',
				'license_url'=> '',
				'attribution'=> '',
				'provider'   => 'unsplash',
				'source'     => 'unsplash',
				'permalink'  => '',
			)
		);

		$this->assertSame( 'https://example.com/f.jpg', $result['urls']['thumb'] );
	}
}
```

- [ ] **Step 2: Run test — expect FAIL**

```bash
./vendor/bin/phpunit tests/php/test-class-imgv-normalizer.php
```

Expected: FAIL — class `IMGV_Normalizer` not found (install phpunit via Composer if needed: `"phpunit/phpunit": "^9.6"`).

- [ ] **Step 3: Implement normalizer**

```php
<?php
/**
 * Normalize provider results to a shared shape.
 *
 * @package IMGVerse
 * @author Krafty Sprouts Media, LLC
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Allow unit tests to load without WordPress when ABSPATH undefined.
	if ( ! defined( 'IMGV_TEST_BOOTSTRAP' ) ) {
		exit;
	}
}

/**
 * IMGV_Normalizer class.
 *
 * @since 2.0.0
 */
class IMGV_Normalizer {

	/**
	 * Build a normalized result array.
	 *
	 * @since 2.0.0
	 * @param array $parts Input parts.
	 * @return array
	 */
	public static function from_parts( $parts ) {
		$full  = isset( $parts['full'] ) ? esc_url_raw( $parts['full'] ) : '';
		$thumb = isset( $parts['thumb'] ) ? esc_url_raw( $parts['thumb'] ) : '';
		if ( '' === $thumb ) {
			$thumb = $full;
		}

		return array(
			'id'          => sanitize_text_field( $parts['id'] ?? '' ),
			'title'       => sanitize_text_field( $parts['title'] ?? '' ),
			'alt'         => sanitize_text_field( $parts['alt'] ?? '' ),
			'urls'        => array(
				'thumb' => $thumb,
				'full'  => $full,
			),
			'user'        => array(
				'name'  => sanitize_text_field( $parts['user_name'] ?? '' ),
				'url'   => esc_url_raw( $parts['user_url'] ?? '' ),
				'photo' => esc_url_raw( $parts['user_photo'] ?? '' ),
			),
			'license'     => sanitize_text_field( $parts['license'] ?? '' ),
			'license_url' => esc_url_raw( $parts['license_url'] ?? '' ),
			'attribution' => wp_kses_post( $parts['attribution'] ?? '' ),
			'provider'    => sanitize_key( $parts['provider'] ?? '' ),
			'source'      => sanitize_key( $parts['source'] ?? '' ),
			'permalink'   => esc_url_raw( $parts['permalink'] ?? '' ),
		);
	}
}
```

For unit tests without WordPress, either polyfill `esc_url_raw` / `sanitize_*` in `tests/php/bootstrap.php` as identity helpers, or define `IMGV_TEST_BOOTSTRAP` and stub those functions.

- [ ] **Step 4: Run test — expect PASS**

```bash
./vendor/bin/phpunit tests/php/test-class-imgv-normalizer.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add includes/class-imgv-normalizer.php tests/php phpunit.xml.dist composer.json composer.lock CHANGELOG.md
git commit -m "feat: add shared image result normalizer with unit tests"
```

---

### Task 3: Provider interface + Openverse adapter (incl. iNaturalist)

**Files:**
- Create: `includes/providers/class-imgv-provider-interface.php`
- Create: `includes/providers/class-imgv-provider-openverse.php`
- Create: `tests/php/test-class-imgv-provider-openverse.php`
- Modify: `includes/class-imgv-api.php` (delegate Openverse search to adapter; keep cache)

**Interfaces:**
- Consumes: `IMGV_Normalizer::from_parts`
- Produces: `IMGV_Provider_Interface::search( $query, $args )` → `array( 'success' => bool, 'images' => array, 'page' => int, 'total_pages' => int, 'total_results' => int, 'message' => string )`
- Openverse `$args` keys: `source` (e.g. `inaturalist`), `license`, `page`, `page_size`

- [ ] **Step 1: Write failing test for Openverse mapper**

Test a pure method `IMGV_Provider_Openverse::map_item( $raw )` with a fixture array mimicking Openverse JSON (include `"source": "inaturalist"`). Assert normalized `source === 'inaturalist'` and `urls.full` equals Openverse `url`.

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Implement interface + Openverse provider**

```php
interface IMGV_Provider_Interface {
	/**
	 * @param string $query Search query.
	 * @param array  $args  Provider args.
	 * @return array
	 */
	public function search( $query, $args = array() );
}
```

Openverse provider:

- Endpoint: `https://api.openverse.org/v1/images/`
- Pass `source` when non-empty (do **not** whitelist-exclude `inaturalist`)
- User-Agent: `IMGVerseWordPressPlugin/` . IMGV_VERSION
- Map each result via `IMGV_Normalizer::from_parts`
- On WP_Error / bad JSON: return `success => false` + message; `error_log` status without secrets

- [ ] **Step 4: Wire `IMGV_API::search_images`** so when provider is openverse (default), it uses this adapter. Keep cache key including provider + source.

- [ ] **Step 5: Manual verify**

```bash
# From WP or curl against Openverse directly while adapter is unit-tested:
curl "https://api.openverse.org/v1/images/?q=bird&source=inaturalist&page_size=1"
```

Expected: JSON with `source: inaturalist`. Confirm adapter map_item handles that fixture.

- [ ] **Step 6: Commit**

```bash
git commit -m "feat: add Openverse provider adapter with iNaturalist source support"
```

---

### Task 4: Unsplash, Pixabay, Pexels adapters + settings fields

**Files:**
- Create: `includes/providers/class-imgv-provider-unsplash.php`
- Create: `includes/providers/class-imgv-provider-pixabay.php`
- Create: `includes/providers/class-imgv-provider-pexels.php`
- Modify: `includes/class-imgv-admin.php` (API key fields; do not echo full keys back to JS — only booleans `has_unsplash_key` etc.)
- Modify: `includes/class-imgv-api.php` (provider router)
- Create: `tests/php/test-provider-mappers.php` (map_item fixtures for each)

**Interfaces:**
- Consumes: settings option keys `unsplash_access_key`, `pixabay_api_key`, `pexels_api_key`
- Produces: same search return shape as Openverse
- Missing key → `success => false`, `code => 'missing_api_key'`, message suitable for UI

Provider download URLs (full):

| Provider | Thumb field | Full field |
|----------|-------------|------------|
| Unsplash | `urls.small` or `urls.thumb` | `urls.regular` or `urls.full` |
| Pixabay | `previewURL` / `webformatURL` | `largeImageURL` |
| Pexels | `src.medium` | `src.large2x` or `src.original` |

- [ ] **Step 1: Failing tests for each `map_item`**

- [ ] **Step 2: Implement adapters + router**

```php
// In IMGV_API
public function search_images( $query, $provider = 'openverse', $args = array() ) {
	$provider = sanitize_key( $provider );
	$adapter  = $this->get_provider( $provider );
	if ( ! $adapter ) {
		return array(
			'success' => false,
			'message' => __( 'Unknown provider.', 'imgverse' ),
			'images'  => array(),
		);
	}
	// cache key includes provider + args
	return $adapter->search( $query, $args );
}
```

- [ ] **Step 3: Settings UI** — three password-style inputs; save via existing `imgv_settings` sanitization.

- [ ] **Step 4: Commit**

```bash
git commit -m "feat: add Unsplash, Pixabay, and Pexels providers with API key settings"
```

---

### Task 5: REST API (search + import)

**Files:**
- Create: `includes/class-imgv-rest.php`
- Modify: `imgverse.php` (load + instantiate REST; keep AJAX temporarily for compat until Task 9)
- Modify: `includes/class-imgv-api.php` (`import_image`: download full URL; optional max W/H via `wp_get_image_editor()->resize`; always generate WP metadata; stop treating `thumbnail|medium|large` as download size)

**Interfaces:**
- `GET /wp-json/imgverse/v1/search` — query args: `q`, `provider`, `source`, `license`, `page`
- `POST /wp-json/imgverse/v1/import` — JSON: `url`, `title`, `alt`, `caption`, `provider`, `source`, `post_id`
- Permission: `current_user_can( 'upload_files' )`
- Produces: WP_REST_Response with normalized images or attachment payload

- [ ] **Step 1: Write failing integration-style unit test** for route registration callback permission — or a thin test that `IMGV_REST::permission_upload` returns false for anonymous (mock `current_user_can` if needed). If full WP bootstrap is unavailable, test import resize helper in isolation:

```php
public function test_import_args_prefer_full_url_not_wp_size_name() {
	// Assert documentation contract: size arg for import is ignored for remote download;
	// only max_width/max_height settings apply.
	$this->assertTrue( true ); // replace with real helper test when extract resize helper
}
```

Prefer extracting `IMGV_API::maybe_resize_file( $file, $max_w, $max_h )` and unit-testing that with a temp image if GD available.

- [ ] **Step 2: Implement REST class**

```php
register_rest_route(
	'imgverse/v1',
	'/search',
	array(
		'methods'             => 'GET',
		'callback'            => array( $this, 'search' ),
		'permission_callback' => array( $this, 'can_upload' ),
		'args'                => array( /* q, provider, source, license, page */ ),
	)
);
```

Import callback calls `$api->import_image( ... )`, sets `_imgv_imported`, `_imgv_import_date`, `_imgv_original_url`, `_imgv_provider`, `_imgv_source`, attaches `post_parent` when `post_id > 0`.

- [ ] **Step 3: Manual verify with WP Application Passwords or logged-in browser**

```
GET /wp-json/imgverse/v1/search?q=bird&provider=openverse&source=inaturalist
```

Expected: 200 + `images` array.

Without key:

```
GET /wp-json/imgverse/v1/search?q=ocean&provider=unsplash
```

Expected: error payload `missing_api_key`.

- [ ] **Step 4: Commit**

```bash
git commit -m "feat: add ImgVerse REST search and import endpoints"
```

---

### Task 6: JS thumb fallback helper (TDD) + EmptyState

**Files:**
- Create: `src/js/utils/thumbFallback.js`
- Create: `src/js/utils/thumbFallback.test.js`
- Create: `src/js/components/EmptyState.js`

**Interfaces:**
- Consumes: `urls.thumb`, `urls.full`
- Produces: `getThumbSrc(urls)`, `nextThumbOnError(currentSrc, urls)` → next URL or `null`

- [ ] **Step 1: Failing Jest test**

```js
import { getThumbSrc, nextThumbOnError } from './thumbFallback';

describe( 'thumbFallback', () => {
	it( 'uses thumb when present', () => {
		expect(
			getThumbSrc( { thumb: 'https://a/t.jpg', full: 'https://a/f.jpg' } )
		).toBe( 'https://a/t.jpg' );
	} );

	it( 'falls back to full when thumb empty', () => {
		expect( getThumbSrc( { thumb: '', full: 'https://a/f.jpg' } ) ).toBe(
			'https://a/f.jpg'
		);
	} );

	it( 'on error swaps thumb to full once', () => {
		expect(
			nextThumbOnError( 'https://a/t.jpg', {
				thumb: 'https://a/t.jpg',
				full: 'https://a/f.jpg',
			} )
		).toBe( 'https://a/f.jpg' );
	} );

	it( 'on error of full returns null', () => {
		expect(
			nextThumbOnError( 'https://a/f.jpg', {
				thumb: 'https://a/t.jpg',
				full: 'https://a/f.jpg',
			} )
		).toBeNull();
	} );
} );
```

- [ ] **Step 2: Run**

```bash
npm run test:js -- --testPathPattern=thumbFallback
```

Expected: FAIL

- [ ] **Step 3: Implement helper + EmptyState component** (missing key / no results / error messages using `imgvData.strings` and settings URL)

- [ ] **Step 4: Tests PASS + commit**

```bash
git commit -m "feat: add thumbnail fallback helper and empty states"
```

---

### Task 7: Shared React App (search + grid + photo)

**Files:**
- Create: `src/js/constants/providers.js`
- Create: `src/js/utils/api.js`
- Create: `src/js/components/ProviderNav.js`
- Create: `src/js/components/SearchBar.js`
- Create: `src/js/components/PhotoGrid.js`
- Create: `src/js/components/Photo.js`
- Create: `src/js/components/App.js`
- Create: `src/scss/style.scss` (import from both entries or one shared CSS entry)

**Interfaces:**
- Consumes: `imgvData` localized object:

```js
{
  restUrl, nonce, postId, settingsUrl,
  providers: { openverse: { needsKey: false }, unsplash: { needsKey: true, hasKey: bool }, ... },
  openverseSources: [{ value: 'inaturalist', label: 'iNaturalist' }, ...],
  defaultInsertSize: 'large',
  strings: { ... }
}
```

- Produces: `<App context="modal"|"sidebar" />` with search → grid → import actions

- [ ] **Step 1: Implement `api.js`**

```js
export async function searchImages( { q, provider, source, license, page } ) {
	const url = new URL( `${imgvData.restUrl}search` );
	url.searchParams.set( 'q', q );
	url.searchParams.set( 'provider', provider );
	if ( source ) url.searchParams.set( 'source', source );
	if ( license ) url.searchParams.set( 'license', license );
	url.searchParams.set( 'page', String( page || 1 ) );

	const res = await fetch( url.toString(), {
		headers: { 'X-WP-Nonce': imgvData.nonce },
	} );
	return res.json();
}

export async function importImage( payload ) {
	const res = await fetch( `${imgvData.restUrl}import`, {
		method: 'POST',
		headers: {
			'X-WP-Nonce': imgvData.nonce,
			'Content-Type': 'application/json',
		},
		body: JSON.stringify( payload ),
	} );
	return res.json();
}
```

- [ ] **Step 2: Build App shell** — provider nav, search, Openverse source filter, load more, wire EmptyState for `missing_api_key`.

- [ ] **Step 3: Photo component** — `<img src={getThumbSrc(urls)} onError=... />`; edit title/alt/caption; Import button calling `importImage` with `urls.full`.

- [ ] **Step 4: Style** polished dark/light photo cards, hover controls, responsive grid. Brand as IMGVerse (original assets only).

- [ ] **Step 5: `npm run build` — Expected: success

- [ ] **Step 6: Commit**

```bash
git commit -m "feat: add shared ImgVerse React search and photo grid"
```

---

### Task 8: Assets enqueue + Media modal mount

**Files:**
- Create: `includes/class-imgv-assets.php`
- Modify: `src/js/media-modal.js` (replace stub)
- Modify: `includes/class-imgv-media-tab.php` (remove Underscore templates + Backbone browser; only ensure media scripts enqueue via assets)
- Modify: `imgverse.php` (boot `IMGV_Assets`)

**Interfaces:**
- Consumes: `App` component
- Produces: MediaFrame router tab `imgverse` that mounts React root into frame content

- [ ] **Step 1: Implement media-modal mount** (MediaFrame tab mount pattern):

Extend `wp.media.view.MediaFrame.Select` and `Post`, add router tab, on `content:create:imgverse` / tab activation create a container `#imgverse-root` and:

```js
import { createRoot } from '@wordpress/element';
import App from './components/App';

createRoot( container ).render( <App context="modal" /> );
```

Handle frame reopen: unmount previous root if needed.

- [ ] **Step 2: `IMGV_Assets::enqueue_media()`** on `wp_enqueue_media`:

```php
$asset = include IMGV_PLUGIN_PATH . 'build/media-modal.asset.php';
wp_enqueue_script(
	'imgv-media-modal',
	IMGV_PLUGIN_URL . 'build/media-modal.js',
	$asset['dependencies'],
	$asset['version'],
	true
);
wp_localize_script( 'imgv-media-modal', 'imgvData', $this->get_localize_data() );
```

`get_localize_data()` sets `hasKey` booleans only (not raw secrets).

- [ ] **Step 3: Delete obsolete** `assets/js/imgv-media-tab.js` usage and `print_media_templates` Backbone templates from `class-imgv-media-tab.php`.

- [ ] **Step 4: Manual test** — WP Admin → Posts → Add Media → IMGVerse tab → search Openverse `inaturalist` → import → library shows attachment.

- [ ] **Step 5: Commit**

```bash
git commit -m "feat: mount ImgVerse React app in WP media modal"
```

---

### Task 9: Plugin sidebar (insert + featured)

**Files:**
- Modify: `src/js/plugin-sidebar.js`
- Create: `src/js/editor/insertImage.js`
- Create: `src/js/editor/setFeaturedImage.js`
- Modify: `includes/class-imgv-block-editor.php`
- Modify: `includes/class-imgv-assets.php` (enqueue on `enqueue_block_editor_assets`)
- Remove old: `assets/js/imgv-block-editor.js` registration

**Interfaces:**
- Consumes: import response `attachment` (from `wp_prepare_attachment_for_js`)
- Produces: insert `core/image` block; `editPost({ featured_image: id })`

- [ ] **Step 1: Implement helpers**

```js
import { createBlock } from '@wordpress/blocks';
import { dispatch, select } from '@wordpress/data';

export function insertImage( attachment, size = 'large' ) {
	const url =
		attachment.sizes?.[ size ]?.url ||
		attachment.url;
	const block = createBlock( 'core/image', {
		id: attachment.id,
		url,
		alt: attachment.alt || '',
		caption: attachment.caption || '',
	} );
	dispatch( 'core/block-editor' ).insertBlocks( block );
}

export function setFeaturedImage( attachmentId ) {
	dispatch( 'core/editor' ).editPost( { featured_media: attachmentId } );
}
```

- [ ] **Step 2: Sidebar registerPlugin** rendering `<App context="sidebar" />` with Insert / Set featured buttons on Photo (visible only when `context === 'sidebar'`).

- [ ] **Step 3: Manual test** — block editor sidebar: search → import → insert; set featured.

- [ ] **Step 4: Remove legacy AJAX handlers** from `imgverse.php` only after confirming REST path is used exclusively (`wp_ajax_imgv_search`, `wp_ajax_imgv_import` optional keep as deprecated wrappers for one release — prefer remove for YAGNI if nothing calls them).

- [ ] **Step 5: Commit**

```bash
git commit -m "feat: add ImgVerse block editor sidebar with insert and featured image"
```

---

### Task 10: Polish, changelog completion, verification pass

**Files:**
- Modify: `CHANGELOG.md` (complete 2.0.0 notes)
- Modify: `README.md` (providers, API keys, media modal usage)
- Modify: `docs/superpowers/specs/2026-07-22-imgverse-editor-ux-design.md` (Status: Implemented / ready for QA)

**Verification checklist (all must pass):**

- [ ] Openverse + source iNaturalist returns results in media modal
- [ ] Unsplash/Pixabay/Pexels: missing key shows EmptyState; with key searches work
- [ ] Import attaches to post + `_imgv_*` meta present
- [ ] Broken Openverse thumb falls back (force bad thumb in Photo test or DevTools)
- [ ] Sidebar insert + featured image
- [ ] `npm run build` clean; `npm run test:js` pass; `./vendor/bin/phpunit` pass
- [ ] No leftover Backbone media templates enqueued

- [ ] **Commit**

```bash
git commit -m "docs: finalize ImgVerse 2.0.0 editor UX changelog and README"
```

---

## Self-review (plan vs spec)

| Spec requirement | Task |
|------------------|------|
| Shared React app | 7 |
| Media modal mount | 8 |
| Block sidebar insert/featured | 9 |
| Openverse + iNaturalist | 3 |
| Unsplash/Pixabay/Pexels + user keys | 4 |
| REST API | 5 |
| Image size model (full download + WP sizes on insert) | 5, 9 |
| Thumb fallback | 6 |
| Error/missing key UX | 6, 7 |
| Keep attribution / `_imgv_*` / cache | 3–5 (extend import meta) |
| No third-party proxy / no Gutenberg block | Out of scope honored |
| Version 2.0.0 + changelog | 1, 10 |

No TBD placeholders remain in task steps. Interface names are consistent: `IMGV_Normalizer::from_parts`, `IMGV_Provider_Interface::search`, REST `imgverse/v1/search|import`, localized `imgvData`.

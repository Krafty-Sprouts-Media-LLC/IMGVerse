# IMGVerse - WordPress Multi-Provider Image Plugin

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-2.1.9-orange.svg)](https://github.com/Krafty-Sprouts-Media-LLC/IMGVerse)

**IMGVerse** lets you search and import images from Openverse (including iNaturalist), Unsplash, Pixabay, and Pexels into the WordPress media library—with attribution, import tracking, and a shared React UI in the media modal and block editor sidebar.

## Key Features

### Multi-provider search
- **Openverse** — no API key; filter by source (Flickr, Wikimedia Commons, **iNaturalist**, Met, NYPL, Rawpixel, Smithsonian, or all)
- **Unsplash**, **Pixabay**, **Pexels** — use your own API keys from **Settings → IMGVerse**
- Missing keys show an in-UI empty state with a link to Settings (keys stay server-side; the editor only gets `hasKey` booleans)

### Editor surfaces (2.0)
- **Media modal** — Add Media → **IMGVerse** tab (React mount into `MediaFrame`, responsive photo grid)
- **Block editor sidebar** — plugin sidebar with the same search UI; **Insert** image into the post or **Set featured image** after import
- Shared React app: provider nav, search, Openverse source filter, photo grid, thumbnail fallback, infinite scroll

### Import & WordPress integration
- Full/large download, optional max W/H resize, then WordPress generates registered sizes
- Attachments set `post_parent` when a post ID is available
- Meta: `_imgv_imported`, `_imgv_import_date`, `_imgv_original_url`, `_imgv_provider`, `_imgv_source`
- Attribution templates and caching from earlier IMGVerse releases

### Performance
- Intelligent caching (Redis / Memcached / object cache / DB transients)
- Rate limiting and database size protection

## Installation

### WordPress Admin
1. Go to **Plugins → Add New**
2. Search for "IMGVerse"
3. Click **Install Now** and **Activate**

### Manual Installation
1. Upload a production zip via **Plugins → Add New → Upload Plugin**, or unzip into `/wp-content/plugins/imgverse/`
2. Activate through the **Plugins** menu
3. For development: `npm install` then `npm run build`

**Production zip rules:** the archive’s top-level folder must be exactly `imgverse` (never `imgverse-x.y.z`), and entry paths must use forward slashes. See [docs/release-packaging.md](docs/release-packaging.md).

### Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- Internet connection for provider APIs

## Usage

### API keys (Settings)
1. Go to **Settings → IMGVerse**
2. Under **Provider API Keys**, add Unsplash Access Key, Pixabay API Key, and/or Pexels API Key as needed
3. Openverse needs no key
4. Optionally set default insert size and max download width/height

### Media modal
1. In a post or page, click **Add Media**
2. Open the **IMGVerse** tab
3. Choose a provider; for Openverse optionally set source (e.g. iNaturalist)
4. Search, browse the grid, import into the media library, then insert via the normal WordPress media flow

### Block editor sidebar
1. Open the **IMGVerse** plugin sidebar in the block editor
2. Search and import as in the media modal
3. Use **Insert** to add an image block (chosen WP size) or **Set featured image**

## Configuration

### Search & display
- Default search behavior, results per page, infinite scroll, grid columns

### Attribution
- Template variables: `{title}`, `{creator}`, `{source}`, `{license}`, `{license_url}`, `{url}`
- Styles, placement (caption / description / custom field), link behavior

### Import
- Default WordPress insert size (`thumbnail` / `medium` / `large` / `full`)
- Max download width/height (default 2400×2400; `0` disables resize)
- Quality, file naming, duplicate handling

### Performance
- Cache duration/strategy, timeouts, rate limits

## REST API

Namespace: `imgverse/v1` (capability: `upload_files`)

| Method | Route | Purpose |
|--------|--------|---------|
| `GET` | `/wp-json/imgverse/v1/search` | Search (`provider`, `query`, Openverse `source` / `license`, pagination) |
| `POST` | `/wp-json/imgverse/v1/import` | Import remote image (URL, meta, `post_id`, provider) |

Legacy AJAX handlers remain for compatibility.

## Architecture (2.0)

```
imgverse/
├── imgverse.php
├── includes/
│   ├── class-imgv-assets.php      # Enqueues build/media-modal + plugin-sidebar
│   ├── class-imgv-rest.php        # REST search / import
│   ├── class-imgv-api.php         # Import pipeline + provider routing
│   ├── class-imgv-admin.php       # Settings (incl. API keys)
│   ├── providers/                 # Openverse, Unsplash, Pixabay, Pexels
│   └── …
├── src/js/                        # React App, media-modal + plugin-sidebar mounts
├── build/                         # @wordpress/scripts output
├── assets/js/imgv-admin.js        # Settings page only
└── assets/css/imgv-admin.css
```

## Development

```bash
npm install
npm run build
npm run test:js
./vendor/bin/phpunit -c phpunit.xml.dist
```

- WordPress PHP Coding Standards
- ESLint via `@wordpress/scripts` for `src/`
- Shipping a test/production zip: [docs/release-packaging.md](docs/release-packaging.md)

## Security

- Input sanitization, REST/AJAX capability checks, nonces
- Provider API keys stored in options; used only on the server
- Client localization never includes raw keys

## License

GPL v2 or later. See the GNU General Public License for details.

## Support

- Documentation: [kraftysprouts.com/imgverse/docs](https://kraftysprouts.com/imgverse/docs)
- WordPress Support: [wordpress.org/support/plugin/imgverse](https://wordpress.org/support/plugin/imgverse)
- GitHub Issues: [github.com/kraftysprouts/imgverse/issues](https://github.com/kraftysprouts/imgverse/issues)

## Credits

- Openverse API — [api.openverse.org](https://api.openverse.org/)
- Unsplash, Pixabay, and Pexels APIs (user-supplied keys)
- WordPress community and contributors

---

**Made by [Krafty Sprouts Media, LLC](https://kraftysprouts.com)**

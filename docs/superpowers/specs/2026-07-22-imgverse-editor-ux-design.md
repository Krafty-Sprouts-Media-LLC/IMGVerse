# IMGVerse — Editor UX Rebuild & Multi-Provider Design Spec

**Date:** 2026-07-22  
**Status:** Implemented / ready for QA  
**Version target:** **2.0.0**  
**Approach:** Shared React app (shared React app pattern), keep IMGVerse Openverse backend

## Purpose

Bring IMGVerse’s media-modal and block-editor experience to a modern stock-image UX quality bar while keeping IMGVerse’s Openverse strengths (including iNaturalist) and adding Unsplash, Pixabay, and Pexels.

## Confirmed decisions

1. **Strategy:** Rebuild IMGVerse editor UX (Approach 1). Do **not** soft-fork a third-party plugin as the long-term base.
2. **Scope (v1):** Media modal + block editor sidebar. Dedicated Gutenberg block deferred.
3. **Providers:** Openverse (incl. iNaturalist and existing Openverse sources) + Unsplash + Pixabay + Pexels.
4. **API keys:** Unsplash / Pixabay / Pexels use **user-supplied keys** in settings. Openverse needs no key. third-party proxy is not used.
5. **Image sizes:** Download provider large/full once; optional max W/H resize; WordPress generates registered sizes; per-image UI chooses WP size only when inserting into a post.
6. **Keep from IMGVerse:** Openverse source list (iNaturalist, Wikimedia, etc.), caching, attribution templates, import tracking (`_imgv_*`), post attachment.
7. **Replace:** Backbone/jQuery media tab UI and separate sidebar UI with one shared React core and thin mounts.

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│  IMGVerse React App (search / grid / import / insert)   │
│  Providers: Openverse | Unsplash | Pixabay | Pexels     │
└───────────────┬─────────────────────┬───────────────────┘
                │                     │
     MediaFrame tab mount      Block editor sidebar mount
                │                     │
                └──────────┬──────────┘
                           ▼
              IMGVerse PHP REST API
                           │
        ┌──────────────────┼──────────────────┐
        ▼                  ▼                  ▼
   Openverse API    Unsplash/Pixabay/   Local import +
   (iNaturalist,    Pexels (user API    attribution +
    Wikimedia…)     keys in settings)   media library
```

### Components

| Piece | Role |
|-------|------|
| Shared React app | Provider nav, search, filters, photo grid, load more, photo actions |
| Media modal mount | Extend `MediaFrame.Select` / `Post`; mount React into IMGVerse tab |
| Sidebar mount | Plugin sidebar; insert into post / set featured image |
| PHP provider adapters | Normalize Openverse / Unsplash / Pixabay / Pexels → common result shape |
| Import pipeline | Download, optional resize, sideload, attribution, `_imgv_*` meta, `post_parent` |
| Settings | API keys, default insert size, max download dimensions |
| Cache | Existing IMGVerse cache for search responses |

### Build

- `@wordpress/scripts` produces `media-modal` and `plugin-sidebar` (and shared) bundles.
- SCSS/CSS for polished photo grid and controls under IMGVerse branding.

## Surfaces

### Media modal (primary)

- Add Media → **IMGVerse** tab (media-modal `MediaFrame` integration).
- Full shared UI: providers, search, filters, grid, load more.
- Photo actions: import; edit title/alt/caption before import; then select/insert via WP media flow.
- After import: refresh media library so the attachment can be inserted normally.

### Block editor sidebar

- Same React app, narrower layout.
- Actions: **Insert into post**, **Set featured image**.
- Insert uses chosen WordPress size (`thumbnail` / `medium` / `large` / `full`).

### Deferred

- Dedicated Gutenberg block (reuse same app later).

### Provider UI

- Switcher: Openverse · Unsplash · Pixabay · Pexels.
- Openverse: source filter includes iNaturalist and other IMGVerse Openverse sources.
- Missing key for Unsplash/Pixabay/Pexels: clear in-UI notice + link to Settings (no silent empty grid).

## Image sizes

| Layer | Behavior |
|-------|----------|
| Download | Provider large/full URL by default |
| Optional resize | Max width/height setting before attach (optional max dimensions model) |
| After import | WordPress generates all registered sizes |
| Insert UI | Choose WP size for the block (`thumbnail` / `medium` / `large` / `full`) |
| Settings | Default insert size + max download dimensions |
| Previews | Provider thumb; Openverse thumb failure → fall back to full (no black broken tiles) |

Do **not** map “download as thumbnail/medium” to WordPress size names against remote URLs.

## Data flow

### Search

1. UI sends provider + query + filters to IMGVerse REST (nonce + capabilities).
2. Provider adapter calls Openverse or Unsplash/Pixabay/Pexels (server adds API keys).
3. Response normalized to:

```text
{ id, title, alt, urls: { thumb, full }, user, license, attribution, provider, source }
```

4. Cache → React grid.
5. Optional short-lived client session cache per provider while the modal stays open.

### Import

1. User: import / insert / set featured.
2. POST import (URL, meta, `post_id`, provider).
3. Download full/large → optional max W/H resize → sideload.
4. Attribution + `_imgv_*` meta + post attachment.
5. Return attachment id + sizes.
6. UI success → media selection / insert block / set featured.

### Settings security

- API keys stored in options; used only server-side on outbound provider requests.
- Do not expose full keys to the browser when avoidable.

## Error handling

| Case | Behavior |
|------|----------|
| Missing API key | In-UI notice + Settings link |
| Provider/API failure | User-facing message; server log status/body (no keys) |
| Rate limit | Clear retry-later message; no aggressive retries |
| Empty results | Explicit empty state |
| Broken thumbnail | `onError` → try `urls.full` once → placeholder card |
| Import/download/sideload fail | Notice; photo returns to idle; retry possible |
| Permissions | Require `upload_files` (and edit-post where inserting); 403 otherwise |

## Out of scope (this version)

- third-party proxy usage
- Soft-fork of a third-party plugin as product base
- Dedicated Gutenberg block
- Giphy or other stock providers beyond Unsplash / Pixabay / Pexels / Openverse
- Building a public IMGVerse API proxy for default keys

## Success criteria

1. Add Media → IMGVerse tab feels high-quality for search, grid, and import.
2. Openverse + **iNaturalist** return usable results (no hard source whitelist excluding them).
3. Unsplash / Pixabay / Pexels work with keys configured; blocked cleanly without keys.
4. Sidebar can insert into post and set featured image after import.
5. Broken Openverse thumbs fall back instead of black tiles.
6. One shared React core powers modal and sidebar (no duplicated Backbone vs sidebar logic).

## Testing / verification

- Openverse search with source = iNaturalist in media modal.
- Unsplash / Pixabay / Pexels search with and without keys.
- Import → library → insert from media modal.
- Sidebar: insert image block + set featured image.
- Force broken Openverse thumb URL → fallback renders.
- Import attaches to current post and writes `_imgv_*` meta.

## Versioning notes

- Target plugin version **2.0.0** (editor UX + multi-provider is a major jump from 1.6.0).
- New APIs / files use `@since 2.0.0`; do not rewrite existing `@since` tags.
- Changelog updated on implementation per project rules.

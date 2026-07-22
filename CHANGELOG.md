# Changelog

All notable changes to IMGVerse will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 22/07/2026

### Added
- `IMGV_Assets` enqueues `build/media-modal.js` / `build/style-media-modal.css` on `wp_enqueue_media`, localizes `imgvData` (hasKey booleans only, never raw API keys), and mounts the React App into MediaFrame.Select/Post tab `imgverse` (`#imgverse-root`).
- Shared React App (`App`, `ProviderNav`, `SearchBar`, `PhotoGrid`, `Photo`) with REST search/import client, Openverse source filter (incl. iNaturalist), thumb fallback, and Instant Images–like grid styles under IMGVerse branding.
- REST API namespace `imgverse/v1` with `GET /search` and `POST /import` (permission: `upload_files`); AJAX handlers kept for compatibility.
- `IMGV_API::maybe_resize_file()` for optional max download dimensions (`max_download_width` / `max_download_height`, default 2400×2400, 0 disables) with GD-backed unit tests.
- Import meta `_imgv_provider` and `_imgv_source` alongside existing `_imgv_imported`, `_imgv_import_date`, `_imgv_original_url`.
- Unsplash, Pixabay, and Pexels provider adapters with API key settings and `map_item` unit fixtures.
- Openverse provider adapter (`IMGV_Provider_Openverse`) with iNaturalist source support and `map_item` unit coverage.
- Provider search interface (`IMGV_Provider_Interface`) for multi-provider adapters.
- Shared `IMGV_Normalizer::from_parts()` for provider image results and PHPUnit coverage.
- Scaffold for React media-modal and plugin-sidebar builds (@wordpress/scripts).
- JS `getThumbSrc` / `nextThumbOnError` thumbnail fallback helpers with Jest coverage.
- Shared React `EmptyState` component for missing API key, no results, and error states.
- Explicit `jsdom` devDependency so Jest (`test:js`) can resolve the jsdom test environment.

### Changed
- Replaced Backbone/Underscore media modal tab (`assets/js/imgv-media-tab.js` + `print_media_templates`) with the React MediaFrame mount.
- `IMGV_API::import_image()` always downloads the full remote URL (no thumbnail/medium/large remote size); optional local resize from settings; import meta always written; `post_parent` set when `post_id > 0`.
- `IMGV_API::search_images( $query, $provider, $args )` routes to Openverse/Unsplash/Pixabay/Pexels adapters; AJAX keeps Openverse `source`/`license` via args; cache keys include provider + source.
- Version target for editor UX rebuild and multi-provider support.

## [1.6.0] - 2025-10-24

### Added
- **Post Attachment System**: Imported images are now properly attached to the post they were imported from
- **Import Tracking**: Custom meta fields (`_imgv_imported`, `_imgv_import_date`, `_imgv_original_url`) track import source and details
- **Post Image Queries**: Helper function `get_post_images()` to retrieve images attached to specific posts
- **Import Statistics**: Function `get_import_stats()` to track total and recent imports for analytics
- **Enhanced AJAX**: Both media modal and block editor now pass post ID for proper attachment
- **WordPress Integration**: Uses native `post_parent` relationship for proper WordPress attachment handling

### Changed
- **Import Function**: Enhanced `import_image()` method to accept optional `$post_id` parameter
- **JavaScript Integration**: Updated both media tab and block editor JavaScript to send post ID
- **Admin Interface**: Added helper functions for post-specific image management

### Technical Improvements
- **Database Relationships**: Proper parent-child relationships between posts and imported images
- **Meta Tracking**: Comprehensive tracking of import metadata for analytics and debugging
- **Query Optimization**: Efficient queries to find post-specific imported images
- **WordPress Standards**: Follows WordPress best practices for attachment handling

## [1.5.0] - 2025-10-24

### Added
- Initial release of IMGVerse WordPress plugin
- **Post Attachment**: Imported images are now properly attached to the post they were imported from
- **Import Tracking**: Custom meta fields track import source, date, and original URL
- **Post Image Queries**: Helper functions to get images attached to specific posts
- **Import Statistics**: Track total and recent imports for analytics
- Unified search across all Openverse sources (Flickr, Wikimedia Commons, iNaturalist, Metropolitan Museum, NYPL, Rawpixel, Smithsonian)
- Professional media modal integration with IMGVerse tab
- Block editor sidebar panel for Gutenberg editor
- Intelligent multi-level caching system with Redis/Memcached support
- Flexible attribution management with customizable templates
- Image size selection during import (thumbnail, medium, large, full)
- Infinite scroll and pagination options for seamless browsing
- Responsive design for desktop, tablet, and mobile devices
- Comprehensive admin settings page with performance monitoring
- Real-time search with debounced input
- Image preview modal with full-size viewing
- Bulk import capabilities with common settings
- Search history and favorites functionality
- Advanced filtering by source and license
- Performance optimization with background processing
- Database protection with size limits and smart purging
- Rate limiting and request queuing
- Error handling with retry mechanisms
- Security features including input sanitization and nonce verification
- Developer-friendly architecture with hooks and filters
- WordPress coding standards compliance
- Internationalization support
- Uninstall cleanup functionality

### Technical Features
- **Caching Strategy**: Auto-detects Redis, Memcached, WordPress object cache, or database caching
- **Performance Monitoring**: Cache hit rates, database load, response times
- **Memory Management**: Proper garbage collection and memory limit checks
- **Database Optimization**: Efficient queries, proper indexing, batch operations
- **API Integration**: Clean API layer with error handling and retry logic
- **File Structure**: Organized plugin architecture with proper separation of concerns
- **Security**: Comprehensive input validation, capability checks, and sanitization
- **Accessibility**: ARIA labels, keyboard navigation, focus management
- **Mobile Support**: Touch-friendly interface with responsive breakpoints

### Admin Interface
- **Search & Display Settings**: Default behavior, results per page, grid layout
- **Attribution Settings**: Template system, placement options, link behavior
- **Import Settings**: Default sizes, quality, file naming, duplicate handling
- **Performance Settings**: Cache configuration, rate limiting, background processing
- **Analytics Dashboard**: Usage statistics, cache performance, popular searches

### Developer Features
- **Hooks and Filters**: `imgv_register_source`, `imgv_source_query`, `imgv_source_response`
- **Custom Source Integration**: Admin interface for adding new image sources
- **API Extensibility**: Base source class for easy extension
- **Plugin Compatibility**: Hooks for other plugins to integrate
- **Code Quality**: WordPress standards, comprehensive documentation, error handling

### Security & Performance
- **Input Sanitization**: All user inputs properly sanitized
- **API Response Validation**: External data validated before processing
- **Nonce Verification**: AJAX requests protected with nonces
- **Capability Checks**: Proper permission verification for all actions
- **File Upload Security**: Image type validation and malicious content scanning
- **Cache Security**: Secure cache key generation and data encryption
- **Rate Limiting**: Built-in protection against API abuse
- **Memory Protection**: Memory usage monitoring and garbage collection
- **Database Protection**: Connection limits and query optimization

### User Experience
- **Intuitive Interface**: Clean, modern design following WordPress standards
- **Loading States**: Clear feedback during search and import operations
- **Error Messages**: User-friendly error messages with retry options
- **Success Notifications**: Toast notifications for successful operations
- **Keyboard Navigation**: Full keyboard support for accessibility
- **Mobile Responsive**: Optimized for all device sizes
- **Dark Mode**: Compatible with WordPress dark mode themes

### Documentation
- **WordPress Repository**: Complete readme.txt with screenshots and FAQ
- **Developer Documentation**: Comprehensive README with code examples
- **Installation Guide**: Step-by-step setup instructions
- **Configuration Guide**: Detailed settings explanation
- **Troubleshooting**: Common issues and solutions
- **API Documentation**: Developer hooks and filters reference

## [Unreleased]

### Planned Features
- Advanced image filters (color, orientation, size)
- Bulk import with progress tracking
- Custom source API integration
- AI-powered image suggestions
- Advanced search operators
- Custom image collections
- Export/import settings
- Multi-language support
- Image comparison tools
- Advanced attribution options

### Performance Improvements
- Image preloading optimization
- Lazy loading enhancements
- Cache compression
- Database query optimization
- Memory usage improvements
- API response caching
- Background task optimization

### Security Enhancements
- Enhanced input validation
- API rate limiting improvements
- Cache security hardening
- File upload validation
- XSS protection enhancements
- CSRF protection improvements

---

**Note**: This changelog follows the format specified in [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

=== IMGVerse ===
Contributors: kraftysprouts
Tags: images, creative commons, openverse, media, import, search, gutenberg, block editor
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Search and import Creative Commons images from Openverse directly into your WordPress posts and pages.

== Description ==

IMGVerse is a powerful WordPress plugin that integrates with the Openverse API to provide seamless access to millions of Creative Commons images. Search, preview, and import images directly into your WordPress media library with proper attribution.

= Key Features =

* **Unified Search**: Search across all Openverse sources (Flickr, Wikimedia Commons, iNaturalist, Metropolitan Museum, NYPL, Rawpixel, Smithsonian) with a single query
* **Media Modal Integration**: Professional media tab integration that works like Instant Images
* **Block Editor Support**: Sidebar panel for Gutenberg editor with direct image block insertion
* **Smart Caching**: Multi-level caching system with Redis/Memcached support for optimal performance
* **Flexible Attribution**: Customizable attribution templates with multiple styles
* **Image Size Management**: Choose from thumbnail, medium, large, or full size during import
* **Infinite Scroll**: Seamless browsing with infinite scroll or pagination options
* **Responsive Design**: Works perfectly on desktop, tablet, and mobile devices
* **Performance Optimized**: Intelligent caching, rate limiting, and background processing

= How It Works =

1. **Search**: Enter your search query in the IMGVerse tab in the media modal or block editor sidebar
2. **Browse**: View results in a beautiful grid layout with hover overlays showing image details
3. **Preview**: Click the preview button to see full-size images with attribution preview
4. **Import**: Select your preferred image size and import directly to your media library
5. **Use**: Imported images are ready to use in your posts and pages with proper attribution

= Attribution Management =

* **Template System**: Create custom attribution templates using variables like {title}, {creator}, {source}, {license}
* **Multiple Styles**: Choose from Simple, Standard, Academic, or Custom attribution formats
* **Flexible Placement**: Attribution can be placed in captions, descriptions, or custom fields
* **Link Options**: Link to source, creator, license, or no links as needed

= Performance Features =

* **Intelligent Caching**: Auto-detects and uses Redis, Memcached, WordPress object cache, or database caching
* **Server Compatibility**: Works seamlessly with existing server caching systems
* **Database Protection**: Prevents database overload with size limits and smart purging
* **Rate Limiting**: Built-in protection against API rate limits
* **Background Processing**: Heavy operations run in the background to avoid timeouts

= Admin Settings =

* **Search & Display**: Configure default search behavior, results per page, and grid layout
* **Attribution**: Set up custom attribution templates and placement options
* **Import**: Choose default image sizes, quality settings, and file naming
* **Performance**: Monitor cache statistics, configure caching strategy, and optimize performance
* **Analytics**: Track usage statistics and popular searches

= Developer Friendly =

* **WordPress Standards**: Follows WordPress coding standards and best practices
* **Extensible**: Hooks and filters for custom development
* **API Integration**: Clean API layer for easy customization
* **Error Handling**: Comprehensive error handling and user feedback
* **Security**: Proper input sanitization, nonce verification, and capability checks

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/imgverse` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > IMGVerse to configure your preferences
4. Start searching for Creative Commons images in the media modal or block editor sidebar

== Frequently Asked Questions ==

= Does this plugin work with the Classic Editor? =

Yes! IMGVerse works with both the Classic Editor and the new Block Editor (Gutenberg). In the Classic Editor, you can access IMGVerse through the media modal. In the Block Editor, you can use the sidebar panel.

= What image sources are available? =

IMGVerse searches across all Openverse sources including Flickr, Wikimedia Commons, iNaturalist, Metropolitan Museum, NYPL, Rawpixel, and Smithsonian.

= How does attribution work? =

The plugin automatically generates proper attribution for all imported images. You can customize the attribution format in the plugin settings, and attribution is automatically added to image captions or descriptions.

= Is this plugin performance optimized? =

Yes! IMGVerse includes intelligent caching, rate limiting, and background processing to ensure optimal performance. It works seamlessly with existing server caching systems like Redis and Memcached.

= Can I customize the search behavior? =

Absolutely! You can configure default search behavior, results per page, grid layout, attribution templates, and much more in the plugin settings.

= Does this plugin work on mobile devices? =

Yes! IMGVerse is fully responsive and works perfectly on desktop, tablet, and mobile devices.

== Screenshots ==

1. Media modal with IMGVerse tab showing search interface
2. Search results in grid layout with image details
3. Image preview modal with attribution information
4. Block editor sidebar with search and import functionality
5. Admin settings page with comprehensive configuration options
6. Cache statistics and performance monitoring

== Changelog ==

= 1.6.0 =
* **NEW**: Post Attachment System - Imported images are now properly attached to the post they were imported from
* **NEW**: Import Tracking - Custom meta fields track import source, date, and original URL
* **NEW**: Post Image Queries - Helper functions to get images attached to specific posts
* **NEW**: Import Statistics - Track total and recent imports for analytics
* **ENHANCED**: AJAX Integration - Both media modal and block editor now pass post ID for proper attachment
* **IMPROVED**: WordPress Integration - Uses native post_parent relationship for proper WordPress attachment handling

= 1.5.0 =
* Initial release
* Unified search across all Openverse sources
* Media modal integration with professional interface
* Block editor sidebar support
* Intelligent multi-level caching system
* Flexible attribution management
* Image size selection during import
* Infinite scroll and pagination options
* Responsive design for all devices
* Comprehensive admin settings
* Performance optimization and monitoring
* Developer-friendly architecture

== Upgrade Notice ==

= 1.3.0 =
Initial release of IMGVerse - the ultimate Creative Commons image search and import plugin for WordPress.

== Support ==

For support, feature requests, or bug reports, please visit our [support page](https://kraftysprouts.com/support) or create an issue on our [GitHub repository](https://github.com/kraftysprouts/imgverse).

== Privacy Policy ==

IMGVerse does not collect, store, or transmit any personal data. All image searches are performed through the Openverse API, and imported images are stored in your WordPress media library following standard WordPress practices.

== Credits ==

This plugin integrates with the [Openverse API](https://api.openverse.org/) to provide access to Creative Commons images. Openverse is a search engine for openly-licensed media, including images, audio, and video.

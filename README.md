# IMGVerse - WordPress Creative Commons Image Plugin

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.6.0-orange.svg)](https://github.com/kraftysprouts/imgverse)

**IMGVerse** is a powerful WordPress plugin that integrates with the Openverse API to provide seamless access to millions of Creative Commons images. Search, preview, and import images directly into your WordPress media library with proper attribution.

## 🚀 Key Features

### 🔍 Unified Search
- Search across all Openverse sources with a single query
- Sources: Flickr, Wikimedia Commons, iNaturalist, Metropolitan Museum, NYPL, Rawpixel, Smithsonian
- Post-search filtering by source and license

### 🖼️ Professional Interface
- **Media Modal Integration**: Works like Instant Images with professional media tab
- **Block Editor Support**: Sidebar panel for Gutenberg editor
- **Responsive Design**: Perfect on desktop, tablet, and mobile
- **Infinite Scroll**: Seamless browsing experience

### ⚡ Performance Optimized
- **Intelligent Caching**: Auto-detects Redis, Memcached, WordPress object cache
- **Server Compatibility**: Works with existing server caching systems
- **Database Protection**: Prevents overload with size limits and smart purging
- **Rate Limiting**: Built-in API protection

### 📝 Attribution Management
- **Template System**: Custom attribution templates with variables
- **Multiple Styles**: Simple, Standard, Academic, or Custom formats
- **Flexible Placement**: Captions, descriptions, or custom fields
- **Link Options**: Source, creator, license, or no links

### 🎛️ Image Size Management
- **Per-Image Selection**: Choose size for each image during import
- **Size Options**: Thumbnail, Medium, Large, Full Size
- **Smart Defaults**: Automatic size selection based on content
- **Bulk Operations**: Apply same settings to multiple images

## 📦 Installation

### WordPress Admin
1. Go to **Plugins > Add New**
2. Search for "IMGVerse"
3. Click **Install Now** and **Activate**

### Manual Installation
1. Download the plugin files
2. Upload to `/wp-content/plugins/imgverse/`
3. Activate through the **Plugins** menu

### Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- Internet connection for API access

## 🎯 Usage

### Media Modal (Classic & Block Editor)
1. Click **Add Media** in any post/page
2. Select the **IMGVerse** tab
3. Enter your search query
4. Browse results in the grid
5. Click **Import** to add to your media library

### Block Editor Sidebar
1. Open the **IMGVerse Images** panel in the sidebar
2. Search for images
3. Select size and click **Import Image**
4. Image block is automatically inserted

### Admin Settings
1. Go to **Settings > IMGVerse**
2. Configure search behavior, attribution, import settings
3. Monitor performance and cache statistics
4. Set up custom attribution templates

## ⚙️ Configuration

### Search & Display Settings
- **Default Search Behavior**: All sources vs specific source
- **Results Per Page**: Number of images to load initially
- **Infinite Scroll**: Enable/disable seamless browsing
- **Grid Layout**: Number of columns and image preview size

### Attribution Settings
- **Template Variables**: `{title}`, `{creator}`, `{source}`, `{license}`, `{license_url}`, `{url}`
- **Attribution Styles**: Simple, Standard, Academic, Custom
- **Placement Options**: Caption, Description, Custom Field
- **Link Behavior**: Source, Creator, License, None

### Import Settings
- **Default Image Size**: Thumbnail, Medium, Large, Full
- **Image Quality**: Compression settings (60-100%)
- **File Naming**: Title, Original, Custom pattern
- **Import Location**: Default folder or custom subfolder
- **Duplicate Handling**: Skip, Rename, Overwrite

### Performance Settings
- **Cache Duration**: 15 minutes to 24 hours
- **Max Cache Size**: 1MB to 100MB
- **Request Timeout**: 5 to 120 seconds
- **Concurrent Requests**: 1 to 10 simultaneous requests
- **Rate Limiting**: 10 to 300 requests per hour
- **Cache Strategy**: Auto-detect, External, WP Object, Database, Disabled

## 🔧 Developer Features

### Hooks and Filters
```php
// Register custom sources
add_action('imgv_register_source', 'my_custom_source');

// Modify API queries
add_filter('imgv_source_query', 'modify_search_query');

// Filter API responses
add_filter('imgv_source_response', 'process_api_response');

// Custom authentication
add_filter('imgv_custom_authentication', 'my_auth_method');
```

### Custom Source Integration
```php
// Add custom image source
function my_custom_source($sources) {
    $sources['myapi'] = array(
        'name' => 'My API',
        'api_endpoint' => 'https://api.example.com/',
        'default_license' => 'cc0,by'
    );
    return $sources;
}
add_filter('imgv_register_source', 'my_custom_source');
```

## 🏗️ Architecture

### File Structure
```
imgverse/
├── imgverse.php                 # Main plugin file
├── includes/
│   ├── class-imgv-core.php     # Core functionality
│   ├── class-imgv-api.php      # API handler
│   ├── class-imgv-cache.php    # Caching system
│   ├── class-imgv-media-tab.php # Media modal integration
│   ├── class-imgv-block-editor.php # Block editor integration
│   └── class-imgv-admin.php    # Admin interface
├── assets/
│   ├── js/
│   │   ├── imgv-media-tab.js   # Media modal JavaScript
│   │   ├── imgv-block-editor.js # Block editor JavaScript
│   │   └── imgv-admin.js       # Admin JavaScript
│   └── css/
│       ├── imgv-media-tab.css  # Media modal styles
│       ├── imgv-block-editor.css # Block editor styles
│       └── imgv-admin.css      # Admin styles
├── languages/                  # Translation files
├── readme.txt                 # WordPress repository readme
└── README.md                  # This file
```

### Caching Strategy
1. **External Cache** (Redis/Memcached) - Preferred, no database impact
2. **WordPress Object Cache** - If persistent and available
3. **Database Transients** - Fallback with size limits
4. **No Caching** - Direct API calls if all else fails

## 🔒 Security

- **Input Sanitization**: All user inputs are properly sanitized
- **Nonce Verification**: AJAX requests are protected with nonces
- **Capability Checks**: Proper permission verification
- **API Response Validation**: External data is validated before processing
- **File Upload Security**: Image types and content are validated

## 📊 Performance

### Caching Benefits
- **Redis/Memcached**: 95%+ cache hit rate, minimal database load
- **WordPress Object Cache**: 80%+ hit rate, reduced API calls
- **Database Cache**: 60%+ hit rate, fallback option
- **Smart Purging**: LRU eviction prevents database bloat

### Optimization Features
- **Lazy Loading**: Images load only when visible
- **Background Processing**: Heavy operations don't block UI
- **Rate Limiting**: Prevents API overload
- **Memory Management**: Proper garbage collection
- **Query Optimization**: Efficient database operations

## 🧪 Testing

### Tested With
- WordPress 5.0 - 6.4
- PHP 7.4 - 8.2
- Classic Editor and Block Editor
- Various themes and plugins
- Mobile and desktop browsers

### Performance Testing
- Large result sets (1000+ images)
- High concurrent usage
- Memory usage monitoring
- Database load testing
- Cache effectiveness analysis

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### Development Setup
1. Clone the repository
2. Install dependencies: `npm install`
3. Run tests: `npm test`
4. Build assets: `npm run build`

### Code Standards
- WordPress PHP Coding Standards
- ESLint for JavaScript
- Proper documentation
- Comprehensive error handling

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

## 🆘 Support

- **Documentation**: [Plugin Documentation](https://kraftysprouts.com/imgverse/docs)
- **Support Forum**: [WordPress Support](https://wordpress.org/support/plugin/imgverse)
- **GitHub Issues**: [Report Bugs](https://github.com/kraftysprouts/imgverse/issues)
- **Email Support**: [support@kraftysprouts.com](mailto:support@kraftysprouts.com)

## 🙏 Credits

- **Openverse API**: [api.openverse.org](https://api.openverse.org/)
- **WordPress Community**: For the amazing platform
- **Contributors**: All developers who helped make this possible

## 📈 Roadmap

### Version 1.7.0
- [ ] Advanced image filters (color, orientation, size)
- [ ] Bulk import with progress tracking
- [ ] Custom source API integration
- [ ] Advanced attribution options
- [ ] Image comparison tools

### Version 1.8.0
- [ ] AI-powered image suggestions
- [ ] Advanced search operators
- [ ] Custom image collections
- [ ] Export/import settings
- [ ] Multi-language support

---

**Made with ❤️ by [Krafty Sprouts Media, LLC](https://kraftysprouts.com)**

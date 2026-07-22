<?php
/**
 * IMGVerse API Handler
 * 
 * @package IMGVerse
 * @author Krafty Sprouts Media, LLC
 * @version 1.5.0
 * @since 1.0.0
 * @last_modified 10/24/2025
 */

// Prevent direct access (allow PHPUnit bootstrap without WordPress).
if ( ! defined( 'ABSPATH' ) ) {
	if ( ! defined( 'IMGV_TEST_BOOTSTRAP' ) ) {
		exit;
	}
}

/**
 * IMGV_API Class
 * 
 * Handles all API interactions with Openverse and other sources
 * 
 * @since 1.0.0
 */
class IMGV_API {
    
    /**
     * Cache instance
     * 
     * @since 1.0.0
     * @var IMGV_Cache
     */
    private $cache;

    /**
     * Registered provider adapters.
     *
     * @since 2.0.0
     * @var array
     */
    private $providers = array();
    
    /**
     * Available sources
     * 
     * @since 1.0.0
     * @var array
     */
    private $sources = array(
        'flickr' => array(
            'name' => 'Flickr',
            'api_endpoint' => 'https://api.openverse.org/v1/images/',
            'default_license' => 'cc0,by,by-sa,by-nc,by-nc-sa,by-nc-nd,by-nd'
        ),
        'wikimedia' => array(
            'name' => 'Wikimedia Commons',
            'api_endpoint' => 'https://api.openverse.org/v1/images/',
            'default_license' => 'cc0,by,by-sa'
        ),
        'inaturalist' => array(
            'name' => 'iNaturalist',
            'api_endpoint' => 'https://api.openverse.org/v1/images/',
            'default_license' => 'cc0,by,by-sa,by-nc,by-nc-sa'
        ),
        'met' => array(
            'name' => 'Metropolitan Museum',
            'api_endpoint' => 'https://api.openverse.org/v1/images/',
            'default_license' => 'cc0,by,by-sa'
        ),
        'nypl' => array(
            'name' => 'NYPL',
            'api_endpoint' => 'https://api.openverse.org/v1/images/',
            'default_license' => 'cc0,by,by-sa'
        ),
        'rawpixel' => array(
            'name' => 'Rawpixel',
            'api_endpoint' => 'https://api.openverse.org/v1/images/',
            'default_license' => 'cc0,by,by-sa'
        ),
        'smithsonian' => array(
            'name' => 'Smithsonian',
            'api_endpoint' => 'https://api.openverse.org/v1/images/',
            'default_license' => 'cc0,by,by-sa'
        )
    );
    
    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct() {
        $this->cache = new IMGV_Cache();
        $this->providers = array(
            'openverse' => new IMGV_Provider_Openverse(),
            'unsplash'  => new IMGV_Provider_Unsplash(),
            'pixabay'   => new IMGV_Provider_Pixabay(),
            'pexels'    => new IMGV_Provider_Pexels(),
        );
    }

    /**
     * Resolve a provider adapter by slug.
     *
     * @since 2.0.0
     * @param string $provider Provider slug.
     * @return IMGV_Provider_Interface|null
     */
    private function get_provider( $provider ) {
        $provider = sanitize_key( $provider );
        if ( isset( $this->providers[ $provider ] ) ) {
            return $this->providers[ $provider ];
        }
        return null;
    }
    
    /**
     * Search for images
     * 
     * @since 1.0.0
     * @param string $query    Search query
     * @param string $provider Provider slug (openverse, unsplash, pixabay, pexels)
     * @param array  $args     Provider args (source, license, page, page_size)
     * @return array Search results
     */
    public function search_images( $query, $provider = 'openverse', $args = array() ) {
        $provider = sanitize_key( $provider );
        if ( '' === $provider ) {
            $provider = 'openverse';
        }

        $adapter = $this->get_provider( $provider );
        if ( ! $adapter ) {
            return array(
                'success' => false,
                'message' => __( 'Unknown provider.', 'imgverse' ),
                'images'  => array(),
            );
        }

        $args = is_array( $args ) ? $args : array();
        $page = isset( $args['page'] ) ? max( 1, (int) $args['page'] ) : 1;
        if ( ! isset( $args['page'] ) ) {
            $args['page'] = $page;
        }
        if ( ! isset( $args['page_size'] ) ) {
            $args['page_size'] = 20;
        }

        $source  = isset( $args['source'] ) ? (string) $args['source'] : '';
        $license = isset( $args['license'] ) ? (string) $args['license'] : '';
        $cache_key = $this->generate_cache_key( $provider, $query, $source, $license, $page );

        // Check cache first
        $cached_result = $this->cache->get( $cache_key );
        if ( false !== $cached_result ) {
            return $cached_result;
        }

        $result = $adapter->search( $query, $args );

        if ( ! isset( $result['message'] ) ) {
            $result['message'] = '';
        }

        // Cache successful results
        if ( ! empty( $result['success'] ) ) {
            $this->cache->set( $cache_key, $result, 1800 ); // 30 minutes
        }

        return $result;
    }
    
    /**
     * Process image data from API
     * 
     * @since 1.0.0
     * @param array $data Raw image data from API
     * @return array Processed image data
     */
    private function process_image_data($data) {
        $sizes = $this->get_image_sizes($data);
        
        return array(
            'id' => $data['id'],
            'title' => $data['title'] ?? __('Untitled', 'imgverse'),
            'url' => $data['url'],
            'thumbnail' => $data['thumbnail'] ?? $data['url'],
            'creator' => $data['creator'] ?? __('Unknown', 'imgverse'),
            'source' => $data['source'],
            'license' => $data['license'],
            'license_url' => $data['license_url'] ?? '',
            'foreign_landing_url' => $data['foreign_landing_url'] ?? '',
            'attribution' => $this->generate_attribution($data),
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'sizes' => $sizes,
            'tags' => $data['tags'] ?? array(),
            'description' => $data['description'] ?? ''
        );
    }
    
    /**
     * Get available image sizes
     * 
     * @since 1.0.0
     * @param array $data Image data
     * @return array Available sizes
     */
    private function get_image_sizes($data) {
        $sizes = array();
        
        // Get WordPress image sizes
        $wp_sizes = get_intermediate_image_sizes();
        $wp_sizes[] = 'full';
        
        foreach ($wp_sizes as $size) {
            $size_data = wp_get_attachment_image_src(0, $size);
            if ($size_data) {
                $sizes[$size] = array(
                    'url' => $data['url'],
                    'width' => $size_data[1],
                    'height' => $size_data[2],
                    'file_size' => $this->estimate_file_size($data['url'])
                );
            }
        }
        
        // Add original size
        $sizes['original'] = array(
            'url' => $data['url'],
            'width' => $data['width'] ?? 0,
            'height' => $data['height'] ?? 0,
            'file_size' => $this->estimate_file_size($data['url'])
        );
        
        return $sizes;
    }
    
    /**
     * Estimate file size for an image URL
     * 
     * @since 1.0.0
     * @param string $url Image URL
     * @return string Estimated file size
     */
    private function estimate_file_size($url) {
        // This is a rough estimation - in a real implementation,
        // you might want to make a HEAD request to get actual file size
        $headers = wp_remote_head($url);
        if (!is_wp_error($headers)) {
            $content_length = wp_remote_retrieve_header($headers, 'content-length');
            if ($content_length) {
                return size_format($content_length);
            }
        }
        
        return __('Unknown', 'imgverse');
    }
    
	/**
	 * Optionally resize a local image file to max width/height.
	 *
	 * Pass 0 for either dimension to leave that side unconstrained.
	 * Pass 0 for both to disable resizing.
	 *
	 * @since 2.0.0
	 * @param string $file  Absolute path to image file.
	 * @param int    $max_w Max width in pixels (0 = unconstrained / disabled with max_h 0).
	 * @param int    $max_h Max height in pixels (0 = unconstrained / disabled with max_w 0).
	 * @return true|WP_Error True on success or when resize is skipped.
	 */
	public static function maybe_resize_file( $file, $max_w, $max_h ) {
		$max_w = (int) $max_w;
		$max_h = (int) $max_h;

		if ( $max_w <= 0 && $max_h <= 0 ) {
			return true;
		}

		if ( ! is_string( $file ) || '' === $file || ! file_exists( $file ) ) {
			return new WP_Error(
				'imgv_resize_missing_file',
				__( 'Image file not found for resize.', 'imgverse' )
			);
		}

		if ( ! function_exists( 'wp_get_image_editor' ) ) {
			return new WP_Error(
				'imgv_resize_unavailable',
				__( 'Image editor is unavailable.', 'imgverse' )
			);
		}

		$editor = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) ) {
			return $editor;
		}

		$resized = $editor->resize( $max_w > 0 ? $max_w : null, $max_h > 0 ? $max_h : null, false );
		if ( is_wp_error( $resized ) ) {
			return $resized;
		}

		$saved = $editor->save( $file );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return true;
	}

    /**
     * Import image to WordPress media library
     * 
     * Always downloads the full remote URL. The $size argument is kept for
     * AJAX compatibility and is not used to select a remote download size.
     * Optional local max dimensions come from imgv_settings max_download_*.
     *
     * @since 1.0.0
     * @param string $image_url   Image URL
     * @param string $title       Image title
     * @param string $attribution Attribution / caption text
     * @param string $alt_text    Alt text
     * @param string $size        Unused for remote download (compat only)
     * @param int    $post_id     Post ID to attach image to (optional)
     * @param string $provider    Provider slug for import meta (optional)
     * @param string $source      Source slug for import meta (optional)
     * @return array Import result
     */
    public function import_image( $image_url, $title, $attribution, $alt_text, $size = 'full', $post_id = null, $provider = '', $source = '' ) {
		unset( $size ); // Remote download always uses the full URL.

        // Download the image
        $image_data = wp_remote_get($image_url, array('timeout' => 60));
        
        if (is_wp_error($image_data)) {
            return array(
                'success' => false,
                'message' => $image_data->get_error_message()
            );
        }
        
        $image_body = wp_remote_retrieve_body($image_data);
        if (empty($image_body)) {
            return array(
                'success' => false,
                'message' => __('Failed to download image', 'imgverse')
            );
        }
        
        // Get file info
        $filename = $this->generate_filename($image_url, $title);
        $upload = wp_upload_bits($filename, null, $image_body);
        
        if ($upload['error']) {
            return array(
                'success' => false,
                'message' => $upload['error']
            );
        }

		$settings = get_option( 'imgv_settings', array() );
		$max_w    = isset( $settings['max_download_width'] ) ? (int) $settings['max_download_width'] : 2400;
		$max_h    = isset( $settings['max_download_height'] ) ? (int) $settings['max_download_height'] : 2400;
		$resized  = self::maybe_resize_file( $upload['file'], $max_w, $max_h );
		if ( is_wp_error( $resized ) ) {
			return array(
				'success' => false,
				'message' => $resized->get_error_message(),
			);
		}
        
        // Create attachment
        $attachment = array(
            'guid' => $upload['url'],
            'post_mime_type' => wp_check_filetype($filename)['type'],
            'post_title' => $title,
            'post_content' => $attribution,
            'post_excerpt' => $attribution,
            'post_status' => 'inherit'
        );
        
        $attachment_id = wp_insert_attachment($attachment, $upload['file']);
        
        if (is_wp_error($attachment_id)) {
            return array(
                'success' => false,
                'message' => __('Failed to create attachment', 'imgverse')
            );
        }
        
        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $metadata);
        
        // Set alt text
        if (!empty($alt_text)) {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
        }

		$post_id = (int) $post_id;
		if ( $post_id > 0 ) {
			wp_update_post(
				array(
					'ID'          => $attachment_id,
					'post_parent' => $post_id,
				)
			);
		}

		update_post_meta( $attachment_id, '_imgv_imported', true );
		update_post_meta( $attachment_id, '_imgv_import_date', current_time( 'mysql' ) );
		update_post_meta( $attachment_id, '_imgv_original_url', esc_url_raw( $image_url ) );
		update_post_meta( $attachment_id, '_imgv_provider', sanitize_key( $provider ) );
		update_post_meta( $attachment_id, '_imgv_source', sanitize_key( $source ) );
        
        // Get attachment details
        $attachment_url = wp_get_attachment_url($attachment_id);
        $attachment_data = wp_prepare_attachment_for_js($attachment_id);
        
        return array(
            'success' => true,
            'attachment_id' => $attachment_id,
            'url' => $attachment_url,
            'attachment' => $attachment_data,
            'post_id' => $post_id
        );
    }
    
    /**
     * Generate attribution text
     * 
     * @since 1.0.0
     * @param array $image_data Image data
     * @return string Attribution text
     */
    public function generate_attribution($image_data) {
        $settings = get_option('imgv_settings', array());
        $template = $settings['default_attribution_template'] ?? '"{title}" by {creator} from {source}';
        
        $title = $image_data['title'] ?? __('Untitled', 'imgverse');
        $creator = $image_data['creator'] ?? __('Unknown', 'imgverse');
        $source = $image_data['source'] ?? '';
        $license = strtoupper($image_data['license'] ?? '');
        $license_url = $image_data['license_url'] ?? '';
        $foreign_url = $image_data['foreign_landing_url'] ?? '';
        
        // Replace template variables
        $attribution = str_replace(
            array('{title}', '{creator}', '{source}', '{license}', '{license_url}', '{url}'),
            array($title, $creator, $source, $license, $license_url, $foreign_url),
            $template
        );
        
        // Add license information if not already included
        if (!empty($license) && strpos($attribution, $license) === false) {
            if (!empty($license_url)) {
                $attribution .= sprintf(' is licensed under <a href="%s" target="_blank" rel="noopener">%s</a>', 
                    esc_url($license_url), $license);
            } else {
                $attribution .= ' is licensed under ' . $license;
            }
        }
        
        return $attribution;
    }
    
    /**
     * Generate filename for imported image
     * 
     * @since 1.0.0
     * @param string $url Image URL
     * @param string $title Image title
     * @return string Generated filename
     */
    private function generate_filename($url, $title) {
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (empty($extension)) {
            $extension = 'jpg'; // Default extension
        }
        
        $filename = !empty($title) ? sanitize_file_name($title) : 'imgverse-image';
        $filename = preg_replace('/[^a-zA-Z0-9-_]/', '-', $filename);
        $filename = preg_replace('/-+/', '-', $filename);
        $filename = trim($filename, '-');
        
        if (empty($filename)) {
            $filename = 'imgverse-image-' . time();
        }
        
        return $filename . '.' . $extension;
    }
    
    /**
     * Get available sources
     * 
     * @since 1.0.0
     * @return array Available sources
     */
    public function get_available_sources() {
        return $this->sources;
    }
    
    /**
     * Generate cache key
     * 
     * @since 1.0.0
     * @param string $provider Provider slug.
     * @param string $query Search query
     * @param string $source Source filter
     * @param string $license License filter
     * @param int $page Page number
     * @return string Cache key
     */
    private function generate_cache_key($provider, $query, $source, $license, $page) {
        return IMGV_CACHE_PREFIX . 'search_' . md5($provider . $query . $source . $license . $page);
    }
}

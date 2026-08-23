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
            $settings = get_option( 'imgv_settings', array() );
            $per_page = isset( $settings['results_per_page'] ) ? (int) $settings['results_per_page'] : 20;
            $args['page_size'] = max( 10, min( 100, $per_page > 0 ? $per_page : 20 ) );
        }

        $source  = isset( $args['source'] ) ? (string) $args['source'] : '';
        $license = isset( $args['license'] ) ? (string) $args['license'] : '';
        $page_size = isset( $args['page_size'] ) ? (int) $args['page_size'] : 20;
        $cache_key = $this->generate_cache_key( $provider, $query, $source, $license, $page, $page_size );

        // Check cache first
        $cached_result = $this->cache->get( $cache_key );
        if ( false !== $cached_result ) {
            // Rebuild attribution from current settings (Openverse cache may
            // still hold provider-native strings).
            if ( is_array( $cached_result ) && ! empty( $cached_result['images'] ) ) {
                $cached_result['images'] = $this->apply_attribution_settings( $cached_result['images'] );
            }
            return $cached_result;
        }

        $result = $adapter->search( $query, $args );

        if ( ! isset( $result['message'] ) ) {
            $result['message'] = '';
        }

        if ( ! empty( $result['success'] ) && ! empty( $result['images'] ) && is_array( $result['images'] ) ) {
            $result['images'] = $this->apply_attribution_settings( $result['images'] );
        }

        // Cache successful results using settings TTL.
        if ( ! empty( $result['success'] ) ) {
            $settings = get_option( 'imgv_settings', array() );
            $ttl      = isset( $settings['cache_duration'] ) ? (int) $settings['cache_duration'] : 1800;
            if ( $ttl < 300 ) {
                $ttl = 300;
            }
            $this->cache->set( $cache_key, $result, $ttl );
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
	 * @param string $file    Absolute path to image file.
	 * @param int    $max_w   Max width in pixels (0 = unconstrained / disabled with max_h 0).
	 * @param int    $max_h   Max height in pixels (0 = unconstrained / disabled with max_w 0).
	 * @param int    $quality Optional JPEG quality 60–100 (0 = editor default).
	 * @return true|WP_Error True on success or when resize is skipped.
	 */
	public static function maybe_resize_file( $file, $max_w, $max_h, $quality = 0 ) {
		$max_w   = (int) $max_w;
		$max_h   = (int) $max_h;
		$quality = (int) $quality;

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

		$size = $editor->get_size();
		if ( is_array( $size ) ) {
			$orig_w = isset( $size['width'] ) ? (int) $size['width'] : 0;
			$orig_h = isset( $size['height'] ) ? (int) $size['height'] : 0;
			$fits_w = ( $max_w <= 0 || $orig_w <= $max_w );
			$fits_h = ( $max_h <= 0 || $orig_h <= $max_h );

			// Already within bounds — WordPress resize() errors with
			// error_getting_dimensions in this case; treat as success.
			if ( $orig_w > 0 && $orig_h > 0 && $fits_w && $fits_h ) {
				return true;
			}
		}

		if ( $quality >= 60 && $quality <= 100 && method_exists( $editor, 'set_quality' ) ) {
			$editor->set_quality( $quality );
		}

		$resized = $editor->resize( $max_w > 0 ? $max_w : null, $max_h > 0 ? $max_h : null, false );
		if ( is_wp_error( $resized ) ) {
			// Image already at/under target size — not a failure.
			if ( 'error_getting_dimensions' === $resized->get_error_code() ) {
				return true;
			}
			return $resized;
		}

		$saved = $editor->save( $file );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return true;
	}

	/**
	 * Allowed import URL host suffixes (https only).
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public static function get_allowed_import_host_suffixes() {
		$suffixes = array(
			'unsplash.com',
			'pixabay.com',
			'pexels.com',
			'wikimedia.org',
			'wikipedia.org',
			'inaturalist.org',
			'inaturalist-open-data.s3.amazonaws.com',
			'staticflickr.com',
			'flickr.com',
			'openverse.org',
			'openverse.engineering',
			'nypl.org',
			'metmuseum.org',
			'si.edu',
			'smithsonian.org',
			'rawpixel.com',
		);

		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filter allowed import URL host suffixes.
			 *
			 * @since 2.0.0
			 * @param array $suffixes Host suffixes (e.g. unsplash.com).
			 */
			$suffixes = apply_filters( 'imgv_allowed_import_host_suffixes', $suffixes );
		}

		return is_array( $suffixes ) ? $suffixes : array();
	}

	/**
	 * Whether a remote URL is allowed for import (https + host allowlist).
	 *
	 * @since 2.0.0
	 * @param string $url Image URL.
	 * @return bool
	 */
	public static function is_allowed_import_url( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return false;
		}

		$parts = function_exists( 'wp_parse_url' ) ? wp_parse_url( $url ) : parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return false;
		}

		if ( 'https' !== strtolower( (string) $parts['scheme'] ) ) {
			return false;
		}

		$host = strtolower( (string) $parts['host'] );
		foreach ( self::get_allowed_import_host_suffixes() as $suffix ) {
			$suffix = strtolower( (string) $suffix );
			if ( '' === $suffix ) {
				continue;
			}
			if ( $host === $suffix || substr( $host, -strlen( '.' . $suffix ) ) === '.' . $suffix ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detect image MIME for a local file.
	 *
	 * @since 2.0.0
	 * @param string $file Absolute path.
	 * @return string MIME type or empty string.
	 */
	public static function get_downloaded_image_mime( $file ) {
		if ( ! is_string( $file ) || '' === $file || ! file_exists( $file ) ) {
			return '';
		}

		if ( function_exists( 'wp_get_image_mime' ) ) {
			$mime = wp_get_image_mime( $file );
			if ( is_string( $mime ) && '' !== $mime ) {
				return $mime;
			}
		}

		$info = @getimagesize( $file );
		if ( is_array( $info ) && ! empty( $info['mime'] ) ) {
			return (string) $info['mime'];
		}

		return '';
	}

	/**
	 * Whether a MIME type is an allowed import image type.
	 *
	 * @since 2.0.0
	 * @param string $mime MIME type.
	 * @return bool
	 */
	public static function is_allowed_image_mime( $mime ) {
		$allowed = array(
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/webp',
		);

		return in_array( (string) $mime, $allowed, true );
	}

	/**
	 * Delete a local upload file if present.
	 *
	 * @since 2.0.0
	 * @param string $file Absolute path.
	 * @return void
	 */
	private static function delete_upload_file( $file ) {
		if ( ! is_string( $file ) || '' === $file || ! file_exists( $file ) ) {
			return;
		}

		if ( function_exists( 'wp_delete_file' ) ) {
			wp_delete_file( $file );
			return;
		}

		unlink( $file );
	}

	/**
	 * Whether the current user may attach media to a post.
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID (0 skips the check).
	 * @return bool
	 */
	public static function user_can_attach_to_post( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return true;
		}

		return current_user_can( 'edit_post', $post_id );
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
	 * @param array  $meta        Extra attribution fields (creator, license, license_url, permalink).
	 * @return array Import result
	 */
	public function import_image( $image_url, $title, $attribution, $alt_text, $size = 'full', $post_id = null, $provider = '', $source = '', $meta = array() ) {
		unset( $size ); // Remote download always uses the full URL.
		$meta = is_array( $meta ) ? $meta : array();
		// Client caption is ignored for storage — rebuilt from settings below.
		unset( $attribution );

		if ( ! self::is_allowed_import_url( $image_url ) ) {
			return array(
				'success' => false,
				'message' => __( 'Image URL is not from an allowed provider.', 'imgverse' ),
			);
		}

		$image_data = wp_safe_remote_get(
			$image_url,
			array(
				'timeout'     => 60,
				'redirection' => 3,
			)
		);

		if ( is_wp_error( $image_data ) ) {
			return array(
				'success' => false,
				'message' => $image_data->get_error_message(),
			);
		}

		$status_code = (int) wp_remote_retrieve_response_code( $image_data );
		if ( $status_code < 200 || $status_code > 299 ) {
			return array(
				'success' => false,
				'message' => __( 'Failed to download image', 'imgverse' ),
			);
		}

		$image_body = wp_remote_retrieve_body( $image_data );
		if ( empty( $image_body ) ) {
			return array(
				'success' => false,
				'message' => __( 'Failed to download image', 'imgverse' ),
			);
		}

		$filename = $this->generate_filename( $image_url, $title );
		$upload   = wp_upload_bits( $filename, null, $image_body );

		if ( ! empty( $upload['error'] ) ) {
			return array(
				'success' => false,
				'message' => $upload['error'],
			);
		}

		$file = isset( $upload['file'] ) ? $upload['file'] : '';
		$mime = self::get_downloaded_image_mime( $file );
		if ( ! self::is_allowed_image_mime( $mime ) ) {
			self::delete_upload_file( $file );
			return array(
				'success' => false,
				'message' => __( 'Downloaded file is not a valid image.', 'imgverse' ),
			);
		}

		$settings = get_option( 'imgv_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$max_w   = isset( $settings['max_download_width'] ) ? (int) $settings['max_download_width'] : 2400;
		$max_h   = isset( $settings['max_download_height'] ) ? (int) $settings['max_download_height'] : 2400;
		$quality = isset( $settings['image_quality'] ) ? (int) $settings['image_quality'] : 90;
		$resized = self::maybe_resize_file( $file, $max_w, $max_h, $quality );
		if ( is_wp_error( $resized ) ) {
			self::delete_upload_file( $file );
			return array(
				'success' => false,
				'message' => $resized->get_error_message(),
			);
		}

		// Never store provider legal dumps or raw license URLs as captions.
		// Rebuild credit from settings when auto attribution is on; otherwise empty.
		$auto_attribution = ! isset( $settings['auto_attribution'] ) || ! empty( $settings['auto_attribution'] );
		if ( $auto_attribution ) {
			$meta_creator = is_array( $meta ) && isset( $meta['creator'] ) ? (string) $meta['creator'] : '';
			$attribution  = $this->generate_attribution(
				array(
					'title'       => $title,
					'creator'     => $meta_creator,
					'source'      => $source,
					'license'     => is_array( $meta ) && isset( $meta['license'] ) ? (string) $meta['license'] : '',
					'license_url' => is_array( $meta ) && isset( $meta['license_url'] ) ? (string) $meta['license_url'] : '',
					'permalink'   => is_array( $meta ) && isset( $meta['permalink'] ) ? (string) $meta['permalink'] : '',
				)
			);
		} else {
			$attribution = '';
		}

		$placement = isset( $settings['attribution_placement'] ) ? $settings['attribution_placement'] : 'caption';
		$content   = '';
		$excerpt   = '';
		if ( '' !== $attribution ) {
			if ( 'description' === $placement ) {
				$content = $attribution;
			} else {
				$excerpt = $attribution;
			}
		}

		$attachment = array(
			'guid'           => $upload['url'],
			'post_mime_type' => $mime,
			'post_title'     => $title,
			'post_content'   => $content,
			'post_excerpt'   => $excerpt,
			'post_status'    => 'inherit',
		);

		$attachment_id = wp_insert_attachment( $attachment, $file );

		if ( is_wp_error( $attachment_id ) ) {
			self::delete_upload_file( $file );
			return array(
				'success' => false,
				'message' => __( 'Failed to create attachment', 'imgverse' ),
			);
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $attachment_id, $file );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		if ( ! empty( $alt_text ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
		}

		if ( 'custom_field' === $placement && '' !== $attribution ) {
			update_post_meta( $attachment_id, '_imgv_attribution', $attribution );
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

		$attachment_url  = wp_get_attachment_url( $attachment_id );
		$attachment_data = wp_prepare_attachment_for_js( $attachment_id );

		return array(
			'success'       => true,
			'attachment_id' => $attachment_id,
			'url'           => $attachment_url,
			'attachment'    => $attachment_data,
			'post_id'       => $post_id,
		);
	}
    
    /**
     * Generate attribution text from settings.
     *
     * Accepts legacy keys (creator, foreign_landing_url) or normalized
     * search results (user.name, permalink). Custom template style does not
     * append extra license text — the template is authoritative.
     *
     * @since 1.0.0
     * @param array $image_data Image data (legacy or normalized).
     * @return string Attribution text
     */
    public function generate_attribution($image_data) {
        $settings = get_option('imgv_settings', array());
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }
        $style = isset( $settings['default_attribution_style'] ) ? $settings['default_attribution_style'] : 'standard';

        $title   = isset( $image_data['title'] ) ? (string) $image_data['title'] : __( 'Untitled', 'imgverse' );
        $creator = '';
        if ( ! empty( $image_data['creator'] ) ) {
            $creator = (string) $image_data['creator'];
        } elseif ( ! empty( $image_data['user']['name'] ) ) {
            $creator = (string) $image_data['user']['name'];
        }
        if ( '' === $creator ) {
            $creator = __( 'Unknown', 'imgverse' );
        }

        $source      = isset( $image_data['source'] ) ? (string) $image_data['source'] : '';
        $provider    = isset( $image_data['provider'] ) ? (string) $image_data['provider'] : '';
        $license     = strtoupper( isset( $image_data['license'] ) ? (string) $image_data['license'] : '' );
        $license_url = isset( $image_data['license_url'] ) ? (string) $image_data['license_url'] : '';
        $foreign_url = '';
        if ( ! empty( $image_data['foreign_landing_url'] ) ) {
            $foreign_url = (string) $image_data['foreign_landing_url'];
        } elseif ( ! empty( $image_data['permalink'] ) ) {
            $foreign_url = (string) $image_data['permalink'];
        }
        $credit_source = '' !== $source ? $source : $provider;

        if ( 'simple' === $style ) {
            $attribution = sprintf(
                /* translators: %s: image creator name */
                __( 'Photo by %s', 'imgverse' ),
                $creator
            );
        } elseif ( 'academic' === $style ) {
            $attribution = sprintf(
                '"%1$s" (%2$s). %3$s. %4$s.',
                $title,
                $creator,
                $source,
                $license
            );
        } elseif ( 'custom' === $style ) {
            $template = isset( $settings['default_attribution_template'] )
                ? (string) $settings['default_attribution_template']
                : 'Photo by {creator} / {source}';
            $attribution = str_replace(
                array( '{title}', '{creator}', '{source}', '{license}', '{license_url}', '{url}' ),
                array( $title, $creator, $source, $license, $license_url, $foreign_url ),
                $template
            );
        } else {
            // standard — short credit, no raw license URL dump.
            $attribution = sprintf(
                /* translators: 1: creator name, 2: source/provider */
                __( 'Photo by %1$s / %2$s', 'imgverse' ),
                $creator,
                $credit_source
            );
        }

        return trim( $attribution );
    }

    /**
     * Apply settings-based attribution to normalized search images.
     *
     * Provider APIs (especially Openverse) ship their own attribution strings
     * that ignore IMGVerse settings; always rebuild from the template/style.
     *
     * @since 2.1.1
     * @param array $images Normalized image list.
     * @return array
     */
    public function apply_attribution_settings( $images ) {
        if ( ! is_array( $images ) ) {
            return array();
        }

        foreach ( $images as $index => $image ) {
            if ( ! is_array( $image ) ) {
                continue;
            }
            $images[ $index ]['attribution'] = $this->generate_attribution( $image );
        }

        return $images;
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

        $settings   = get_option( 'imgv_settings', array() );
        $naming     = isset( $settings['file_naming'] ) ? $settings['file_naming'] : 'title';
        $path_base  = pathinfo( (string) parse_url( $url, PHP_URL_PATH ), PATHINFO_FILENAME );

        if ( 'original' === $naming && '' !== $path_base ) {
            $filename = sanitize_file_name( $path_base );
        } else {
            $filename = ! empty( $title ) ? sanitize_file_name( $title ) : 'imgverse-image';
        }

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
    private function generate_cache_key( $provider, $query, $source, $license, $page, $page_size = 20 ) {
        return IMGV_CACHE_PREFIX . 'search_' . md5(
            strtolower( (string) $provider ) . '|' .
            strtolower( trim( (string) $query ) ) . '|' .
            (string) $source . '|' .
            (string) $license . '|' .
            (int) $page . '|' .
            (int) $page_size
        );
    }
}

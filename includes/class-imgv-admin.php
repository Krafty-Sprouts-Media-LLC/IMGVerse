<?php
/**
 * IMGVerse Admin Interface
 * 
 * @package IMGVerse
 * @author Krafty Sprouts Media, LLC
 * @version 1.5.0
 * @since 1.0.0
 * @last_modified 10/24/2025
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * IMGV_Admin Class
 * 
 * Handles admin interface and settings
 * 
 * @since 1.0.0
 */
class IMGV_Admin {
    
    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Register plugin settings
     * 
     * @since 1.0.0
     */
    public function register_settings() {
        register_setting('imgv_settings', 'imgv_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));
    }
    
    /**
     * Sanitize settings
     * 
     * @since 1.0.0
     * @param array $input Raw settings input
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        $existing  = get_option('imgv_settings', array());
        
        // Search & Display Settings
        $sanitized['default_search_behavior'] = sanitize_text_field($input['default_search_behavior'] ?? 'all_sources');
        $sanitized['results_per_page'] = intval($input['results_per_page'] ?? 20);
        $sanitized['enable_infinite_scroll'] = !empty($input['enable_infinite_scroll']);
        $sanitized['grid_columns'] = intval($input['grid_columns'] ?? 4);
        
        // Attribution Settings
        $sanitized['auto_attribution'] = !empty($input['auto_attribution']);
        $sanitized['default_attribution_template'] = wp_kses_post($input['default_attribution_template'] ?? 'Photo by {creator} / {source}');
        $sanitized['default_attribution_style'] = sanitize_text_field($input['default_attribution_style'] ?? 'standard');
        $sanitized['attribution_placement'] = sanitize_text_field($input['attribution_placement'] ?? 'caption');
        $sanitized['link_behavior'] = sanitize_text_field($input['link_behavior'] ?? 'source');
        
        // Import Settings
        $sanitized['default_image_size'] = sanitize_text_field($input['default_image_size'] ?? 'large');
        $sanitized['image_quality'] = intval($input['image_quality'] ?? 90);
        $sanitized['file_naming'] = sanitize_text_field($input['file_naming'] ?? 'title');
        $sanitized['import_location'] = sanitize_text_field($input['import_location'] ?? 'default');
        $sanitized['duplicate_handling'] = sanitize_text_field($input['duplicate_handling'] ?? 'rename');
        $sanitized['max_download_width'] = max( 0, intval( $input['max_download_width'] ?? 2400 ) );
        $sanitized['max_download_height'] = max( 0, intval( $input['max_download_height'] ?? 2400 ) );
        
        // API & Performance Settings
        $sanitized['cache_duration'] = intval($input['cache_duration'] ?? 1800);
        $sanitized['max_cache_size'] = intval($input['max_cache_size'] ?? 10485760);
        $sanitized['request_timeout'] = intval($input['request_timeout'] ?? 30);
        $sanitized['concurrent_requests'] = intval($input['concurrent_requests'] ?? 3);
        $sanitized['rate_limiting'] = intval($input['rate_limiting'] ?? 60);
        $sanitized['cache_strategy'] = sanitize_text_field($input['cache_strategy'] ?? 'auto');
        $sanitized['background_processing'] = !empty($input['background_processing']);

        // Provider API keys (empty password submit keeps existing value).
        $key_fields = array(
            'unsplash_access_key',
            'pixabay_api_key',
            'pexels_api_key',
        );
        foreach ($key_fields as $key_field) {
            $submitted = isset($input[$key_field]) ? sanitize_text_field($input[$key_field]) : '';
            if ('' === $submitted && !empty($existing[$key_field])) {
                $sanitized[$key_field] = $existing[$key_field];
            } else {
                $sanitized[$key_field] = $submitted;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Render settings page
     * 
     * @since 1.0.0
     */
    public function render_settings_page() {
        $settings = get_option('imgv_settings', array());
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }
        $stats = self::get_import_stats();
        ?>
        <div class="wrap imgv-settings">
            <h1><?php esc_html_e( 'IMGVerse Settings', 'imgverse' ); ?></h1>
            <hr class="wp-header-end" />

            <div class="imgv-settings__hero">
                <div>
                    <p><?php esc_html_e( 'Configure providers, attribution, import limits, and search defaults used by the media modal and sidebar.', 'imgverse' ); ?></p>
                </div>
                <div class="imgv-settings__actions">
                    <button type="button" id="imgv-clear-cache" class="button button-secondary">
                        <?php esc_html_e( 'Clear Cache', 'imgverse' ); ?>
                    </button>
                    <button type="button" id="imgv-test-api" class="button button-secondary">
                        <?php esc_html_e( 'Test Openverse', 'imgverse' ); ?>
                    </button>
                </div>
            </div>

            <form method="post" action="options.php" class="imgv-settings__form">
                <?php settings_fields( 'imgv_settings' ); ?>

                <div class="imgv-settings__layout">
                    <aside class="imgv-settings__nav" aria-label="<?php esc_attr_e( 'Settings sections', 'imgverse' ); ?>">
                        <div class="imgv-settings__nav-card">
                            <h2 class="imgv-settings__nav-title"><?php esc_html_e( 'Sections', 'imgverse' ); ?></h2>
                            <ul class="imgv-settings__nav-list">
                                <li><button type="button" class="imgv-settings__nav-button is-active" data-target="imgv-section-general"><?php esc_html_e( 'General', 'imgverse' ); ?></button></li>
                                <li><button type="button" class="imgv-settings__nav-button" data-target="imgv-section-api"><?php esc_html_e( 'API Keys', 'imgverse' ); ?></button></li>
                                <li><button type="button" class="imgv-settings__nav-button" data-target="imgv-section-attribution"><?php esc_html_e( 'Attribution', 'imgverse' ); ?></button></li>
                                <li><button type="button" class="imgv-settings__nav-button" data-target="imgv-section-import"><?php esc_html_e( 'Import', 'imgverse' ); ?></button></li>
                                <li><button type="button" class="imgv-settings__nav-button" data-target="imgv-section-cache"><?php esc_html_e( 'Cache', 'imgverse' ); ?></button></li>
                            </ul>
                        </div>
                    </aside>

                    <div class="imgv-settings__sections">
                        <section id="imgv-section-general" class="imgv-settings-entry">
                            <div class="imgv-settings-entry__header">
                                <h2><?php esc_html_e( 'General', 'imgverse' ); ?></h2>
                                <p><?php esc_html_e( 'Search results and grid layout in the media modal and sidebar.', 'imgverse' ); ?></p>
                            </div>
                            <div class="imgv-settings-entry__body">
                                <table class="form-table" role="presentation">
                                    <tr>
                                        <th scope="row"><label for="imgv-results-per-page"><?php esc_html_e( 'Results Per Page', 'imgverse' ); ?></label></th>
                                        <td>
                                            <input type="number" id="imgv-results-per-page" name="imgv_settings[results_per_page]" value="<?php echo esc_attr( $settings['results_per_page'] ?? 20 ); ?>" min="10" max="100" />
                                            <p class="description"><?php esc_html_e( 'Number of images fetched per search request (10–100).', 'imgverse' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="imgv-grid-columns"><?php esc_html_e( 'Grid Columns', 'imgverse' ); ?></label></th>
                                        <td>
                                            <input type="number" id="imgv-grid-columns" name="imgv_settings[grid_columns]" value="<?php echo esc_attr( $settings['grid_columns'] ?? 4 ); ?>" min="2" max="6" />
                                            <p class="description"><?php esc_html_e( 'Preferred number of columns in the results grid (2–6).', 'imgverse' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e( 'Infinite Scroll', 'imgverse' ); ?></th>
                                        <td>
                                            <?php
                                            $infinite_scroll_on = ! array_key_exists( 'enable_infinite_scroll', $settings )
                                                || ! empty( $settings['enable_infinite_scroll'] );
                                            ?>
                                            <label for="imgv-enable-infinite-scroll">
                                                <input type="checkbox" id="imgv-enable-infinite-scroll" name="imgv_settings[enable_infinite_scroll]" value="1" <?php checked( $infinite_scroll_on ); ?> />
                                                <?php esc_html_e( 'Automatically load more results as you scroll', 'imgverse' ); ?>
                                            </label>
                                        </td>
                                    </tr>
                                </table>
                                <div class="imgv-settings__stats">
                                    <p><strong><?php esc_html_e( 'Total imports:', 'imgverse' ); ?></strong> <?php echo esc_html( (string) $stats['total_imports'] ); ?></p>
                                    <p><strong><?php esc_html_e( 'Imports (last 7 days):', 'imgverse' ); ?></strong> <?php echo esc_html( (string) $stats['recent_imports'] ); ?></p>
                                </div>
                            </div>
                        </section>

                        <section id="imgv-section-api" class="imgv-settings-entry">
                            <div class="imgv-settings-entry__header">
                                <h2><?php esc_html_e( 'API Keys', 'imgverse' ); ?></h2>
                                <p><?php esc_html_e( 'Openverse needs no key. Unsplash, Pixabay, and Pexels keys stay server-side and are never sent to the editor.', 'imgverse' ); ?></p>
                            </div>
                            <div class="imgv-settings-entry__body">
                                <table class="form-table" role="presentation">
                                    <tr>
                                        <th scope="row"><label for="imgv-unsplash-access-key"><?php esc_html_e( 'Unsplash Access Key', 'imgverse' ); ?></label></th>
                                        <td>
                                            <input type="password" id="imgv-unsplash-access-key" name="imgv_settings[unsplash_access_key]" value="" class="regular-text" autocomplete="off" />
                                            <p class="description"><?php esc_html_e( 'Leave blank to keep the existing key.', 'imgverse' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="imgv-pixabay-api-key"><?php esc_html_e( 'Pixabay API Key', 'imgverse' ); ?></label></th>
                                        <td>
                                            <input type="password" id="imgv-pixabay-api-key" name="imgv_settings[pixabay_api_key]" value="" class="regular-text" autocomplete="off" />
                                            <p class="description"><?php esc_html_e( 'Leave blank to keep the existing key.', 'imgverse' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="imgv-pexels-api-key"><?php esc_html_e( 'Pexels API Key', 'imgverse' ); ?></label></th>
                                        <td>
                                            <input type="password" id="imgv-pexels-api-key" name="imgv_settings[pexels_api_key]" value="" class="regular-text" autocomplete="off" />
                                            <p class="description"><?php esc_html_e( 'Leave blank to keep the existing key.', 'imgverse' ); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </section>

                        <section id="imgv-section-attribution" class="imgv-settings-entry">
                            <div class="imgv-settings-entry__header">
                                <h2><?php esc_html_e( 'Attribution', 'imgverse' ); ?></h2>
                                <p><?php esc_html_e( 'How credit text is built and stored on imported attachments.', 'imgverse' ); ?></p>
                            </div>
                            <div class="imgv-settings-entry__body">
                                <table class="form-table" role="presentation">
                                    <tr>
                                        <th scope="row"><?php esc_html_e( 'Auto Attribution', 'imgverse' ); ?></th>
                                        <td>
                                            <label for="imgv-auto-attribution">
                                                <input type="checkbox" id="imgv-auto-attribution" name="imgv_settings[auto_attribution]" value="1" <?php checked( ! isset( $settings['auto_attribution'] ) || ! empty( $settings['auto_attribution'] ) ); ?> />
                                                <?php esc_html_e( 'Automatically add credit as the attachment caption when importing.', 'imgverse' ); ?>
                                            </label>
                                            <p class="description"><?php esc_html_e( 'When enabled, IMGVerse builds a clean credit from your style/template. Provider legal dumps and raw Creative Commons URLs are never stored.', 'imgverse' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="imgv-attribution-style"><?php esc_html_e( 'Attribution Style', 'imgverse' ); ?></label></th>
                                        <td>
                                            <select id="imgv-attribution-style" name="imgv_settings[default_attribution_style]">
                                                <option value="simple" <?php selected( $settings['default_attribution_style'] ?? 'standard', 'simple' ); ?>><?php esc_html_e( 'Simple: Photo by [Creator]', 'imgverse' ); ?></option>
                                                <option value="standard" <?php selected( $settings['default_attribution_style'] ?? 'standard', 'standard' ); ?>><?php esc_html_e( 'Standard: Photo by [Creator] / [Source]', 'imgverse' ); ?></option>
                                                <option value="academic" <?php selected( $settings['default_attribution_style'] ?? 'standard', 'academic' ); ?>><?php esc_html_e( 'Academic citation', 'imgverse' ); ?></option>
                                                <option value="custom" <?php selected( $settings['default_attribution_style'] ?? 'standard', 'custom' ); ?>><?php esc_html_e( 'Custom template', 'imgverse' ); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="imgv-attribution-template"><?php esc_html_e( 'Custom Template', 'imgverse' ); ?></label></th>
                                        <td>
                                            <textarea id="imgv-attribution-template" name="imgv_settings[default_attribution_template]" rows="3" cols="50"><?php echo esc_textarea( $settings['default_attribution_template'] ?? 'Photo by {creator} / {source}' ); ?></textarea>
                                            <p class="description">
                                                <?php esc_html_e( 'Used when style is Custom. Recommended:', 'imgverse' ); ?>
                                                <code>Photo by {creator} / {source}</code>
                                            </p>
                                            <p class="description">
                                                <?php esc_html_e( 'Optional:', 'imgverse' ); ?>
                                                <code>"{title}" by {creator} / {source}</code>
                                                <?php esc_html_e( 'or with license code', 'imgverse' ); ?>
                                                <code>Photo by {creator} / {source} ({license})</code>
                                            </p>
                                            <p class="description">
                                                <?php esc_html_e( 'Only include what you want stored as the caption. Variables:', 'imgverse' ); ?>
                                                <code>{title}</code>, <code>{creator}</code>, <code>{source}</code>, <code>{license}</code>, <code>{license_url}</code>, <code>{url}</code>
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="imgv-attribution-placement"><?php esc_html_e( 'Placement', 'imgverse' ); ?></label></th>
                                        <td>
                                            <select id="imgv-attribution-placement" name="imgv_settings[attribution_placement]">
                                                <option value="caption" <?php selected( $settings['attribution_placement'] ?? 'caption', 'caption' ); ?>><?php esc_html_e( 'Image caption (excerpt)', 'imgverse' ); ?></option>
                                                <option value="description" <?php selected( $settings['attribution_placement'] ?? 'caption', 'description' ); ?>><?php esc_html_e( 'Image description (content)', 'imgverse' ); ?></option>
                                                <option value="custom_field" <?php selected( $settings['attribution_placement'] ?? 'caption', 'custom_field' ); ?>><?php esc_html_e( 'Custom field (_imgv_attribution)', 'imgverse' ); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </section>

                        <section id="imgv-section-import" class="imgv-settings-entry">
                            <div class="imgv-settings-entry__header">
                                <h2><?php esc_html_e( 'Import', 'imgverse' ); ?></h2>
                                <p><?php esc_html_e( 'Download limits, quality, naming, and default insert size.', 'imgverse' ); ?></p>
                            </div>
                            <div class="imgv-settings-entry__body">
                                <table class="form-table" role="presentation">
                                    <tr>
                                        <th scope="row"><label for="imgv-default-image-size"><?php esc_html_e( 'Default Insert Size', 'imgverse' ); ?></label></th>
                                        <td>
                                            <select id="imgv-default-image-size" name="imgv_settings[default_image_size]">
                                                <option value="thumbnail" <?php selected( $settings['default_image_size'] ?? 'large', 'thumbnail' ); ?>><?php esc_html_e( 'Thumbnail', 'imgverse' ); ?></option>
                                                <option value="medium" <?php selected( $settings['default_image_size'] ?? 'large', 'medium' ); ?>><?php esc_html_e( 'Medium', 'imgverse' ); ?></option>
                                                <option value="large" <?php selected( $settings['default_image_size'] ?? 'large', 'large' ); ?>><?php esc_html_e( 'Large', 'imgverse' ); ?></option>
                                                <option value="full" <?php selected( $settings['default_image_size'] ?? 'large', 'full' ); ?>><?php esc_html_e( 'Full Size', 'imgverse' ); ?></option>
                                            </select>
                                            <p class="description"><?php esc_html_e( 'Default size when inserting from the sidebar. Imports always download the full remote file first.', 'imgverse' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="imgv-max-download-width"><?php esc_html_e( 'Max Download Width', 'imgverse' ); ?></label></th>
                                        <td>
                                            <input type="number" id="imgv-max-download-width" name="imgv_settings[max_download_width]" value="<?php echo esc_attr( $settings['max_download_width'] ?? 2400 ); ?>" min="0" max="10000" />
                                            <p class="description"><?php esc_html_e( 'Resize after download. Set width and height to 0 to disable.', 'imgverse' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="imgv-max-download-height"><?php esc_html_e( 'Max Download Height', 'imgverse' ); ?></label></th>
                                        <td>
                                            <input type="number" id="imgv-max-download-height" name="imgv_settings[max_download_height]" value="<?php echo esc_attr( $settings['max_download_height'] ?? 2400 ); ?>" min="0" max="10000" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="imgv-image-quality"><?php esc_html_e( 'Image Quality', 'imgverse' ); ?></label></th>
                                        <td>
                                            <input type="range" id="imgv-image-quality" name="imgv_settings[image_quality]" min="60" max="100" value="<?php echo esc_attr( $settings['image_quality'] ?? 90 ); ?>" />
                                            <span class="imgv-quality-value"><?php echo esc_html( (string) ( $settings['image_quality'] ?? 90 ) ); ?>%</span>
                                            <p class="description"><?php esc_html_e( 'JPEG quality when a resize is applied.', 'imgverse' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="imgv-file-naming"><?php esc_html_e( 'File Naming', 'imgverse' ); ?></label></th>
                                        <td>
                                            <select id="imgv-file-naming" name="imgv_settings[file_naming]">
                                                <option value="title" <?php selected( $settings['file_naming'] ?? 'title', 'title' ); ?>><?php esc_html_e( 'Use image title', 'imgverse' ); ?></option>
                                                <option value="original" <?php selected( $settings['file_naming'] ?? 'title', 'original' ); ?>><?php esc_html_e( 'Keep original filename', 'imgverse' ); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </section>

                        <section id="imgv-section-cache" class="imgv-settings-entry">
                            <div class="imgv-settings-entry__header">
                                <h2><?php esc_html_e( 'Cache', 'imgverse' ); ?></h2>
                                <p><?php esc_html_e( 'How long successful search results are cached.', 'imgverse' ); ?></p>
                            </div>
                            <div class="imgv-settings-entry__body">
                                <table class="form-table" role="presentation">
                                    <tr>
                                        <th scope="row"><label for="imgv-cache-duration"><?php esc_html_e( 'Cache Duration', 'imgverse' ); ?></label></th>
                                        <td>
                                            <input type="number" id="imgv-cache-duration" name="imgv_settings[cache_duration]" value="<?php echo esc_attr( $settings['cache_duration'] ?? 1800 ); ?>" min="300" max="86400" />
                                            <span><?php esc_html_e( 'seconds (5 minutes to 24 hours)', 'imgverse' ); ?></span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </section>

                        <div class="imgv-settings__footer">
                            <?php submit_button( __( 'Save Settings', 'imgverse' ) ); ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Display admin notices
     * 
     * @since 1.0.0
     */
    public function admin_notices() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'settings_page_imgverse-settings' !== $screen->id ) {
			return;
		}

        // Check if cache is working properly
        $cache = new IMGV_Cache();
        $stats = $cache->get_stats();
        
        if ($stats['method'] === 'database' && $stats['hit_rate'] < 50) {
            echo '<div class="notice notice-warning"><p>';
            printf(
                __('IMGVerse: Consider enabling Redis or Memcached for better performance. Current cache hit rate: %s%%', 'imgverse'),
                round($stats['hit_rate'], 1)
            );
            echo '</p></div>';
        }
    }
    
    /**
     * Get images attached to a specific post
     * 
     * @since 1.0.0
     * @param int $post_id Post ID
     * @return array Array of attached images
     */
    public static function get_post_images($post_id) {
        $args = array(
            'post_type' => 'attachment',
            'post_parent' => $post_id,
            'post_mime_type' => 'image',
            'meta_query' => array(
                array(
                    'key' => '_imgv_imported',
                    'value' => true,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        return get_posts($args);
    }
    
    /**
     * Get import statistics
     * 
     * @since 1.0.0
     * @return array Import statistics
     */
    public static function get_import_stats() {
        global $wpdb;
        
        $total_imports = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_imgv_imported' 
            AND meta_value = '1'
        ");
        
        $recent_imports = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_imgv_imported' 
            AND pm.meta_value = '1'
            AND p.post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        
        return array(
            'total_imports' => intval($total_imports),
            'recent_imports' => intval($recent_imports)
        );
    }
}

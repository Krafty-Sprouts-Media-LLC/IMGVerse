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
        
        // Search & Display Settings
        $sanitized['default_search_behavior'] = sanitize_text_field($input['default_search_behavior'] ?? 'all_sources');
        $sanitized['results_per_page'] = intval($input['results_per_page'] ?? 20);
        $sanitized['enable_infinite_scroll'] = !empty($input['enable_infinite_scroll']);
        $sanitized['grid_columns'] = intval($input['grid_columns'] ?? 4);
        
        // Attribution Settings
        $sanitized['default_attribution_template'] = wp_kses_post($input['default_attribution_template'] ?? '"{title}" by {creator} from {source}');
        $sanitized['default_attribution_style'] = sanitize_text_field($input['default_attribution_style'] ?? 'standard');
        $sanitized['attribution_placement'] = sanitize_text_field($input['attribution_placement'] ?? 'caption');
        $sanitized['link_behavior'] = sanitize_text_field($input['link_behavior'] ?? 'source');
        
        // Import Settings
        $sanitized['default_image_size'] = sanitize_text_field($input['default_image_size'] ?? 'large');
        $sanitized['image_quality'] = intval($input['image_quality'] ?? 90);
        $sanitized['file_naming'] = sanitize_text_field($input['file_naming'] ?? 'title');
        $sanitized['import_location'] = sanitize_text_field($input['import_location'] ?? 'default');
        $sanitized['duplicate_handling'] = sanitize_text_field($input['duplicate_handling'] ?? 'rename');
        
        // API & Performance Settings
        $sanitized['cache_duration'] = intval($input['cache_duration'] ?? 1800);
        $sanitized['max_cache_size'] = intval($input['max_cache_size'] ?? 10485760);
        $sanitized['request_timeout'] = intval($input['request_timeout'] ?? 30);
        $sanitized['concurrent_requests'] = intval($input['concurrent_requests'] ?? 3);
        $sanitized['rate_limiting'] = intval($input['rate_limiting'] ?? 60);
        $sanitized['cache_strategy'] = sanitize_text_field($input['cache_strategy'] ?? 'auto');
        $sanitized['background_processing'] = !empty($input['background_processing']);
        
        return $sanitized;
    }
    
    /**
     * Render settings page
     * 
     * @since 1.0.0
     */
    public function render_settings_page() {
        $settings = get_option('imgv_settings', array());
        $cache_stats = get_option('imgv_cache_stats', array());
        ?>
        <div class="wrap">
            <h1><?php _e('IMGVerse Settings', 'imgverse'); ?></h1>
            
            <div class="imgv-admin-header">
                <div class="imgv-admin-info">
                    <h2><?php _e('Creative Commons Image Search & Import', 'imgverse'); ?></h2>
                    <p><?php _e('Search and import Creative Commons images from Openverse directly into your WordPress posts and pages.', 'imgverse'); ?></p>
                </div>
                <div class="imgv-admin-actions">
                    <button type="button" id="imgv-clear-cache" class="button button-secondary">
                        <?php _e('Clear Cache', 'imgverse'); ?>
                    </button>
                    <button type="button" id="imgv-test-api" class="button button-secondary">
                        <?php _e('Test API Connection', 'imgverse'); ?>
                    </button>
                </div>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields('imgv_settings'); ?>
                
                <div class="imgv-settings-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#search-display" class="nav-tab nav-tab-active"><?php _e('Search & Display', 'imgverse'); ?></a>
                        <a href="#attribution" class="nav-tab"><?php _e('Attribution', 'imgverse'); ?></a>
                        <a href="#import" class="nav-tab"><?php _e('Import', 'imgverse'); ?></a>
                        <a href="#performance" class="nav-tab"><?php _e('Performance', 'imgverse'); ?></a>
                        <a href="#analytics" class="nav-tab"><?php _e('Analytics', 'imgverse'); ?></a>
                    </nav>
                    
                    <div id="search-display" class="tab-content active">
                        <h3><?php _e('Search & Display Settings', 'imgverse'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Default Search Behavior', 'imgverse'); ?></th>
                                <td>
                                    <select name="imgv_settings[default_search_behavior]">
                                        <option value="all_sources" <?php selected($settings['default_search_behavior'] ?? 'all_sources', 'all_sources'); ?>>
                                            <?php _e('Search All Sources', 'imgverse'); ?>
                                        </option>
                                        <option value="specific_source" <?php selected($settings['default_search_behavior'] ?? '', 'specific_source'); ?>>
                                            <?php _e('Search Specific Source', 'imgverse'); ?>
                                        </option>
                                    </select>
                                    <p class="description"><?php _e('Whether to search all sources by default or allow users to select specific sources.', 'imgverse'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Results Per Page', 'imgverse'); ?></th>
                                <td>
                                    <input type="number" name="imgv_settings[results_per_page]" value="<?php echo esc_attr($settings['results_per_page'] ?? 20); ?>" min="10" max="100" />
                                    <p class="description"><?php _e('Number of images to load initially.', 'imgverse'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Infinite Scroll', 'imgverse'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="imgv_settings[enable_infinite_scroll]" value="1" <?php checked(!empty($settings['enable_infinite_scroll'])); ?> />
                                        <?php _e('Enable infinite scroll for seamless browsing', 'imgverse'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Grid Columns', 'imgverse'); ?></th>
                                <td>
                                    <input type="number" name="imgv_settings[grid_columns]" value="<?php echo esc_attr($settings['grid_columns'] ?? 4); ?>" min="2" max="6" />
                                    <p class="description"><?php _e('Number of columns in the image grid.', 'imgverse'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div id="attribution" class="tab-content">
                        <h3><?php _e('Attribution Settings', 'imgverse'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Attribution Template', 'imgverse'); ?></th>
                                <td>
                                    <textarea name="imgv_settings[default_attribution_template]" rows="3" cols="50"><?php echo esc_textarea($settings['default_attribution_template'] ?? '"{title}" by {creator} from {source}'); ?></textarea>
                                    <p class="description">
                                        <?php _e('Template variables:', 'imgverse'); ?> 
                                        <code>{title}</code>, <code>{creator}</code>, <code>{source}</code>, <code>{license}</code>, <code>{license_url}</code>, <code>{url}</code>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Default Attribution Style', 'imgverse'); ?></th>
                                <td>
                                    <select name="imgv_settings[default_attribution_style]">
                                        <option value="simple" <?php selected($settings['default_attribution_style'] ?? 'standard', 'simple'); ?>>
                                            <?php _e('Simple: "Image by [Creator]"', 'imgverse'); ?>
                                        </option>
                                        <option value="standard" <?php selected($settings['default_attribution_style'] ?? 'standard', 'standard'); ?>>
                                            <?php _e('Standard: "[Title]" by [Creator] from [Source]', 'imgverse'); ?>
                                        </option>
                                        <option value="academic" <?php selected($settings['default_attribution_style'] ?? 'standard', 'academic'); ?>>
                                            <?php _e('Academic: Full citation format', 'imgverse'); ?>
                                        </option>
                                        <option value="custom" <?php selected($settings['default_attribution_style'] ?? 'standard', 'custom'); ?>>
                                            <?php _e('Custom: Use template above', 'imgverse'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Attribution Placement', 'imgverse'); ?></th>
                                <td>
                                    <select name="imgv_settings[attribution_placement]">
                                        <option value="caption" <?php selected($settings['attribution_placement'] ?? 'caption', 'caption'); ?>>
                                            <?php _e('Image Caption', 'imgverse'); ?>
                                        </option>
                                        <option value="description" <?php selected($settings['attribution_placement'] ?? 'caption', 'description'); ?>>
                                            <?php _e('Image Description', 'imgverse'); ?>
                                        </option>
                                        <option value="custom_field" <?php selected($settings['attribution_placement'] ?? 'caption', 'custom_field'); ?>>
                                            <?php _e('Custom Field', 'imgverse'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Link Behavior', 'imgverse'); ?></th>
                                <td>
                                    <select name="imgv_settings[link_behavior]">
                                        <option value="source" <?php selected($settings['link_behavior'] ?? 'source', 'source'); ?>>
                                            <?php _e('Link to Source', 'imgverse'); ?>
                                        </option>
                                        <option value="creator" <?php selected($settings['link_behavior'] ?? 'source', 'creator'); ?>>
                                            <?php _e('Link to Creator', 'imgverse'); ?>
                                        </option>
                                        <option value="license" <?php selected($settings['link_behavior'] ?? 'source', 'license'); ?>>
                                            <?php _e('Link to License', 'imgverse'); ?>
                                        </option>
                                        <option value="none" <?php selected($settings['link_behavior'] ?? 'source', 'none'); ?>>
                                            <?php _e('No Links', 'imgverse'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div id="import" class="tab-content">
                        <h3><?php _e('Import Settings', 'imgverse'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Default Image Size', 'imgverse'); ?></th>
                                <td>
                                    <select name="imgv_settings[default_image_size]">
                                        <option value="thumbnail" <?php selected($settings['default_image_size'] ?? 'large', 'thumbnail'); ?>>
                                            <?php _e('Thumbnail', 'imgverse'); ?>
                                        </option>
                                        <option value="medium" <?php selected($settings['default_image_size'] ?? 'large', 'medium'); ?>>
                                            <?php _e('Medium', 'imgverse'); ?>
                                        </option>
                                        <option value="large" <?php selected($settings['default_image_size'] ?? 'large', 'large'); ?>>
                                            <?php _e('Large', 'imgverse'); ?>
                                        </option>
                                        <option value="full" <?php selected($settings['default_image_size'] ?? 'large', 'full'); ?>>
                                            <?php _e('Full Size', 'imgverse'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Image Quality', 'imgverse'); ?></th>
                                <td>
                                    <input type="range" name="imgv_settings[image_quality]" min="60" max="100" value="<?php echo esc_attr($settings['image_quality'] ?? 90); ?>" />
                                    <span class="imgv-quality-value"><?php echo esc_html($settings['image_quality'] ?? 90); ?>%</span>
                                    <p class="description"><?php _e('Compression quality for imported images.', 'imgverse'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('File Naming', 'imgverse'); ?></th>
                                <td>
                                    <select name="imgv_settings[file_naming]">
                                        <option value="title" <?php selected($settings['file_naming'] ?? 'title', 'title'); ?>>
                                            <?php _e('Use Image Title', 'imgverse'); ?>
                                        </option>
                                        <option value="original" <?php selected($settings['file_naming'] ?? 'title', 'original'); ?>>
                                            <?php _e('Keep Original Filename', 'imgverse'); ?>
                                        </option>
                                        <option value="custom" <?php selected($settings['file_naming'] ?? 'title', 'custom'); ?>>
                                            <?php _e('Custom Pattern', 'imgverse'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Import Location', 'imgverse'); ?></th>
                                <td>
                                    <select name="imgv_settings[import_location]">
                                        <option value="default" <?php selected($settings['import_location'] ?? 'default', 'default'); ?>>
                                            <?php _e('Default WordPress Media Folder', 'imgverse'); ?>
                                        </option>
                                        <option value="imgverse" <?php selected($settings['import_location'] ?? 'default', 'imgverse'); ?>>
                                            <?php _e('IMGVerse Subfolder', 'imgverse'); ?>
                                        </option>
                                        <option value="custom" <?php selected($settings['import_location'] ?? 'default', 'custom'); ?>>
                                            <?php _e('Custom Subfolder', 'imgverse'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Duplicate Handling', 'imgverse'); ?></th>
                                <td>
                                    <select name="imgv_settings[duplicate_handling]">
                                        <option value="skip" <?php selected($settings['duplicate_handling'] ?? 'rename', 'skip'); ?>>
                                            <?php _e('Skip Duplicates', 'imgverse'); ?>
                                        </option>
                                        <option value="rename" <?php selected($settings['duplicate_handling'] ?? 'rename', 'rename'); ?>>
                                            <?php _e('Rename with Number', 'imgverse'); ?>
                                        </option>
                                        <option value="overwrite" <?php selected($settings['duplicate_handling'] ?? 'rename', 'overwrite'); ?>>
                                            <?php _e('Overwrite Existing', 'imgverse'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div id="performance" class="tab-content">
                        <h3><?php _e('Performance Settings', 'imgverse'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Cache Duration', 'imgverse'); ?></th>
                                <td>
                                    <input type="number" name="imgv_settings[cache_duration]" value="<?php echo esc_attr($settings['cache_duration'] ?? 1800); ?>" min="300" max="86400" />
                                    <span><?php _e('seconds (15 minutes to 24 hours)', 'imgverse'); ?></span>
                                    <p class="description"><?php _e('How long to cache search results.', 'imgverse'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Max Cache Size', 'imgverse'); ?></th>
                                <td>
                                    <input type="number" name="imgv_settings[max_cache_size]" value="<?php echo esc_attr($settings['max_cache_size'] ?? 10485760); ?>" min="1048576" max="104857600" />
                                    <span><?php _e('bytes (1MB to 100MB)', 'imgverse'); ?></span>
                                    <p class="description"><?php _e('Maximum size for database cache storage.', 'imgverse'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Request Timeout', 'imgverse'); ?></th>
                                <td>
                                    <input type="number" name="imgv_settings[request_timeout]" value="<?php echo esc_attr($settings['request_timeout'] ?? 30); ?>" min="5" max="120" />
                                    <span><?php _e('seconds', 'imgverse'); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Concurrent Requests', 'imgverse'); ?></th>
                                <td>
                                    <input type="number" name="imgv_settings[concurrent_requests]" value="<?php echo esc_attr($settings['concurrent_requests'] ?? 3); ?>" min="1" max="10" />
                                    <p class="description"><?php _e('Maximum simultaneous API requests.', 'imgverse'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Rate Limiting', 'imgverse'); ?></th>
                                <td>
                                    <input type="number" name="imgv_settings[rate_limiting]" value="<?php echo esc_attr($settings['rate_limiting'] ?? 60); ?>" min="10" max="300" />
                                    <span><?php _e('requests per hour', 'imgverse'); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Cache Strategy', 'imgverse'); ?></th>
                                <td>
                                    <select name="imgv_settings[cache_strategy]">
                                        <option value="auto" <?php selected($settings['cache_strategy'] ?? 'auto', 'auto'); ?>>
                                            <?php _e('Auto-detect (Recommended)', 'imgverse'); ?>
                                        </option>
                                        <option value="external" <?php selected($settings['cache_strategy'] ?? 'auto', 'external'); ?>>
                                            <?php _e('External Cache (Redis/Memcached)', 'imgverse'); ?>
                                        </option>
                                        <option value="wp_object" <?php selected($settings['cache_strategy'] ?? 'auto', 'wp_object'); ?>>
                                            <?php _e('WordPress Object Cache', 'imgverse'); ?>
                                        </option>
                                        <option value="database" <?php selected($settings['cache_strategy'] ?? 'auto', 'database'); ?>>
                                            <?php _e('Database Only', 'imgverse'); ?>
                                        </option>
                                        <option value="disabled" <?php selected($settings['cache_strategy'] ?? 'auto', 'disabled'); ?>>
                                            <?php _e('No Caching', 'imgverse'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Background Processing', 'imgverse'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="imgv_settings[background_processing]" value="1" <?php checked(!empty($settings['background_processing'])); ?> />
                                        <?php _e('Enable background processing for heavy operations', 'imgverse'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <h4><?php _e('Cache Statistics', 'imgverse'); ?></h4>
                        <div class="imgv-cache-stats">
                            <p><strong><?php _e('Cache Method:', 'imgverse'); ?></strong> <span id="cache-method"><?php _e('Detecting...', 'imgverse'); ?></span></p>
                            <p><strong><?php _e('Cache Hit Rate:', 'imgverse'); ?></strong> <span id="cache-hit-rate"><?php _e('Calculating...', 'imgverse'); ?></span></p>
                            <p><strong><?php _e('Cache Size:', 'imgverse'); ?></strong> <span id="cache-size"><?php _e('Calculating...', 'imgverse'); ?></span></p>
                        </div>
                    </div>
                    
                    <div id="analytics" class="tab-content">
                        <h3><?php _e('Usage Analytics', 'imgverse'); ?></h3>
                        <p><?php _e('Track your usage and performance metrics.', 'imgverse'); ?></p>
                        
                        <h4><?php _e('Import History', 'imgverse'); ?></h4>
                        <div id="import-history">
                            <p><?php _e('No imports yet.', 'imgverse'); ?></p>
                        </div>
                        
                        <h4><?php _e('Popular Searches', 'imgverse'); ?></h4>
                        <div id="popular-searches">
                            <p><?php _e('No searches yet.', 'imgverse'); ?></p>
                        </div>
                    </div>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <style>
        .imgv-admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .imgv-settings-tabs {
            margin-top: 20px;
        }
        
        .tab-content {
            display: none;
            padding: 20px 0;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .imgv-cache-stats {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        
        .imgv-quality-value {
            margin-left: 10px;
            font-weight: bold;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').removeClass('active');
                $(target).addClass('active');
            });
            
            // Quality slider
            $('input[name="imgv_settings[image_quality]"]').on('input', function() {
                $(this).siblings('.imgv-quality-value').text($(this).val() + '%');
            });
            
            // Clear cache
            $('#imgv-clear-cache').on('click', function() {
                if (confirm('<?php _e('Are you sure you want to clear all cache?', 'imgverse'); ?>')) {
                    $.post(ajaxurl, {
                        action: 'imgv_clear_cache',
                        nonce: '<?php echo wp_create_nonce('imgv_nonce'); ?>'
                    }, function(response) {
                        alert(response.message || '<?php _e('Cache cleared successfully.', 'imgverse'); ?>');
                        location.reload();
                    });
                }
            });
            
            // Test API
            $('#imgv-test-api').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('<?php _e('Testing...', 'imgverse'); ?>');
                
                $.post(ajaxurl, {
                    action: 'imgv_search',
                    nonce: '<?php echo wp_create_nonce('imgv_nonce'); ?>',
                    query: 'test',
                    page: 1
                }, function(response) {
                    if (response.success) {
                        alert('<?php _e('API connection successful!', 'imgverse'); ?>');
                    } else {
                        alert('<?php _e('API connection failed:', 'imgverse'); ?> ' + (response.message || '<?php _e('Unknown error', 'imgverse'); ?>'));
                    }
                }).always(function() {
                    $btn.prop('disabled', false).text('<?php _e('Test API Connection', 'imgverse'); ?>');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Display admin notices
     * 
     * @since 1.0.0
     */
    public function admin_notices() {
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

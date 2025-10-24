<?php
/**
 * IMGVerse Media Tab Handler
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
 * IMGV_Media_Tab Class
 * 
 * Handles WordPress media modal integration
 * 
 * @since 1.0.0
 */
class IMGV_Media_Tab {
    
    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct() {
        add_action('print_media_templates', array($this, 'print_media_templates'));
        add_action('wp_enqueue_media', array($this, 'enqueue_media_scripts'));
    }
    
    /**
     * Enqueue media scripts
     * 
     * @since 1.0.0
     */
    public function enqueue_media_scripts() {
        wp_enqueue_script(
            'imgv-media-tab',
            IMGV_PLUGIN_URL . 'assets/js/imgv-media-tab.js',
            array('media-views', 'jquery'),
            IMGV_VERSION,
            true
        );
        
        wp_enqueue_style(
            'imgv-media-tab',
            IMGV_PLUGIN_URL . 'assets/css/imgv-media-tab.css',
            array(),
            IMGV_VERSION
        );
        
        wp_localize_script('imgv-media-tab', 'imgv_media', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('imgv_nonce'),
            'post_id' => get_the_ID(),
            'strings' => array(
                'tab_title' => __('IMGVerse', 'imgverse'),
                'search_placeholder' => __('Search for images...', 'imgverse'),
                'searching' => __('Searching...', 'imgverse'),
                'import' => __('Import Image', 'imgverse'),
                'importing' => __('Importing...', 'imgverse'),
                'error' => __('Error occurred. Please try again.', 'imgverse'),
                'success' => __('Image imported successfully!', 'imgverse'),
                'no_results' => __('No images found. Try different search terms.', 'imgverse'),
                'all_sources' => __('All Sources', 'imgverse'),
                'all_licenses' => __('All Licenses', 'imgverse'),
                'source_label' => __('Source:', 'imgverse'),
                'license_label' => __('License:', 'imgverse'),
                'search_button' => __('Search', 'imgverse'),
                'load_more' => __('Load More', 'imgverse'),
                'loading_more' => __('Loading more images...', 'imgverse'),
                'size_options' => array(
                    'thumbnail' => __('Thumbnail', 'imgverse'),
                    'medium' => __('Medium', 'imgverse'),
                    'large' => __('Large', 'imgverse'),
                    'full' => __('Full Size', 'imgverse')
                )
            )
        ));
    }
    
    /**
     * Print media templates
     * 
     * @since 1.0.0
     */
    public function print_media_templates() {
        ?>
        <script type="text/html" id="tmpl-imgv-browser">
            <div class="imgv-browser">
                <div class="imgv-toolbar">
                    <div class="imgv-search-form">
                        <input type="text" id="imgv-search-input" placeholder="<?php _e('Search for images...', 'imgverse'); ?>" />
                        <div class="imgv-filters">
                            <label>
                                <?php _e('Source:', 'imgverse'); ?>
                                <select id="imgv-source">
                                    <option value=""><?php _e('All Sources', 'imgverse'); ?></option>
                                    <option value="flickr">Flickr</option>
                                    <option value="wikimedia">Wikimedia Commons</option>
                                    <option value="inaturalist">iNaturalist</option>
                                    <option value="met">Metropolitan Museum</option>
                                    <option value="nypl">NYPL</option>
                                    <option value="rawpixel">Rawpixel</option>
                                    <option value="smithsonian">Smithsonian</option>
                                </select>
                            </label>
                            <label>
                                <?php _e('License:', 'imgverse'); ?>
                                <select id="imgv-license">
                                    <option value=""><?php _e('All Licenses', 'imgverse'); ?></option>
                                    <option value="cc0">CC0</option>
                                    <option value="by">CC BY</option>
                                    <option value="by-sa">CC BY-SA</option>
                                    <option value="by-nc">CC BY-NC</option>
                                    <option value="by-nc-sa">CC BY-NC-SA</option>
                                    <option value="by-nc-nd">CC BY-NC-ND</option>
                                    <option value="by-nd">CC BY-ND</option>
                                </select>
                            </label>
                            <button type="button" id="imgv-search-btn" class="button button-primary">
                                <?php _e('Search', 'imgverse'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="imgv-content">
                    <div id="imgv-loading" class="imgv-loading" style="display: none;">
                        <div class="imgv-spinner"></div>
                        <p><?php _e('Searching images...', 'imgverse'); ?></p>
                    </div>
                    <div id="imgv-results" class="imgv-results"></div>
                    <div id="imgv-pagination" class="imgv-pagination"></div>
                </div>
            </div>
        </script>

        <script type="text/html" id="tmpl-imgv-image">
            <div class="imgv-image" data-id="{{ data.id }}">
                <div class="imgv-image-preview">
                    <img src="{{ data.thumbnail || data.url }}" alt="{{ data.title }}" />
                    <div class="imgv-image-overlay">
                        <div class="imgv-image-actions">
                            <button type="button" class="button button-secondary imgv-preview-btn" data-image="{{ JSON.stringify(data) }}">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                            <button type="button" class="button button-primary imgv-import-btn" data-image="{{ JSON.stringify(data) }}">
                                <span class="dashicons dashicons-download"></span>
                                <?php _e('Import', 'imgverse'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="imgv-image-details">
                    <h4 class="imgv-image-title">{{ data.title }}</h4>
                    <div class="imgv-image-meta">
                        <div class="imgv-meta-item">
                            <strong><?php _e('Creator:', 'imgverse'); ?></strong> {{ data.creator }}
                        </div>
                        <div class="imgv-meta-item">
                            <strong><?php _e('Source:', 'imgverse'); ?></strong> {{ data.source }}
                        </div>
                        <div class="imgv-meta-item">
                            <strong><?php _e('License:', 'imgverse'); ?></strong> {{ data.license.toUpperCase() }}
                        </div>
                    </div>
                    <div class="imgv-size-selector">
                        <label for="imgv-size-{{ data.id }}">
                            <?php _e('Size:', 'imgverse'); ?>
                        </label>
                        <select id="imgv-size-{{ data.id }}" class="imgv-size-select">
                            <option value="thumbnail"><?php _e('Thumbnail', 'imgverse'); ?></option>
                            <option value="medium"><?php _e('Medium', 'imgverse'); ?></option>
                            <option value="large" selected><?php _e('Large', 'imgverse'); ?></option>
                            <option value="full"><?php _e('Full Size', 'imgverse'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </script>

        <script type="text/html" id="tmpl-imgv-preview">
            <div class="imgv-preview-modal">
                <div class="imgv-preview-content">
                    <div class="imgv-preview-header">
                        <h3>{{ data.title }}</h3>
                        <button type="button" class="imgv-close-preview">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                    <div class="imgv-preview-body">
                        <img src="{{ data.url }}" alt="{{ data.title }}" />
                        <div class="imgv-preview-details">
                            <div class="imgv-preview-meta">
                                <p><strong><?php _e('Creator:', 'imgverse'); ?></strong> {{ data.creator }}</p>
                                <p><strong><?php _e('Source:', 'imgverse'); ?></strong> {{ data.source }}</p>
                                <p><strong><?php _e('License:', 'imgverse'); ?></strong> {{ data.license.toUpperCase() }}</p>
                                <p><strong><?php _e('Dimensions:', 'imgverse'); ?></strong> {{ data.width }} × {{ data.height }}</p>
                            </div>
                            <div class="imgv-preview-attribution">
                                <h4><?php _e('Attribution:', 'imgverse'); ?></h4>
                                <div class="imgv-attribution-preview">{{{ data.attribution }}}</div>
                            </div>
                        </div>
                    </div>
                    <div class="imgv-preview-footer">
                        <div class="imgv-size-selector">
                            <label for="imgv-preview-size">
                                <?php _e('Import Size:', 'imgverse'); ?>
                            </label>
                            <select id="imgv-preview-size" class="imgv-size-select">
                                <option value="thumbnail"><?php _e('Thumbnail', 'imgverse'); ?></option>
                                <option value="medium"><?php _e('Medium', 'imgverse'); ?></option>
                                <option value="large" selected><?php _e('Large', 'imgverse'); ?></option>
                                <option value="full"><?php _e('Full Size', 'imgverse'); ?></option>
                            </select>
                        </div>
                        <button type="button" class="button button-primary imgv-import-from-preview" data-image="{{ JSON.stringify(data) }}">
                            <?php _e('Import Image', 'imgverse'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </script>
        <?php
    }
}

<?php
/**
 * IMGVerse Block Editor Integration
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
 * IMGV_Block_Editor Class
 * 
 * Handles Gutenberg block editor integration
 * 
 * @since 1.0.0
 */
class IMGV_Block_Editor {
    
    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct() {
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
    }
    
    /**
     * Enqueue block editor assets
     * 
     * @since 1.0.0
     */
    public function enqueue_block_editor_assets() {
        if (!function_exists('get_current_screen')) {
            return;
        }
        
        $screen = get_current_screen();
        if (!$screen || !$screen->is_block_editor) {
            return;
        }
        
        wp_enqueue_script(
            'imgv-block-editor',
            IMGV_PLUGIN_URL . 'assets/js/imgv-block-editor.js',
            array('wp-plugins', 'wp-edit-post', 'wp-i18n', 'wp-element', 'wp-components', 'wp-data', 'wp-blocks'),
            IMGV_VERSION,
            true
        );
        
        wp_enqueue_style(
            'imgv-block-editor',
            IMGV_PLUGIN_URL . 'assets/css/imgv-block-editor.css',
            array(),
            IMGV_VERSION
        );
        
        wp_localize_script('imgv-block-editor', 'imgv_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('imgv_nonce'),
            'post_id' => get_the_ID(),
            'strings' => array(
                'panel_title' => __('IMGVerse Images', 'imgverse'),
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
                'size_options' => array(
                    'thumbnail' => __('Thumbnail', 'imgverse'),
                    'medium' => __('Medium', 'imgverse'),
                    'large' => __('Large', 'imgverse'),
                    'full' => __('Full Size', 'imgverse')
                )
            )
        ));
        
        wp_set_script_translations('imgv-block-editor', 'imgverse');
    }
}

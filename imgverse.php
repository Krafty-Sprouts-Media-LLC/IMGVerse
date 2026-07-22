<?php
/**
 * Plugin Name: IMGVerse
 * Plugin URI: https://kraftysprouts.com/imgverse
 * Description: Search and insert Creative Commons images from Openverse directly into your WordPress posts and pages.
 * Version: 2.0.0
 * Author: Krafty Sprouts Media, LLC
 * Author URI: https://kraftysprouts.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: imgverse
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package IMGVerse
 * @author Krafty Sprouts Media, LLC
 * @version 2.0.0
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('IMGV_VERSION', '2.0.0');
define('IMGV_PLUGIN_FILE', __FILE__);
define('IMGV_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IMGV_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('IMGV_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('IMGV_API_URL', 'https://api.openverse.org/v1/');
define('IMGV_CACHE_PREFIX', 'imgv_cache_');
define('IMGV_CACHE_TABLE', 'imgv_cache');

/**
 * Main IMGVerse Plugin Class
 * 
 * @since 1.0.0
 */
class IMGV_Core {
    
    /**
     * Plugin instance
     * 
     * @since 1.0.0
     * @var IMGV_Core
     */
    private static $instance = null;
    
    /**
     * Plugin components
     * 
     * @since 1.0.0
     * @var array
     */
    private $components = array();
    
    /**
     * Get plugin instance
     * 
     * @since 1.0.0
     * @return IMGV_Core
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_components();
    }
    
    /**
     * Initialize WordPress hooks
     * 
     * @since 1.0.0
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_init', array($this, 'check_requirements'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        
        // AJAX hooks
        add_action('wp_ajax_imgv_search', array($this, 'ajax_search_images'));
        add_action('wp_ajax_imgv_import', array($this, 'ajax_import_image'));
        add_action('wp_ajax_imgv_get_sources', array($this, 'ajax_get_sources'));
        add_action('wp_ajax_imgv_clear_cache', array($this, 'ajax_clear_cache'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Uninstall hook
        register_uninstall_hook(__FILE__, array('IMGV_Core', 'uninstall'));
    }
    
    /**
     * Initialize plugin
     * 
     * @since 1.0.0
     */
    public function init() {
        // Create cache table if it doesn't exist
        $this->create_cache_table();
        
        // Initialize components
        do_action('imgv_init');
    }
    
    /**
     * Load plugin textdomain
     * 
     * @since 1.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain('imgverse', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Check system requirements
     * 
     * @since 1.0.0
     */
    public function check_requirements() {
        $errors = array();
        
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $errors[] = __('IMGVerse requires PHP 7.4 or higher.', 'imgverse');
        }
        
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            $errors[] = __('IMGVerse requires WordPress 5.0 or higher.', 'imgverse');
        }
        
        if (!empty($errors)) {
            add_action('admin_notices', function() use ($errors) {
                foreach ($errors as $error) {
                    echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
                }
            });
        }
    }
    
    /**
     * Load plugin components
     * 
     * @since 1.0.0
     */
    private function load_components() {
        // Load required files
        require_once IMGV_PLUGIN_PATH . 'includes/class-imgv-api.php';
        require_once IMGV_PLUGIN_PATH . 'includes/class-imgv-media-tab.php';
        require_once IMGV_PLUGIN_PATH . 'includes/class-imgv-block-editor.php';
        require_once IMGV_PLUGIN_PATH . 'includes/class-imgv-cache.php';
        require_once IMGV_PLUGIN_PATH . 'includes/class-imgv-admin.php';
        
        // Initialize components
        $this->components['api'] = new IMGV_API();
        $this->components['media_tab'] = new IMGV_Media_Tab();
        $this->components['block_editor'] = new IMGV_Block_Editor();
        $this->components['cache'] = new IMGV_Cache();
        $this->components['admin'] = new IMGV_Admin();
    }
    
    /**
     * Add admin menu
     * 
     * @since 1.0.0
     */
    public function add_admin_menu() {
        add_options_page(
            __('IMGVerse Settings', 'imgverse'),
            __('IMGVerse', 'imgverse'),
            'manage_options',
            'imgverse-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page callback
     * 
     * @since 1.0.0
     */
    public function admin_page() {
        $this->components['admin']->render_settings_page();
    }
    
    /**
     * Enqueue admin scripts and styles
     * 
     * @since 1.0.0
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on relevant pages
        if (!in_array($hook, array('post.php', 'post-new.php', 'upload.php', 'settings_page_imgverse-settings'))) {
            return;
        }
        
        wp_enqueue_media();
        
        wp_enqueue_script(
            'imgv-admin',
            IMGV_PLUGIN_URL . 'assets/js/imgv-admin.js',
            array('jquery', 'media-views'),
            IMGV_VERSION,
            true
        );
        
        wp_enqueue_style(
            'imgv-admin',
            IMGV_PLUGIN_URL . 'assets/css/imgv-admin.css',
            array(),
            IMGV_VERSION
        );
        
        wp_localize_script('imgv-admin', 'imgv_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('imgv_nonce'),
            'strings' => array(
                'searching' => __('Searching...', 'imgverse'),
                'importing' => __('Importing...', 'imgverse'),
                'error' => __('Error occurred. Please try again.', 'imgverse'),
                'success' => __('Success!', 'imgverse'),
                'no_results' => __('No images found. Try different search terms.', 'imgverse'),
                'cache_cleared' => __('Cache cleared successfully.', 'imgverse'),
            )
        ));
    }
    
    /**
     * Enqueue frontend scripts and styles
     * 
     * @since 1.0.0
     */
    public function enqueue_scripts() {
        // Only load if needed
        if (!is_admin()) {
            return;
        }
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
        ));
        
        wp_set_script_translations('imgv-block-editor', 'imgverse');
    }
    
    /**
     * AJAX handler for image search
     * 
     * @since 1.0.0
     */
    public function ajax_search_images() {
        check_ajax_referer('imgv_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'imgverse'))));
        }
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        $source = sanitize_text_field($_POST['source'] ?? '');
        $license = sanitize_text_field($_POST['license'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        
        if (empty($query)) {
            wp_die(json_encode(array('success' => false, 'message' => __('Search query is required.', 'imgverse'))));
        }
        
        $results = $this->components['api']->search_images($query, $source, $license, $page);
        
        wp_die(json_encode($results));
    }
    
    /**
     * AJAX handler for image import
     * 
     * @since 1.0.0
     */
    public function ajax_import_image() {
        check_ajax_referer('imgv_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'imgverse'))));
        }
        
        $image_url = esc_url_raw($_POST['image_url'] ?? '');
        $title = sanitize_text_field($_POST['title'] ?? '');
        $attribution = wp_kses_post($_POST['attribution'] ?? '');
        $alt_text = sanitize_text_field($_POST['alt_text'] ?? '');
        $size = sanitize_text_field($_POST['size'] ?? 'full');
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (empty($image_url)) {
            wp_die(json_encode(array('success' => false, 'message' => __('Image URL is required.', 'imgverse'))));
        }
        
        $result = $this->components['api']->import_image($image_url, $title, $attribution, $alt_text, $size, $post_id);
        
        wp_die(json_encode($result));
    }
    
    /**
     * AJAX handler for getting sources
     * 
     * @since 1.0.0
     */
    public function ajax_get_sources() {
        check_ajax_referer('imgv_nonce', 'nonce');
        
        $sources = $this->components['api']->get_available_sources();
        
        wp_die(json_encode(array('success' => true, 'sources' => $sources)));
    }
    
    /**
     * AJAX handler for clearing cache
     * 
     * @since 1.0.0
     */
    public function ajax_clear_cache() {
        check_ajax_referer('imgv_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'imgverse'))));
        }
        
        $this->components['cache']->clear_all_cache();
        
        wp_die(json_encode(array('success' => true, 'message' => __('Cache cleared successfully.', 'imgverse'))));
    }
    
    /**
     * Create cache table
     * 
     * @since 1.0.0
     */
    private function create_cache_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . IMGV_CACHE_TABLE;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            cache_key varchar(255) NOT NULL,
            cache_value longtext NOT NULL,
            expires datetime NOT NULL,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY cache_key (cache_key),
            KEY expires (expires)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Plugin activation
     * 
     * @since 1.0.0
     */
    public function activate() {
        // Create cache table
        $this->create_cache_table();
        
        // Set default options
        $default_options = array(
            'default_size' => 'large',
            'default_attribution_template' => '"{title}" by {creator} from {source}',
            'cache_duration' => 1800, // 30 minutes
            'max_cache_size' => 10485760, // 10MB
            'enable_infinite_scroll' => true,
            'results_per_page' => 20,
        );
        
        add_option('imgv_settings', $default_options);
        
        // Schedule cache cleanup
        if (!wp_next_scheduled('imgv_cleanup_cache')) {
            wp_schedule_event(time(), 'daily', 'imgv_cleanup_cache');
        }
    }
    
    /**
     * Plugin deactivation
     * 
     * @since 1.0.0
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('imgv_cleanup_cache');
    }
    
    /**
     * Plugin uninstall
     * 
     * @since 1.0.0
     */
    public static function uninstall() {
        global $wpdb;
        
        // Remove cache table
        $table_name = $wpdb->prefix . IMGV_CACHE_TABLE;
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        // Remove options
        delete_option('imgv_settings');
        delete_option('imgv_cache_stats');
        
        // Clear scheduled events
        wp_clear_scheduled_hook('imgv_cleanup_cache');
    }
}

// Initialize the plugin
function imgv_init() {
    return IMGV_Core::get_instance();
}

// Start the plugin
imgv_init();

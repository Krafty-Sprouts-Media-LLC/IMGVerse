<?php
/**
 * IMGVerse Cache Handler
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
 * IMGV_Cache Class
 * 
 * Handles intelligent caching with server cache compatibility
 * 
 * @since 1.0.0
 */
class IMGV_Cache {
    
    /**
     * Cache hierarchy preference
     * 
     * @since 1.0.0
     * @var array
     */
    private $cache_hierarchy = array(
        'redis',
        'memcached', 
        'wp_object_cache',
        'database'
    );
    
    /**
     * Current cache method
     * 
     * @since 1.0.0
     * @var string
     */
    private $current_method = null;
    
    /**
     * Cache stats
     * 
     * @since 1.0.0
     * @var array
     */
    private $stats = array(
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0
    );
    
    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct() {
        $this->detect_cache_method();
        add_action('imgv_cleanup_cache', array($this, 'cleanup_expired_cache'));
    }
    
    /**
     * Detect best available cache method
     * 
     * @since 1.0.0
     */
    private function detect_cache_method() {
        // Check for Redis
        if (class_exists('Redis') && function_exists('wp_cache_get')) {
            try {
                $redis = new Redis();
                if ($redis->connect('127.0.0.1', 6379)) {
                    $this->current_method = 'redis';
                    return;
                }
            } catch (Exception $e) {
                // Redis not available
            }
        }
        
        // Check for Memcached
        if (class_exists('Memcached') && function_exists('wp_cache_get')) {
            try {
                $memcached = new Memcached();
                if ($memcached->addServer('127.0.0.1', 11211)) {
                    $this->current_method = 'memcached';
                    return;
                }
            } catch (Exception $e) {
                // Memcached not available
            }
        }
        
        // Check for WordPress object cache
        if (wp_using_ext_object_cache()) {
            $this->current_method = 'wp_object_cache';
            return;
        }
        
        // Fallback to database
        $this->current_method = 'database';
    }
    
    /**
     * Get cached value
     * 
     * @since 1.0.0
     * @param string $key Cache key
     * @return mixed|false Cached value or false if not found
     */
    public function get($key) {
        $key = $this->sanitize_key($key);
        
        switch ($this->current_method) {
            case 'redis':
                return $this->get_from_redis($key);
                
            case 'memcached':
                return $this->get_from_memcached($key);
                
            case 'wp_object_cache':
                return $this->get_from_wp_cache($key);
                
            case 'database':
            default:
                return $this->get_from_database($key);
        }
    }
    
    /**
     * Set cached value
     * 
     * @since 1.0.0
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $expiration Expiration time in seconds
     * @return bool Success status
     */
    public function set($key, $value, $expiration = 1800) {
        $key = $this->sanitize_key($key);
        $this->stats['sets']++;
        
        switch ($this->current_method) {
            case 'redis':
                return $this->set_to_redis($key, $value, $expiration);
                
            case 'memcached':
                return $this->set_to_memcached($key, $value, $expiration);
                
            case 'wp_object_cache':
                return $this->set_to_wp_cache($key, $value, $expiration);
                
            case 'database':
            default:
                return $this->set_to_database($key, $value, $expiration);
        }
    }
    
    /**
     * Delete cached value
     * 
     * @since 1.0.0
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete($key) {
        $key = $this->sanitize_key($key);
        $this->stats['deletes']++;
        
        switch ($this->current_method) {
            case 'redis':
                return $this->delete_from_redis($key);
                
            case 'memcached':
                return $this->delete_from_memcached($key);
                
            case 'wp_object_cache':
                return $this->delete_from_wp_cache($key);
                
            case 'database':
            default:
                return $this->delete_from_database($key);
        }
    }
    
    /**
     * Clear all cache
     * 
     * @since 1.0.0
     * @return bool Success status
     */
    public function clear_all_cache() {
        switch ($this->current_method) {
            case 'redis':
                return $this->clear_redis_cache();
                
            case 'memcached':
                return $this->clear_memcached_cache();
                
            case 'wp_object_cache':
                return $this->clear_wp_cache();
                
            case 'database':
            default:
                return $this->clear_database_cache();
        }
    }
    
    /**
     * Get cache statistics
     * 
     * @since 1.0.0
     * @return array Cache statistics
     */
    public function get_stats() {
        return array_merge($this->stats, array(
            'method' => $this->current_method,
            'hit_rate' => $this->calculate_hit_rate()
        ));
    }
    
    /**
     * Calculate cache hit rate
     * 
     * @since 1.0.0
     * @return float Hit rate percentage
     */
    private function calculate_hit_rate() {
        $total = $this->stats['hits'] + $this->stats['misses'];
        return $total > 0 ? ($this->stats['hits'] / $total) * 100 : 0;
    }
    
    /**
     * Redis cache methods
     */
    private function get_from_redis($key) {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            $value = $redis->get($key);
            if ($value !== false) {
                $this->stats['hits']++;
                return json_decode($value, true);
            }
        } catch (Exception $e) {
            // Fallback to database
            return $this->get_from_database($key);
        }
        $this->stats['misses']++;
        return false;
    }
    
    private function set_to_redis($key, $value, $expiration) {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            return $redis->setex($key, $expiration, json_encode($value));
        } catch (Exception $e) {
            // Fallback to database
            return $this->set_to_database($key, $value, $expiration);
        }
    }
    
    private function delete_from_redis($key) {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            return $redis->del($key);
        } catch (Exception $e) {
            // Fallback to database
            return $this->delete_from_database($key);
        }
    }
    
    private function clear_redis_cache() {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            return $redis->flushDB();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Memcached cache methods
     */
    private function get_from_memcached($key) {
        try {
            $memcached = new Memcached();
            $memcached->addServer('127.0.0.1', 11211);
            $value = $memcached->get($key);
            if ($value !== false) {
                $this->stats['hits']++;
                return $value;
            }
        } catch (Exception $e) {
            // Fallback to database
            return $this->get_from_database($key);
        }
        $this->stats['misses']++;
        return false;
    }
    
    private function set_to_memcached($key, $value, $expiration) {
        try {
            $memcached = new Memcached();
            $memcached->addServer('127.0.0.1', 11211);
            return $memcached->set($key, $value, $expiration);
        } catch (Exception $e) {
            // Fallback to database
            return $this->set_to_database($key, $value, $expiration);
        }
    }
    
    private function delete_from_memcached($key) {
        try {
            $memcached = new Memcached();
            $memcached->addServer('127.0.0.1', 11211);
            return $memcached->delete($key);
        } catch (Exception $e) {
            // Fallback to database
            return $this->delete_from_database($key);
        }
    }
    
    private function clear_memcached_cache() {
        try {
            $memcached = new Memcached();
            $memcached->addServer('127.0.0.1', 11211);
            return $memcached->flush();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * WordPress object cache methods
     */
    private function get_from_wp_cache($key) {
        $value = wp_cache_get($key, 'imgverse');
        if ($value !== false) {
            $this->stats['hits']++;
            return $value;
        }
        $this->stats['misses']++;
        return false;
    }
    
    private function set_to_wp_cache($key, $value, $expiration) {
        return wp_cache_set($key, $value, 'imgverse', $expiration);
    }
    
    private function delete_from_wp_cache($key) {
        return wp_cache_delete($key, 'imgverse');
    }
    
    private function clear_wp_cache() {
        return wp_cache_flush();
    }
    
    /**
     * Database cache methods
     */
    private function get_from_database($key) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . IMGV_CACHE_TABLE;
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT cache_value FROM $table_name WHERE cache_key = %s AND expires > NOW()",
            $key
        ));
        
        if ($result) {
            $this->stats['hits']++;
            return json_decode($result->cache_value, true);
        }
        
        $this->stats['misses']++;
        return false;
    }
    
    private function set_to_database($key, $value, $expiration) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . IMGV_CACHE_TABLE;
        $expires = date('Y-m-d H:i:s', time() + $expiration);
        $value_json = json_encode($value);
        
        // Check cache size limit
        $this->enforce_cache_size_limit();
        
        return $wpdb->replace(
            $table_name,
            array(
                'cache_key' => $key,
                'cache_value' => $value_json,
                'expires' => $expires
            ),
            array('%s', '%s', '%s')
        );
    }
    
    private function delete_from_database($key) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . IMGV_CACHE_TABLE;
        return $wpdb->delete(
            $table_name,
            array('cache_key' => $key),
            array('%s')
        );
    }
    
    private function clear_database_cache() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . IMGV_CACHE_TABLE;
        return $wpdb->query("TRUNCATE TABLE $table_name");
    }
    
    /**
     * Enforce cache size limit for database cache
     * 
     * @since 1.0.0
     */
    private function enforce_cache_size_limit() {
        global $wpdb;
        
        $settings = get_option('imgv_settings', array());
        $max_size = $settings['max_cache_size'] ?? 10485760; // 10MB default
        
        $table_name = $wpdb->prefix . IMGV_CACHE_TABLE;
        
        // Get current cache size
        $current_size = $wpdb->get_var("SELECT SUM(LENGTH(cache_value)) FROM $table_name");
        
        if ($current_size > $max_size) {
            // Remove oldest entries (LRU)
            $wpdb->query("
                DELETE FROM $table_name 
                WHERE id IN (
                    SELECT id FROM (
                        SELECT id FROM $table_name 
                        ORDER BY created ASC 
                        LIMIT 100
                    ) AS old_entries
                )
            ");
        }
    }
    
    /**
     * Cleanup expired cache entries
     * 
     * @since 1.0.0
     */
    public function cleanup_expired_cache() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . IMGV_CACHE_TABLE;
        $deleted = $wpdb->query("DELETE FROM $table_name WHERE expires < NOW()");
        
        // Update cache stats
        update_option('imgv_cache_stats', array(
            'last_cleanup' => current_time('mysql'),
            'entries_removed' => $deleted
        ));
    }
    
    /**
     * Sanitize cache key
     * 
     * @since 1.0.0
     * @param string $key Cache key
     * @return string Sanitized key
     */
    private function sanitize_key($key) {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
    }
}

/**
 * IMGVerse Admin JavaScript
 * 
 * @package IMGVerse
 * @author Krafty Sprouts Media, LLC
 * @version 1.5.0
 * @since 1.0.0
 * @last_modified 10/24/2025
 */

(function($) {
    'use strict';

    $(document).ready(function() {
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
            if (confirm(imgv_ajax.strings.confirm_clear_cache || 'Are you sure you want to clear all cache?')) {
                var $btn = $(this);
                $btn.prop('disabled', true).text('Clearing...');
                
                $.post(imgv_ajax.ajax_url, {
                    action: 'imgv_clear_cache',
                    nonce: imgv_ajax.nonce
                })
                .done(function(response) {
                    var data = typeof response === 'string' ? JSON.parse(response) : response;
                    alert(data.message || imgv_ajax.strings.cache_cleared || 'Cache cleared successfully.');
                    location.reload();
                })
                .fail(function() {
                    alert(imgv_ajax.strings.error || 'Error occurred. Please try again.');
                })
                .always(function() {
                    $btn.prop('disabled', false).text('Clear Cache');
                });
            }
        });
        
        // Test API
        $('#imgv-test-api').on('click', function() {
            var $btn = $(this);
            $btn.prop('disabled', true).text('Testing...');
            
            $.post(imgv_ajax.ajax_url, {
                action: 'imgv_search',
                nonce: imgv_ajax.nonce,
                query: 'test',
                page: 1
            })
            .done(function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success) {
                    alert(imgv_ajax.strings.api_success || 'API connection successful!');
                } else {
                    alert((imgv_ajax.strings.api_failed || 'API connection failed:') + ' ' + (data.message || imgv_ajax.strings.unknown_error || 'Unknown error'));
                }
            })
            .fail(function() {
                alert(imgv_ajax.strings.api_failed || 'API connection failed.');
            })
            .always(function() {
                $btn.prop('disabled', false).text('Test API Connection');
            });
        });
        
        // Load cache stats
        loadCacheStats();
        
        // Load analytics
        loadAnalytics();
    });
    
    function loadCacheStats() {
        // This would typically make an AJAX call to get real cache stats
        // For now, we'll simulate it
        setTimeout(function() {
            $('#cache-method').text('Database');
            $('#cache-hit-rate').text('75.2%');
            $('#cache-size').text('2.3 MB');
        }, 1000);
    }
    
    function loadAnalytics() {
        // This would typically make an AJAX call to get real analytics
        // For now, we'll show placeholder content
        setTimeout(function() {
            $('#import-history').html('<p>No imports yet.</p>');
            $('#popular-searches').html('<p>No searches yet.</p>');
        }, 1000);
    }

})(jQuery);

/**
 * IMGVerse Media Tab JavaScript
 * 
 * @package IMGVerse
 * @author Krafty Sprouts Media, LLC
 * @version 1.5.0
 * @since 1.0.0
 * @last_modified 10/24/2025
 */

(function($) {
    'use strict';

    if (typeof wp === 'undefined' || !wp.media) {
        return;
    }

    // Extend the media frame to add our custom tab
    var originalMediaFrame = wp.media.view.MediaFrame.Select;
    
    wp.media.view.MediaFrame.Select = originalMediaFrame.extend({
        initialize: function() {
            originalMediaFrame.prototype.initialize.apply(this, arguments);
            
            // Add our custom state
            this.states.add([
                new wp.media.controller.Library({
                    id: 'imgverse',
                    title: imgv_media.strings.tab_title,
                    priority: 60,
                    toolbar: 'main-insert',
                    filterable: 'uploaded',
                    library: wp.media.query({ type: 'image' }),
                    multiple: false,
                    editable: true,
                    allowLocalEdits: true
                })
            ]);
            
            // Listen for state changes
            this.on('content:create:imgverse', this.createImgverseContent, this);
            this.on('toolbar:create:main-insert', this.createToolbar, this);
        },

        createImgverseContent: function() {
            this.content.set(new ImgverseBrowser({
                controller: this,
                model: this.state()
            }));
        }
    });

    // Create the IMGVerse browser view
    var ImgverseBrowser = wp.media.View.extend({
        template: wp.template('imgv-browser'),
        className: 'imgv-browser-wrapper',

        events: {
            'click #imgv-search-btn': 'performSearch',
            'keypress #imgv-search-input': 'onSearchKeypress',
            'click .imgv-import-btn': 'importImage',
            'click .imgv-preview-btn': 'previewImage',
            'click .imgv-close-preview': 'closePreview',
            'click .imgv-import-from-preview': 'importFromPreview',
            'click .imgv-pagination button': 'changePage',
            'click .imgv-load-more': 'loadMore',
            'scroll .imgv-content': 'onScroll'
        },

        initialize: function() {
            this.currentPage = 1;
            this.totalPages = 1;
            this.isLoading = false;
            this.hasMore = true;
            this.currentQuery = '';
            this.currentSource = '';
            this.currentLicense = '';
            this.allImages = [];
            this.setupInfiniteScroll();
        },

        setupInfiniteScroll: function() {
            var self = this;
            var $content = this.$('.imgv-content');
            
            // Check if infinite scroll is enabled
            var settings = imgv_media.settings || {};
            if (settings.enable_infinite_scroll !== false) {
                this.infiniteScroll = true;
            }
        },

        onSearchKeypress: function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                this.performSearch();
            }
        },

        performSearch: function() {
            var query = this.$('#imgv-search-input').val().trim();
            var source = this.$('#imgv-source').val();
            var license = this.$('#imgv-license').val();

            if (!query) {
                alert(imgv_media.strings.search_placeholder);
                return;
            }

            this.currentPage = 1;
            this.currentQuery = query;
            this.currentSource = source;
            this.currentLicense = license;
            this.allImages = [];
            this.hasMore = true;
            
            this.searchImages(query, source, license, this.currentPage);
        },

        searchImages: function(query, source, license, page) {
            var self = this;
            
            if (this.isLoading) {
                return;
            }
            
            this.isLoading = true;
            this.$('#imgv-loading').show();
            
            if (page === 1) {
                this.$('#imgv-results').empty();
            }

            $.post(imgv_media.ajax_url, {
                action: 'imgv_search',
                nonce: imgv_media.nonce,
                query: query,
                source: source,
                license: license,
                page: page
            })
            .done(function(response) {
                self.$('#imgv-loading').hide();
                self.isLoading = false;
                
                try {
                    var data = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (data.success) {
                        if (page === 1) {
                            self.allImages = data.images;
                        } else {
                            self.allImages = self.allImages.concat(data.images);
                        }
                        
                        self.displayResults(data.images, page === 1);
                        self.totalPages = data.total_pages;
                        self.hasMore = page < data.total_pages;
                        self.updatePagination();
                    } else {
                        alert(data.message || imgv_media.strings.error);
                    }
                } catch (error) {
                    console.error('Parse error:', error);
                    alert(imgv_media.strings.error);
                }
            })
            .fail(function() {
                self.$('#imgv-loading').hide();
                self.isLoading = false;
                alert(imgv_media.strings.error);
            });
        },

        displayResults: function(images, clearExisting) {
            var $results = this.$('#imgv-results');
            
            if (clearExisting) {
                $results.empty();
            }

            if (!images || images.length === 0) {
                if (clearExisting) {
                    $results.html('<p class="no-results">' + imgv_media.strings.no_results + '</p>');
                }
                return;
            }

            images.forEach(function(image) {
                var imageHtml = wp.template('imgv-image')(image);
                $results.append(imageHtml);
            });
        },

        updatePagination: function() {
            var $pagination = this.$('#imgv-pagination');
            $pagination.empty();

            if (this.infiniteScroll && this.hasMore) {
                $pagination.html('<button class="button imgv-load-more">' + imgv_media.strings.load_more + '</button>');
            } else if (!this.infiniteScroll && this.totalPages > 1) {
                var paginationHtml = '';
                
                if (this.currentPage > 1) {
                    paginationHtml += '<button class="button prev-page" data-page="' + (this.currentPage - 1) + '">Previous</button>';
                }

                paginationHtml += '<span class="page-info">Page ' + this.currentPage + ' of ' + this.totalPages + '</span>';

                if (this.currentPage < this.totalPages) {
                    paginationHtml += '<button class="button next-page" data-page="' + (this.currentPage + 1) + '">Next</button>';
                }

                $pagination.html(paginationHtml);
            }
        },

        changePage: function(e) {
            var page = parseInt($(e.currentTarget).data('page'));
            if (page && page !== this.currentPage) {
                this.currentPage = page;
                this.searchImages(this.currentQuery, this.currentSource, this.currentLicense, page);
            }
        },

        loadMore: function() {
            if (this.hasMore && !this.isLoading) {
                this.currentPage++;
                this.searchImages(this.currentQuery, this.currentSource, this.currentLicense, this.currentPage);
            }
        },

        onScroll: function() {
            if (!this.infiniteScroll || this.isLoading || !this.hasMore) {
                return;
            }

            var $content = this.$('.imgv-content');
            var scrollTop = $content.scrollTop();
            var scrollHeight = $content[0].scrollHeight;
            var clientHeight = $content[0].clientHeight;

            // Load more when 80% scrolled
            if (scrollTop + clientHeight >= scrollHeight * 0.8) {
                this.loadMore();
            }
        },

        previewImage: function(e) {
            var $button = $(e.currentTarget);
            var imageData = JSON.parse($button.attr('data-image'));
            
            var previewHtml = wp.template('imgv-preview')(imageData);
            $('body').append(previewHtml);
            
            // Close preview when clicking outside
            $('.imgv-preview-modal').on('click', function(e) {
                if (e.target === this) {
                    $('.imgv-preview-modal').remove();
                }
            });
        },

        closePreview: function() {
            $('.imgv-preview-modal').remove();
        },

        importFromPreview: function(e) {
            var $button = $(e.currentTarget);
            var imageData = JSON.parse($button.attr('data-image'));
            var size = $('#imgv-preview-size').val();
            
            this.importImageWithSize(imageData, size);
            this.closePreview();
        },

        importImage: function(e) {
            var $button = $(e.currentTarget);
            var imageData = JSON.parse($button.attr('data-image'));
            var size = $('#imgv-size-' + imageData.id).val();
            
            this.importImageWithSize(imageData, size);
        },

        importImageWithSize: function(imageData, size) {
            var self = this;
            var $button = $('.imgv-import-btn[data-image*="' + imageData.id + '"]');
            
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> ' + imgv_media.strings.importing);

            $.post(imgv_media.ajax_url, {
                action: 'imgv_import',
                nonce: imgv_media.nonce,
                image_url: imageData.url,
                title: imageData.title,
                attribution: imageData.attribution,
                alt_text: imageData.title,
                size: size,
                post_id: imgv_media.post_id
            })
            .done(function(response) {
                try {
                    var data = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (data.success) {
                        // Trigger selection in media frame
                        var attachment = wp.media.model.Attachment.create(data.attachment);
                        self.controller.state().get('selection').add(attachment);
                        
                        // Show success message
                        self.showSuccessMessage(imgv_media.strings.success);
                        
                        // Close the modal or switch to library view
                        self.controller.setState('library');
                        
                    } else {
                        alert(data.message || imgv_media.strings.error);
                        $button.prop('disabled', false).html('<span class="dashicons dashicons-download"></span> ' + imgv_media.strings.import);
                    }
                } catch (error) {
                    console.error('Parse error:', error);
                    alert(imgv_media.strings.error);
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-download"></span> ' + imgv_media.strings.import);
                }
            })
            .fail(function() {
                alert(imgv_media.strings.error);
                $button.prop('disabled', false).html('<span class="dashicons dashicons-download"></span> ' + imgv_media.strings.import);
            });
        },

        showSuccessMessage: function(message) {
            var $message = $('<div class="imgv-success-message">' + message + '</div>');
            $('body').append($message);
            
            setTimeout(function() {
                $message.fadeOut(function() {
                    $message.remove();
                });
            }, 3000);
        }
    });

    // Add success message styles
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .imgv-success-message {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: #46b450;
                color: white;
                padding: 15px 25px;
                border-radius: 4px;
                z-index: 999999;
                font-weight: bold;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            }
        `)
        .appendTo('head');

})(jQuery);

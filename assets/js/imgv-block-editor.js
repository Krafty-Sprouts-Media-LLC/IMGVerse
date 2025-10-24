/**
 * IMGVerse Block Editor JavaScript
 * 
 * @package IMGVerse
 * @author Krafty Sprouts Media, LLC
 * @version 1.5.0
 * @since 1.0.0
 * @last_modified 10/24/2025
 */

(function() {
    const { registerPlugin } = wp.plugins;
    const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
    const { PanelBody, Button, TextControl, SelectControl, Spinner, Notice } = wp.components;
    const { useState, useEffect } = wp.element;
    const { __ } = wp.i18n;
    const { dispatch } = wp.data;
    const { createBlock } = wp.blocks;

    const ImgversePanel = () => {
        const [searchQuery, setSearchQuery] = useState('');
        const [source, setSource] = useState('');
        const [license, setLicense] = useState('');
        const [isSearching, setIsSearching] = useState(false);
        const [images, setImages] = useState([]);
        const [currentPage, setCurrentPage] = useState(1);
        const [hasMore, setHasMore] = useState(true);
        const [error, setError] = useState('');

        const performSearch = () => {
            if (!searchQuery.trim()) {
                setError(__('Please enter a search term', 'imgverse'));
                return;
            }

            setIsSearching(true);
            setImages([]);
            setCurrentPage(1);
            setError('');
            
            const formData = new FormData();
            formData.append('action', 'imgv_search');
            formData.append('nonce', imgv_ajax.nonce);
            formData.append('query', searchQuery);
            formData.append('source', source);
            formData.append('license', license);
            formData.append('page', '1');

            fetch(imgv_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(responseText => {
                setIsSearching(false);
                try {
                    const data = JSON.parse(responseText);
                    if (data.success) {
                        setImages(data.images || []);
                        setHasMore(data.page < data.total_pages);
                        setError('');
                    } else {
                        setError(data.message || __('Error occurred. Please try again.', 'imgverse'));
                    }
                } catch (error) {
                    console.error('Parse error:', error, responseText);
                    setError(__('Error occurred. Please try again.', 'imgverse'));
                }
            })
            .catch(error => {
                setIsSearching(false);
                console.error('Search error:', error);
                setError(__('Error occurred. Please try again.', 'imgverse'));
            });
        };

        const loadMore = () => {
            if (isSearching || !hasMore) return;

            setIsSearching(true);
            const nextPage = currentPage + 1;
            
            const formData = new FormData();
            formData.append('action', 'imgv_search');
            formData.append('nonce', imgv_ajax.nonce);
            formData.append('query', searchQuery);
            formData.append('source', source);
            formData.append('license', license);
            formData.append('page', nextPage.toString());

            fetch(imgv_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(responseText => {
                setIsSearching(false);
                try {
                    const data = JSON.parse(responseText);
                    if (data.success) {
                        setImages(prev => [...prev, ...(data.images || [])]);
                        setCurrentPage(nextPage);
                        setHasMore(data.page < data.total_pages);
                    } else {
                        setError(data.message || __('Error occurred. Please try again.', 'imgverse'));
                    }
                } catch (error) {
                    console.error('Parse error:', error, responseText);
                    setError(__('Error occurred. Please try again.', 'imgverse'));
                }
            })
            .catch(error => {
                setIsSearching(false);
                console.error('Load more error:', error);
                setError(__('Error occurred. Please try again.', 'imgverse'));
            });
        };

        const importImage = (imageData, size = 'large') => {
            const formData = new FormData();
            formData.append('action', 'imgv_import');
            formData.append('nonce', imgv_ajax.nonce);
            formData.append('image_url', imageData.url);
            formData.append('title', imageData.title);
            formData.append('attribution', imageData.attribution);
            formData.append('alt_text', imageData.title);
            formData.append('size', size);
            formData.append('post_id', imgv_ajax.post_id);

            fetch(imgv_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(responseText => {
                try {
                    const data = JSON.parse(responseText);
                    if (data.success) {
                        // Insert image block
                        const imageBlock = createBlock('core/image', {
                            id: data.attachment_id,
                            url: data.url,
                            alt: imageData.title,
                            caption: imageData.attribution
                        });
                        
                        dispatch('core/block-editor').insertBlocks([imageBlock]);
                        
                        // Show success message
                        setError('');
                        // You could add a success state here if needed
                        
                    } else {
                        setError(data.message || __('Error occurred. Please try again.', 'imgverse'));
                    }
                } catch (error) {
                    console.error('Parse error:', error, responseText);
                    setError(__('Error occurred. Please try again.', 'imgverse'));
                }
            })
            .catch(error => {
                console.error('Import error:', error);
                setError(__('Error occurred. Please try again.', 'imgverse'));
            });
        };

        const ImageCard = ({ image, onImport }) => {
            const [selectedSize, setSelectedSize] = useState('large');
            const [isImporting, setIsImporting] = useState(false);

            const handleImport = () => {
                setIsImporting(true);
                onImport(image, selectedSize);
                // Reset after a delay
                setTimeout(() => setIsImporting(false), 2000);
            };

            return wp.element.createElement(
                'div',
                {
                    style: {
                        border: '1px solid #ddd',
                        borderRadius: '4px',
                        marginBottom: '15px',
                        padding: '10px',
                        backgroundColor: '#fff'
                    }
                },
                wp.element.createElement('img', {
                    src: image.thumbnail || image.url,
                    alt: image.title,
                    style: { 
                        width: '100%', 
                        maxHeight: '120px', 
                        objectFit: 'cover',
                        borderRadius: '3px',
                        marginBottom: '8px'
                    },
                    onError: (e) => {
                        e.target.src = image.url;
                    }
                }),
                wp.element.createElement('div', {
                    style: { fontSize: '13px', fontWeight: '500', marginBottom: '5px', lineHeight: '1.3' }
                }, image.title),
                wp.element.createElement('div', {
                    style: { fontSize: '11px', color: '#666', marginBottom: '8px', lineHeight: '1.3' }
                }, `${image.creator} • ${image.source} • ${image.license.toUpperCase()}`),
                wp.element.createElement('div', {
                    style: { marginBottom: '8px' }
                },
                    wp.element.createElement('label', {
                        style: { fontSize: '12px', fontWeight: '500', display: 'block', marginBottom: '4px' }
                    }, __('Size:', 'imgverse')),
                    wp.element.createElement(SelectControl, {
                        value: selectedSize,
                        onChange: setSelectedSize,
                        options: [
                            { label: __('Thumbnail', 'imgverse'), value: 'thumbnail' },
                            { label: __('Medium', 'imgverse'), value: 'medium' },
                            { label: __('Large', 'imgverse'), value: 'large' },
                            { label: __('Full Size', 'imgverse'), value: 'full' }
                        ],
                        style: { fontSize: '12px' }
                    })
                ),
                wp.element.createElement(Button, {
                    variant: 'secondary',
                    isSmall: true,
                    onClick: handleImport,
                    isBusy: isImporting,
                    disabled: isImporting,
                    style: { width: '100%' }
                }, isImporting ? __('Importing...', 'imgverse') : __('Import Image', 'imgverse'))
            );
        };

        return wp.element.createElement(
            PluginSidebar,
            {
                name: 'imgverse-sidebar',
                title: __('IMGVerse Images', 'imgverse'),
                icon: 'format-image'
            },
            wp.element.createElement(
                PanelBody,
                { title: __('Search CC Images', 'imgverse'), initialOpen: true },
                error && wp.element.createElement(Notice, {
                    status: 'error',
                    isDismissible: true,
                    onRemove: () => setError('')
                }, error),
                
                wp.element.createElement(TextControl, {
                    label: __('Search Query', 'imgverse'),
                    value: searchQuery,
                    onChange: setSearchQuery,
                    placeholder: __('Search for images...', 'imgverse'),
                    onKeyDown: (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            performSearch();
                        }
                    }
                }),
                
                wp.element.createElement(SelectControl, {
                    label: __('Source', 'imgverse'),
                    value: source,
                    onChange: setSource,
                    options: [
                        { label: __('All Sources', 'imgverse'), value: '' },
                        { label: 'Flickr', value: 'flickr' },
                        { label: 'Wikimedia Commons', value: 'wikimedia' },
                        { label: 'iNaturalist', value: 'inaturalist' },
                        { label: 'Metropolitan Museum', value: 'met' },
                        { label: 'NYPL', value: 'nypl' },
                        { label: 'Rawpixel', value: 'rawpixel' },
                        { label: 'Smithsonian', value: 'smithsonian' }
                    ]
                }),
                
                wp.element.createElement(SelectControl, {
                    label: __('License', 'imgverse'),
                    value: license,
                    onChange: setLicense,
                    options: [
                        { label: __('All Licenses', 'imgverse'), value: '' },
                        { label: 'CC0', value: 'cc0' },
                        { label: 'CC BY', value: 'by' },
                        { label: 'CC BY-SA', value: 'by-sa' },
                        { label: 'CC BY-NC', value: 'by-nc' },
                        { label: 'CC BY-NC-SA', value: 'by-nc-sa' },
                        { label: 'CC BY-NC-ND', value: 'by-nc-nd' },
                        { label: 'CC BY-ND', value: 'by-nd' }
                    ]
                }),
                
                wp.element.createElement(Button, {
                    variant: 'primary',
                    isBusy: isSearching,
                    onClick: performSearch,
                    disabled: isSearching || !searchQuery.trim(),
                    style: { width: '100%', marginBottom: '20px' }
                }, isSearching ? __('Searching...', 'imgverse') : __('Search Images', 'imgverse')),
                
                isSearching && wp.element.createElement(
                    'div',
                    { style: { textAlign: 'center', padding: '20px' } },
                    wp.element.createElement(Spinner)
                ),
                
                images.length > 0 && wp.element.createElement(
                    'div',
                    null,
                    wp.element.createElement('h4', {
                        style: { margin: '20px 0 15px 0', fontSize: '14px', fontWeight: 'bold' }
                    }, __('Search Results', 'imgverse') + ` (${images.length})`),
                    images.slice(0, 10).map((image, index) =>
                        wp.element.createElement(ImageCard, {
                            key: index,
                            image: image,
                            onImport: importImage
                        })
                    ),
                    images.length > 10 && wp.element.createElement('p', {
                        style: { fontSize: '12px', color: '#666', fontStyle: 'italic' }
                    }, `${__('Showing first 10 of', 'imgverse')} ${images.length} ${__('results', 'imgverse')}`),
                    
                    hasMore && wp.element.createElement(Button, {
                        variant: 'secondary',
                        onClick: loadMore,
                        isBusy: isSearching,
                        disabled: isSearching,
                        style: { width: '100%', marginTop: '10px' }
                    }, isSearching ? __('Loading more...', 'imgverse') : __('Load More', 'imgverse'))
                ),
                
                images.length === 0 && !isSearching && searchQuery && wp.element.createElement('p', {
                    style: { fontSize: '13px', color: '#666', fontStyle: 'italic', textAlign: 'center' }
                }, __('No images found. Try different search terms.', 'imgverse'))
            )
        );
    };

    registerPlugin('imgverse-images', {
        render: () => {
            return wp.element.createElement(
                wp.element.Fragment,
                null,
                wp.element.createElement(PluginSidebarMoreMenuItem, {
                    target: 'imgverse-sidebar',
                    icon: 'format-image'
                }, __('IMGVerse Images', 'imgverse')),
                wp.element.createElement(ImgversePanel)
            );
        }
    });

})();

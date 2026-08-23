/**
 * Media modal entry: mount the shared App into the WP MediaFrame IMGVerse tab.
 *
 * @package IMGVerse
 */

/* global wp, imgvData, jQuery */

import '../scss/style.scss';
import { createRoot } from '@wordpress/element';
import App from './components/App';

let activeFrameId = '';
let activeFrame = null;
let mediaFrameInstance = null;
let reactRoot = null;

/**
 * Tab label from localized data.
 *
 * @return {string} Tab title.
 */
function getTabTitle() {
	if ( typeof imgvData !== 'undefined' && imgvData && imgvData.tabTitle ) {
		return imgvData.tabTitle;
	}

	return 'IMGVerse';
}

/**
 * Unmount the React app if mounted.
 */
function unmountApp() {
	if ( reactRoot ) {
		reactRoot.unmount();
		reactRoot = null;
	}
}

/**
 * Mount App into a DOM container.
 *
 * @param {Element} container Root element.
 */
function mountApp( container ) {
	unmountApp();
	reactRoot = createRoot( container );
	reactRoot.render( <App context="modal" /> );
}

/**
 * Create the #imgverse-root wrapper for the media frame content.
 *
 * @return {Element} Wrapper element.
 */
function createWrapperHTML() {
	const wrapper = document.createElement( 'div' );
	wrapper.className = 'imgv-browser-wrapper';

	const root = document.createElement( 'div' );
	root.id = 'imgverse-root';
	root.className = 'imgv-media-modal-root';

	wrapper.appendChild( root );
	return wrapper;
}

/**
 * Render IMGVerse into the active media frame content region.
 */
function imgverseMediaTab() {
	if ( ! activeFrame ) {
		return;
	}

	const modal = activeFrame.querySelector( '.media-frame-content' );

	if ( ! modal ) {
		return;
	}

	unmountApp();
	modal.innerHTML = '';
	modal.appendChild( createWrapperHTML() );

	const element = modal.querySelector( '#imgverse-root' );

	if ( ! element ) {
		return;
	}

	mountApp( element );
}

/**
 * Store the active frame from a MediaFrame content:create handler.
 *
 * @param {Object} frame MediaFrame instance.
 */
function storeActiveFrame( frame ) {
	mediaFrameInstance = frame;
	const state = frame.state();

	if ( state && state.frame && state.frame.el ) {
		activeFrameId = state.id || '';
		activeFrame = state.frame.el;
	}
}

/**
 * After a successful import, switch to Media Library and select the attachment
 * so Insert can be used without hunting.
 *
 * @param {Object} attachment Attachment data (needs id).
 */
export function selectImportedAttachment( attachment ) {
	if (
		typeof wp === 'undefined' ||
		! wp.media ||
		! attachment ||
		! attachment.id
	) {
		return;
	}

	const frame = mediaFrameInstance || wp.media.frame;

	if ( ! frame ) {
		return;
	}

	if ( frame.el ) {
		const browseTab = frame.el.querySelector( '#menu-item-browse' );

		if ( browseTab ) {
			browseTab.click();
		}
	}

	if ( frame.content && typeof frame.content.mode === 'function' ) {
		frame.content.mode( 'browse' );
	}

	const state =
		typeof frame.state === 'function' ? frame.state() : null;

	if ( ! state || typeof state.get !== 'function' ) {
		return;
	}

	const selection = state.get( 'selection' );

	if ( ! selection ) {
		return;
	}

	const model = wp.media.attachment( attachment.id );

	model.fetch( {
		success() {
			selection.reset( model );

			if ( typeof selection.trigger === 'function' ) {
				selection.trigger( 'selection:single' );
			}
		},
	} );
}

window.imgvSelectImportedAttachment = selectImportedAttachment;

/**
 * Whether the IMGVerse router tab is currently selected.
 *
 * @return {boolean} True when active.
 */
function isImgverseTabActive() {
	if ( ! activeFrame ) {
		return false;
	}

	const selectedTab = activeFrame.querySelector(
		'.media-router button.media-menu-item.active'
	);

	return Boolean(
		selectedTab && 'menu-item-imgverse' === selectedTab.id
	);
}

if ( typeof wp !== 'undefined' && wp.media && wp.media.view && wp.media.view.MediaFrame ) {
	const oldMediaFrame = wp.media.view.MediaFrame.Post;
	const oldMediaFrameSelect = wp.media.view.MediaFrame.Select;

	wp.media.view.MediaFrame.Select = oldMediaFrameSelect.extend( {
		browseRouter( routerView ) {
			oldMediaFrameSelect.prototype.browseRouter.apply( this, arguments );
			routerView.set( {
				imgverse: {
					text: getTabTitle(),
					priority: 60,
				},
			} );
		},

		bindHandlers() {
			oldMediaFrameSelect.prototype.bindHandlers.apply( this, arguments );
			this.on( 'content:create:imgverse', this.createImgverseContent, this );
		},

		createImgverseContent() {
			storeActiveFrame( this );
			imgverseMediaTab();
		},
	} );

	wp.media.view.MediaFrame.Post = oldMediaFrame.extend( {
		browseRouter( routerView ) {
			oldMediaFrame.prototype.browseRouter.apply( this, arguments );
			routerView.set( {
				imgverse: {
					text: getTabTitle(),
					priority: 60,
				},
			} );
		},

		bindHandlers() {
			oldMediaFrame.prototype.bindHandlers.apply( this, arguments );
			this.on( 'content:create:imgverse', this.createImgverseContent, this );
		},

		createImgverseContent() {
			storeActiveFrame( this );
			imgverseMediaTab();
		},
	} );

	if ( typeof jQuery !== 'undefined' ) {
		jQuery( document ).ready( function ( $ ) {
			wp.media.view.Modal.prototype.on( 'open', function () {
				if ( isImgverseTabActive() ) {
					imgverseMediaTab();
				}
			} );

			$( document ).on(
				'click',
				'.media-router button.media-menu-item',
				function () {
					if ( isImgverseTabActive() ) {
						imgverseMediaTab();
					}
				}
			);
		} );
	}
}

// Keep a global reference for debugging / Task 9 reuse.
window.imgvApp = App;

export { App };
export default App;

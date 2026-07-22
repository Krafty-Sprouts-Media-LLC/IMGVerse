/**
 * Set the current post featured image from an attachment ID.
 *
 * @package IMGVerse
 */

import { dispatch } from '@wordpress/data';

/**
 * Set featured_media on the current post.
 *
 * @param {number} attachmentId Attachment post ID.
 */
export function setFeaturedImage( attachmentId ) {
	const id = Number( attachmentId );

	if ( ! id ) {
		return;
	}

	dispatch( 'core/editor' ).editPost( { featured_media: id } );
}

export default setFeaturedImage;

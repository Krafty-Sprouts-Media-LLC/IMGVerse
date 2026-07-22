/**
 * Insert an imported attachment as a core/image block.
 *
 * @package IMGVerse
 */

import { createBlock } from '@wordpress/blocks';
import { dispatch } from '@wordpress/data';

/**
 * Insert a core/image block for the given attachment.
 *
 * @param {Object} attachment Attachment from wp_prepare_attachment_for_js.
 * @param {string} [size='large'] Preferred image size slug.
 */
export function insertImage( attachment, size = 'large' ) {
	if ( ! attachment || ! attachment.id ) {
		return;
	}

	const url =
		( attachment.sizes &&
			attachment.sizes[ size ] &&
			attachment.sizes[ size ].url ) ||
		attachment.url;

	if ( ! url ) {
		return;
	}

	const caption =
		typeof attachment.caption === 'string'
			? attachment.caption
			: ( attachment.caption && attachment.caption.raw ) || '';

	const block = createBlock( 'core/image', {
		id: attachment.id,
		url,
		alt: attachment.alt || '',
		caption,
	} );

	dispatch( 'core/block-editor' ).insertBlocks( block );
}

export default insertImage;

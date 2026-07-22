/**
 * REST client wrappers for IMGVerse search and import.
 *
 * @package IMGVerse
 */

/* global imgvData */

/**
 * Resolve localized script data when available.
 *
 * @return {Object|null} imgvData or null when undefined.
 */
function getImgvData() {
	if ( typeof imgvData === 'undefined' || ! imgvData ) {
		return null;
	}

	return imgvData;
}

/**
 * Search images via the IMGVerse REST API.
 *
 * @param {Object} params          Search params.
 * @param {string} params.q        Query string.
 * @param {string} params.provider Provider slug.
 * @param {string} [params.source] Openverse source filter.
 * @param {string} [params.license] License filter.
 * @param {number} [params.page]   Page number.
 * @return {Promise<Object>} Parsed JSON response.
 */
export async function searchImages( { q, provider, source, license, page } ) {
	const data = getImgvData();

	if ( ! data || ! data.restUrl ) {
		return {
			success: false,
			code: 'missing_config',
			images: [],
			message: 'IMGVerse script data is not available.',
		};
	}

	const url = new URL( `${ data.restUrl }search` );
	url.searchParams.set( 'q', q || '' );
	url.searchParams.set( 'provider', provider || 'openverse' );

	if ( source ) {
		url.searchParams.set( 'source', source );
	}

	if ( license ) {
		url.searchParams.set( 'license', license );
	}

	url.searchParams.set( 'page', String( page || 1 ) );

	try {
		const res = await fetch( url.toString(), {
			headers: {
				'X-WP-Nonce': data.nonce || '',
			},
		} );

		return await res.json();
	} catch ( error ) {
		return {
			success: false,
			images: [],
			message:
				error && error.message
					? error.message
					: 'Search request failed.',
		};
	}
}

/**
 * Import a remote image into the media library.
 *
 * @param {Object} payload Import body (url, title, alt, caption, provider, source, post_id).
 * @return {Promise<Object>} Parsed JSON response.
 */
export async function importImage( payload ) {
	const data = getImgvData();

	if ( ! data || ! data.restUrl ) {
		return {
			success: false,
			message: 'IMGVerse script data is not available.',
		};
	}

	try {
		const res = await fetch( `${ data.restUrl }import`, {
			method: 'POST',
			headers: {
				'X-WP-Nonce': data.nonce || '',
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( payload ),
		} );

		return await res.json();
	} catch ( error ) {
		return {
			success: false,
			message:
				error && error.message
					? error.message
					: 'Import request failed.',
		};
	}
}

/**
 * Current post ID from localized data.
 *
 * @return {number} Post ID or 0.
 */
export function getPostId() {
	const data = getImgvData();

	if ( ! data || ! data.postId ) {
		return 0;
	}

	return Number( data.postId ) || 0;
}

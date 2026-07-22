/**
 * Thumbnail URL selection and onError fallback helpers.
 *
 * @package IMGVerse
 */

/**
 * Resolve the initial thumbnail src for a normalized urls object.
 *
 * @param {Object} urls Urls with thumb and full keys.
 * @return {string} Preferred thumbnail URL.
 */
export function getThumbSrc( urls ) {
	if ( urls.thumb ) {
		return urls.thumb;
	}

	return urls.full;
}

/**
 * Return the next URL to try after a thumbnail load error, or null when exhausted.
 *
 * @param {string} currentSrc The URL that failed to load.
 * @param {Object} urls       Urls with thumb and full keys.
 * @return {string|null} Next URL or null.
 */
export function nextThumbOnError( currentSrc, urls ) {
	if ( currentSrc === urls.thumb && urls.full ) {
		return urls.full;
	}

	return null;
}

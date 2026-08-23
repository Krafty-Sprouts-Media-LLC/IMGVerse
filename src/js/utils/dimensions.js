/**
 * Image dimension helpers for photo cards.
 *
 * @package IMGVerse
 */

/**
 * Orientation slug from width/height.
 *
 * @param {number} width  Pixel width.
 * @param {number} height Pixel height.
 * @return {string} landscape|portrait|square|''
 */
export function getOrientation( width, height ) {
	const w = Number( width ) || 0;
	const h = Number( height ) || 0;

	if ( w <= 0 || h <= 0 ) {
		return '';
	}

	const ratio = w / h;

	if ( ratio > 1.05 ) {
		return 'landscape';
	}

	if ( ratio < 0.95 ) {
		return 'portrait';
	}

	return 'square';
}

/**
 * Human label for orientation.
 *
 * @param {string} orientation Orientation slug.
 * @return {string} Label.
 */
export function getOrientationLabel( orientation ) {
	if ( 'landscape' === orientation ) {
		return 'Wide';
	}

	if ( 'portrait' === orientation ) {
		return 'Portrait';
	}

	if ( 'square' === orientation ) {
		return 'Square';
	}

	return '';
}

/**
 * Format dimensions for display.
 *
 * @param {number} width  Pixel width.
 * @param {number} height Pixel height.
 * @return {string} e.g. "1920 × 1080".
 */
export function formatDimensions( width, height ) {
	const w = Number( width ) || 0;
	const h = Number( height ) || 0;

	if ( w <= 0 || h <= 0 ) {
		return '';
	}

	return `${ w } × ${ h }`;
}

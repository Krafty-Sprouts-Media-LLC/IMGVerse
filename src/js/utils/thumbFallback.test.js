/**
 * Unit tests for thumbnail URL fallback helpers.
 *
 * @package IMGVerse
 */

import { getThumbSrc, nextThumbOnError } from './thumbFallback';

describe( 'thumbFallback', () => {
	it( 'uses thumb when present', () => {
		expect(
			getThumbSrc( { thumb: 'https://a/t.jpg', full: 'https://a/f.jpg' } )
		).toBe( 'https://a/t.jpg' );
	} );

	it( 'falls back to full when thumb empty', () => {
		expect( getThumbSrc( { thumb: '', full: 'https://a/f.jpg' } ) ).toBe(
			'https://a/f.jpg'
		);
	} );

	it( 'on error swaps thumb to full once', () => {
		expect(
			nextThumbOnError( 'https://a/t.jpg', {
				thumb: 'https://a/t.jpg',
				full: 'https://a/f.jpg',
			} )
		).toBe( 'https://a/f.jpg' );
	} );

	it( 'on error of full returns null', () => {
		expect(
			nextThumbOnError( 'https://a/f.jpg', {
				thumb: 'https://a/t.jpg',
				full: 'https://a/f.jpg',
			} )
		).toBeNull();
	} );
} );

/**
 * Tests for dimension helpers.
 *
 * @package IMGVerse
 */

import {
	formatDimensions,
	getOrientation,
	getOrientationLabel,
} from './dimensions';

describe( 'dimensions helpers', () => {
	it( 'classifies landscape, portrait, and square', () => {
		expect( getOrientation( 1920, 1080 ) ).toBe( 'landscape' );
		expect( getOrientation( 800, 1200 ) ).toBe( 'portrait' );
		expect( getOrientation( 1000, 1000 ) ).toBe( 'square' );
		expect( getOrientation( 0, 100 ) ).toBe( '' );
	} );

	it( 'formats labels and pixel sizes', () => {
		expect( getOrientationLabel( 'landscape' ) ).toBe( 'Wide' );
		expect( getOrientationLabel( 'portrait' ) ).toBe( 'Portrait' );
		expect( getOrientationLabel( 'square' ) ).toBe( 'Square' );
		expect( formatDimensions( 1920, 1080 ) ).toBe( '1920 × 1080' );
		expect( formatDimensions( 0, 1080 ) ).toBe( '' );
	} );
} );

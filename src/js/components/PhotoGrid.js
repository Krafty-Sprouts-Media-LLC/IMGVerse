/**
 * Responsive photo results grid for IMGVerse.
 *
 * @package IMGVerse
 */

import Photo from './Photo';

/**
 * PhotoGrid component.
 *
 * @param {Object}   props          Component props.
 * @param {Array}    props.images   Normalized image results.
 * @param {string}   props.context  App context (modal|sidebar).
 * @return {JSX.Element} Grid markup.
 */
export default function PhotoGrid( { images, context } ) {
	if ( ! images || ! images.length ) {
		return null;
	}

	return (
		<div className={ `imgv-photo-grid imgv-photo-grid--${ context || 'modal' }` }>
			{ images.map( ( image, index ) => (
				<Photo
					key={ image.id || `${ image.provider || 'img' }-${ index }` }
					image={ image }
					context={ context }
				/>
			) ) }
		</div>
	);
}

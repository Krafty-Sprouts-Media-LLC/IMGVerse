/**
 * Photo card with thumb fallback, meta editing, and import action.
 *
 * @package IMGVerse
 */

import { useState } from '@wordpress/element';
import { getThumbSrc, nextThumbOnError } from '../utils/thumbFallback';
import { getPostId, importImage } from '../utils/api';

/**
 * Photo component.
 *
 * @param {Object} props       Component props.
 * @param {Object} props.image Normalized image result.
 * @param {string} props.context App context (modal|sidebar).
 * @return {JSX.Element} Photo card markup.
 */
export default function Photo( { image, context } ) {
	const urls = image.urls || {};
	const [ thumbSrc, setThumbSrc ] = useState( () => getThumbSrc( urls ) );
	const [ title, setTitle ] = useState( image.title || '' );
	const [ alt, setAlt ] = useState( image.alt || '' );
	const [ caption, setCaption ] = useState(
		image.attribution || image.caption || ''
	);
	const [ importing, setImporting ] = useState( false );
	const [ status, setStatus ] = useState( '' );
	const [ error, setError ] = useState( '' );
	const [ expanded, setExpanded ] = useState( false );

	/**
	 * Fall back from thumb to full when the preview fails.
	 */
	function handleThumbError() {
		const next = nextThumbOnError( thumbSrc, urls );

		if ( next ) {
			setThumbSrc( next );
		}
	}

	/**
	 * Import the full image into the media library.
	 */
	async function handleImport() {
		if ( importing || ! urls.full ) {
			return;
		}

		setImporting( true );
		setError( '' );
		setStatus( '' );

		try {
			const result = await importImage( {
				url: urls.full,
				title,
				alt,
				caption,
				provider: image.provider || '',
				source: image.source || '',
				post_id: getPostId(),
			} );

			if ( result && ( result.success || result.id || result.attachment_id ) ) {
				setStatus( 'Imported' );
			} else {
				setError(
					( result && result.message ) || 'Import failed. Please try again.'
				);
			}
		} catch ( err ) {
			setError(
				err && err.message
					? err.message
					: 'Import failed. Please try again.'
			);
		} finally {
			setImporting( false );
		}
	}

	const userName =
		image.user && image.user.name ? image.user.name : '';

	return (
		<article
			className={ `imgv-photo imgv-photo--${ context || 'modal' }` }
		>
			<div className="imgv-photo__media">
				{ thumbSrc ? (
					<img
						className="imgv-photo__thumb"
						src={ thumbSrc }
						alt={ alt || title || 'IMGVerse image' }
						loading="lazy"
						onError={ handleThumbError }
					/>
				) : (
					<div className="imgv-photo__placeholder" aria-hidden="true" />
				) }
				<div className="imgv-photo__overlay">
					<button
						type="button"
						className="imgv-photo__action"
						onClick={ () => setExpanded( ( value ) => ! value ) }
					>
						{ expanded ? 'Hide details' : 'Edit details' }
					</button>
					<button
						type="button"
						className="imgv-photo__action imgv-photo__action--primary"
						onClick={ handleImport }
						disabled={ importing || ! urls.full || Boolean( status ) }
					>
						{ importing ? 'Importing…' : status || 'Import' }
					</button>
				</div>
			</div>
			<div className="imgv-photo__meta">
				{ userName ? (
					<p className="imgv-photo__credit">{ userName }</p>
				) : null }
				{ error ? (
					<p className="imgv-photo__error" role="alert">
						{ error }
					</p>
				) : null }
				{ expanded ? (
					<div className="imgv-photo__fields">
						<label className="imgv-photo__field">
							<span>Title</span>
							<input
								type="text"
								value={ title }
								onChange={ ( event ) =>
									setTitle( event.target.value )
								}
							/>
						</label>
						<label className="imgv-photo__field">
							<span>Alt text</span>
							<input
								type="text"
								value={ alt }
								onChange={ ( event ) =>
									setAlt( event.target.value )
								}
							/>
						</label>
						<label className="imgv-photo__field">
							<span>Caption</span>
							<textarea
								rows={ 2 }
								value={ caption }
								onChange={ ( event ) =>
									setCaption( event.target.value )
								}
							/>
						</label>
					</div>
				) : null }
			</div>
		</article>
	);
}

/**
 * Photo card with thumb fallback, meta editing, and import/insert actions.
 *
 * @package IMGVerse
 */

/* global imgvData */

import { useState } from '@wordpress/element';
import { getThumbSrc, nextThumbOnError } from '../utils/thumbFallback';
import {
	formatDimensions,
	getOrientation,
	getOrientationLabel,
} from '../utils/dimensions';
import { getPostId, importImage } from '../utils/api';
import { insertImage } from '../editor/insertImage';
import { setFeaturedImage } from '../editor/setFeaturedImage';

/**
 * Preferred insert size from localized settings.
 *
 * @return {string} Size slug.
 */
function getDefaultInsertSize() {
	if (
		typeof imgvData !== 'undefined' &&
		imgvData &&
		imgvData.defaultInsertSize
	) {
		return imgvData.defaultInsertSize;
	}

	return 'large';
}

/**
 * Photo component.
 *
 * @param {Object} props         Component props.
 * @param {Object} props.image   Normalized image result.
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
	const [ insertSize, setInsertSize ] = useState( getDefaultInsertSize );
	const [ importing, setImporting ] = useState( false );
	const [ status, setStatus ] = useState( '' );
	const [ error, setError ] = useState( '' );
	const [ expanded, setExpanded ] = useState( false );
	const [ attachment, setAttachment ] = useState( null );
	const [ featuredSet, setFeaturedSet ] = useState( false );
	const [ inserted, setInserted ] = useState( false );

	const isSidebar = 'sidebar' === context;
	const isSuccess = Boolean( status ) && ! error;
	const width = Number( image.width ) || 0;
	const height = Number( image.height ) || 0;
	const orientation = getOrientation( width, height );
	const orientationLabel = getOrientationLabel( orientation );
	const dimensionsLabel = formatDimensions( width, height );
	const mediaStyle =
		width > 0 && height > 0
			? { aspectRatio: `${ width } / ${ height }` }
			: undefined;

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
		if ( importing || ! urls.full || Boolean( status ) ) {
			return;
		}

		setImporting( true );
		setError( '' );
		setStatus( '' );
		setAttachment( null );
		setFeaturedSet( false );
		setInserted( false );

		try {
			const result = await importImage( {
				url: urls.full,
				title,
				alt,
				caption,
				provider: image.provider || '',
				source: image.source || '',
				creator: userName,
				license: image.license || '',
				license_url: image.license_url || '',
				permalink: image.permalink || '',
				post_id: getPostId(),
			} );

			if ( result && ( result.success || result.id || result.attachment_id ) ) {
				setStatus( 'Imported' );
				const nextAttachment =
					result.attachment ||
					( result.attachment_id
						? { id: result.attachment_id, url: result.url || '' }
						: null );

				if ( nextAttachment ) {
					setAttachment( nextAttachment );

					if (
						! isSidebar &&
						typeof window.imgvSelectImportedAttachment === 'function'
					) {
						window.imgvSelectImportedAttachment( nextAttachment );
					}
				}
			} else {
				setError(
					( result && result.message ) ||
						'Import failed. Please try again.'
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

	/**
	 * Insert the imported attachment into the block editor.
	 */
	function handleInsert() {
		if ( ! attachment ) {
			return;
		}

		insertImage( attachment, insertSize );
		setInserted( true );
	}

	/**
	 * Set the imported attachment as the post featured image.
	 */
	function handleSetFeatured() {
		if ( ! attachment || ! attachment.id ) {
			return;
		}

		setFeaturedImage( attachment.id );
		setFeaturedSet( true );
	}

	const userName =
		image.user && image.user.name ? image.user.name : '';

	const stateClass = importing
		? ' is-importing'
		: isSuccess
			? ' is-success'
			: error
				? ' is-error'
				: '';

	return (
		<article
			className={ `imgv-photo imgv-photo--${ context || 'modal' }${
				orientation ? ` imgv-photo--${ orientation }` : ''
			}${ expanded ? ' is-expanded' : '' }${ stateClass }` }
		>
			<div className="imgv-photo__wrap">
				<button
					type="button"
					className="imgv-photo__media"
					style={ mediaStyle }
					onClick={ handleImport }
					disabled={ importing || ! urls.full || Boolean( status ) }
					aria-label={
						importing
							? 'Importing image'
							: status
								? 'Image imported'
								: `Import ${ title || 'image' }`
					}
				>
					{ thumbSrc ? (
						<img
							className="imgv-photo__thumb"
							src={ thumbSrc }
							alt={ alt || title || 'IMGVerse image' }
							loading="lazy"
							onError={ handleThumbError }
						/>
					) : (
						<div
							className="imgv-photo__placeholder"
							aria-hidden="true"
						/>
					) }
				</button>

				{ importing ? (
					<div className="imgv-photo__progress" role="status">
						<span className="imgv-photo__progress-label">
							Saving to library
						</span>
						<span className="imgv-photo__progress-bar" aria-hidden="true" />
					</div>
				) : null }

				{ isSuccess ? (
					<div className="imgv-photo__banner imgv-photo__banner--success" role="status">
						Saved
					</div>
				) : null }

				{ error ? (
					<div className="imgv-photo__banner imgv-photo__banner--error" role="alert">
						Failed
					</div>
				) : null }

				{ dimensionsLabel || orientationLabel ? (
					<div className="imgv-photo__meta-top">
						<span
							className={
								orientation
									? `imgv-photo__shape imgv-photo__shape--${ orientation }`
									: 'imgv-photo__shape'
							}
						>
							{ orientationLabel
								? `${ orientationLabel } · ${ dimensionsLabel }`
								: dimensionsLabel }
						</span>
					</div>
				) : userName ? (
					<div className="imgv-photo__meta-top">
						<span className="imgv-photo__action">{ userName }</span>
					</div>
				) : null }

				<div className="imgv-photo__controls">
					<p className="imgv-photo__credit">
						{ userName || title || 'IMGVerse' }
					</p>
					<div className="imgv-photo__actions">
						<button
							type="button"
							className="imgv-photo__action"
							onClick={ () =>
								setExpanded( ( value ) => ! value )
							}
						>
							{ expanded ? 'Hide' : 'Edit' }
						</button>
						<button
							type="button"
							className="imgv-photo__action imgv-photo__action--primary"
							onClick={ handleImport }
							disabled={
								importing || ! urls.full || Boolean( status )
							}
						>
							{ importing
								? 'Downloading'
								: status || 'Download' }
						</button>
						{ isSidebar && attachment ? (
							<>
								<button
									type="button"
									className="imgv-photo__action imgv-photo__action--primary"
									onClick={ handleInsert }
									disabled={ inserted }
								>
									{ inserted ? 'Inserted' : 'Insert' }
								</button>
								<button
									type="button"
									className="imgv-photo__action"
									onClick={ handleSetFeatured }
									disabled={ featuredSet }
								>
									{ featuredSet
										? 'Featured set'
										: 'Set featured' }
								</button>
							</>
						) : null }
					</div>
				</div>
			</div>

			{ error ? (
				<p className="imgv-photo__error" role="alert">
					{ error }
				</p>
			) : null }

			{ isSidebar ? (
				<label className="imgv-photo__field imgv-photo__field--size">
					<span>Insert size</span>
					<select
						value={ insertSize }
						onChange={ ( event ) =>
							setInsertSize( event.target.value )
						}
					>
						<option value="thumbnail">Thumbnail</option>
						<option value="medium">Medium</option>
						<option value="large">Large</option>
						<option value="full">Full</option>
					</select>
				</label>
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
		</article>
	);
}

/**
 * Shared IMGVerse React app: provider nav, search, grid, and import.
 *
 * @package IMGVerse
 */

/* global imgvData */

import { useState } from '@wordpress/element';
import { providerMissingKey } from '../constants/providers';
import { searchImages } from '../utils/api';
import ProviderNav from './ProviderNav';
import SearchBar from './SearchBar';
import PhotoGrid from './PhotoGrid';
import EmptyState from './EmptyState';

/**
 * Read localized provider config safely.
 *
 * @return {Object} Providers map.
 */
function getProvidersConfig() {
	if ( typeof imgvData === 'undefined' || ! imgvData ) {
		return {};
	}

	return imgvData.providers || {};
}

/**
 * Grid column count from settings (2–6).
 *
 * @return {number} Column count.
 */
function getGridColumns() {
	if ( typeof imgvData === 'undefined' || ! imgvData ) {
		return 4;
	}

	const cols = Number( imgvData.gridColumns ) || 4;
	return Math.min( 6, Math.max( 2, cols ) );
}

/**
 * App root component.
 *
 * @param {Object} props         Component props.
 * @param {string} props.context Mount context: modal or sidebar.
 * @return {JSX.Element} App shell.
 */
export default function App( { context = 'modal' } ) {
	const [ provider, setProvider ] = useState( 'openverse' );
	const [ query, setQuery ] = useState( '' );
	const [ source, setSource ] = useState( '' );
	const [ license, setLicense ] = useState( '' );
	const [ images, setImages ] = useState( [] );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 0 );
	const [ loading, setLoading ] = useState( false );
	const [ loadingMore, setLoadingMore ] = useState( false );
	const [ emptyReason, setEmptyReason ] = useState( '' );
	const [ errorMessage, setErrorMessage ] = useState( '' );
	const [ hasSearched, setHasSearched ] = useState( false );

	const providersConfig = getProvidersConfig();
	const missingKey = providerMissingKey( provider, providersConfig );
	const gridColumns = getGridColumns();

	/**
	 * Reset results when the provider changes.
	 *
	 * @param {string} nextProvider Next provider slug.
	 */
	function handleProviderChange( nextProvider ) {
		setProvider( nextProvider );
		setImages( [] );
		setPage( 1 );
		setTotalPages( 0 );
		setEmptyReason( '' );
		setErrorMessage( '' );
		setHasSearched( false );
		setSource( '' );
		setLicense( '' );
	}

	/**
	 * Run a search (page 1) or append a page for load more.
	 *
	 * @param {number}  nextPage Target page.
	 * @param {boolean} append   Whether to append to existing results.
	 */
	async function runSearch( nextPage = 1, append = false ) {
		if ( missingKey ) {
			setImages( [] );
			setEmptyReason( 'missing_api_key' );
			setErrorMessage( '' );
			setHasSearched( true );
			return;
		}

		const trimmed = query.trim();

		if ( ! trimmed ) {
			return;
		}

		if ( append ) {
			setLoadingMore( true );
		} else {
			setLoading( true );
			setEmptyReason( '' );
			setErrorMessage( '' );
		}

		try {
			const result = await searchImages( {
				q: trimmed,
				provider,
				source: 'openverse' === provider ? source : '',
				license: 'openverse' === provider ? license : '',
				page: nextPage,
			} );

			setHasSearched( true );

			if ( result && 'missing_api_key' === result.code ) {
				setImages( [] );
				setPage( 1 );
				setTotalPages( 0 );
				setEmptyReason( 'missing_api_key' );
				setErrorMessage( result.message || '' );
				return;
			}

			if ( ! result || false === result.success ) {
				if ( ! append ) {
					setImages( [] );
				}
				setEmptyReason( 'error' );
				setErrorMessage(
					( result && result.message ) ||
						'Error occurred. Please try again.'
				);
				return;
			}

			const nextImages = Array.isArray( result.images )
				? result.images
				: [];

			setImages( ( prev ) =>
				append ? [ ...prev, ...nextImages ] : nextImages
			);
			setPage( result.page || nextPage );
			setTotalPages( result.total_pages || 0 );

			if ( ! nextImages.length && ! append ) {
				setEmptyReason( 'no_results' );
			} else {
				setEmptyReason( '' );
			}

			setErrorMessage( '' );
		} catch ( error ) {
			setHasSearched( true );
			setEmptyReason( 'error' );
			setErrorMessage(
				error && error.message
					? error.message
					: 'Error occurred. Please try again.'
			);

			if ( ! append ) {
				setImages( [] );
			}
		} finally {
			setLoading( false );
			setLoadingMore( false );
		}
	}

	/**
	 * Submit a fresh search.
	 */
	function handleSubmit() {
		runSearch( 1, false );
	}

	/**
	 * Load the next results page.
	 */
	function handleLoadMore() {
		if ( loading || loadingMore || page >= totalPages ) {
			return;
		}

		runSearch( page + 1, true );
	}

	const reason = missingKey
		? 'missing_api_key'
		: emptyReason || ( hasSearched ? '' : 'welcome' );
	const showEmpty =
		missingKey ||
		! hasSearched ||
		( hasSearched && ! loading && Boolean( emptyReason ) );
	const canLoadMore =
		! missingKey &&
		hasSearched &&
		! loading &&
		! emptyReason &&
		images.length > 0 &&
		page < totalPages;

	return (
		<div
			className={ `imgv-app imgv-app--${ context }` }
			data-context={ context }
			style={ { '--imgv-cols': String( gridColumns ) } }
		>
			<div className="imgv-app__chrome">
				<header className="imgv-app__header">
					<div className="imgv-app__brand">
						<span className="imgv-app__brand-mark">IMGVerse</span>
					</div>
					<ProviderNav
						provider={ provider }
						onChange={ handleProviderChange }
					/>
				</header>

				<SearchBar
					query={ query }
					provider={ provider }
					source={ source }
					license={ license }
					loading={ loading }
					onQueryChange={ setQuery }
					onSourceChange={ setSource }
					onLicenseChange={ setLicense }
					onSubmit={ handleSubmit }
				/>
			</div>

			<div
				className={
					loading && ! images.length
						? 'imgv-app__body is-loading'
						: 'imgv-app__body'
				}
			>
				{ loading && ! images.length ? (
					<p className="imgv-app__status" role="status">
						Searching…
					</p>
				) : null }

				{ showEmpty && ! ( loading && ! images.length ) ? (
					<EmptyState reason={ reason } message={ errorMessage } />
				) : null }

				{ ! showEmpty ? (
					<PhotoGrid images={ images } context={ context } />
				) : null }

				{ canLoadMore ? (
					<div className="imgv-app__load-more">
						<button
							type="button"
							className="imgv-app__load-more-button"
							onClick={ handleLoadMore }
							disabled={ loadingMore }
						>
							{ loadingMore ? 'Loading…' : 'Load more' }
						</button>
					</div>
				) : null }
			</div>
		</div>
	);
}

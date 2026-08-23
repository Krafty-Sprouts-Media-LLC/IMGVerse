/**
 * Shared IMGVerse React app: provider nav, search, grid, and infinite scroll.
 *
 * @package IMGVerse
 */

/* global imgvData */

import { useEffect, useRef, useState } from '@wordpress/element';
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
 * Whether infinite scroll is enabled (default on).
 *
 * @return {boolean} True when more pages load on scroll.
 */
function getInfiniteScrollEnabled() {
	if ( typeof imgvData === 'undefined' || ! imgvData ) {
		return true;
	}

	if ( 'undefined' === typeof imgvData.infiniteScroll ) {
		return true;
	}

	return Boolean( imgvData.infiniteScroll );
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

	const bodyRef = useRef( null );
	const sentinelRef = useRef( null );
	const loadMoreLock = useRef( false );
	const pageRef = useRef( 1 );
	const totalPagesRef = useRef( 0 );
	const loadingRef = useRef( false );
	const loadingMoreRef = useRef( false );
	const runSearchRef = useRef( null );

	const providersConfig = getProvidersConfig();
	const missingKey = providerMissingKey( provider, providersConfig );
	const gridColumns = getGridColumns();
	const infiniteScroll = getInfiniteScrollEnabled();

	pageRef.current = page;
	totalPagesRef.current = totalPages;
	loadingRef.current = loading;
	loadingMoreRef.current = loadingMore;

	/**
	 * Reset results when the provider changes; keep the search term and re-run.
	 *
	 * @param {string} nextProvider Next provider slug.
	 */
	function handleProviderChange( nextProvider ) {
		if ( nextProvider === provider ) {
			return;
		}

		setProvider( nextProvider );
		setImages( [] );
		setPage( 1 );
		setTotalPages( 0 );
		setEmptyReason( '' );
		setErrorMessage( '' );
		setSource( '' );
		setLicense( '' );

		const trimmed = query.trim();
		const nextMissing = providerMissingKey( nextProvider, providersConfig );

		if ( nextMissing ) {
			setHasSearched( true );
			setEmptyReason( 'missing_api_key' );
			return;
		}

		if ( ! trimmed ) {
			setHasSearched( false );
			return;
		}

		setHasSearched( true );
		runSearch( 1, false, nextProvider );
	}

	/**
	 * Run a search (page 1) or append a page for infinite scroll.
	 *
	 * @param {number}  nextPage           Target page.
	 * @param {boolean} append             Whether to append to existing results.
	 * @param {string}  [providerOverride] Optional provider when switching tabs.
	 */
	async function runSearch( nextPage = 1, append = false, providerOverride ) {
		const activeProvider = providerOverride || provider;
		const activeMissing = providerOverride
			? providerMissingKey( providerOverride, providersConfig )
			: missingKey;

		if ( activeMissing ) {
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
			if ( loadMoreLock.current ) {
				return;
			}
			loadMoreLock.current = true;
			setLoadingMore( true );
		} else {
			loadMoreLock.current = false;
			setLoading( true );
			setEmptyReason( '' );
			setErrorMessage( '' );
		}

		try {
			const result = await searchImages( {
				q: trimmed,
				provider: activeProvider,
				source: 'openverse' === activeProvider ? source : '',
				license: 'openverse' === activeProvider ? license : '',
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
			loadMoreLock.current = false;
		}
	}

	runSearchRef.current = runSearch;

	/**
	 * Submit a fresh search.
	 */
	function handleSubmit() {
		runSearch( 1, false );
	}

	/**
	 * Manual load more when infinite scroll is disabled.
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
	const hasMorePages =
		! missingKey &&
		hasSearched &&
		! emptyReason &&
		images.length > 0 &&
		page < totalPages;
	const canAutoLoadMore = infiniteScroll && hasMorePages && ! loading;
	const canManualLoadMore = ! infiniteScroll && hasMorePages && ! loading;

	useEffect( () => {
		if ( ! canAutoLoadMore ) {
			return undefined;
		}

		const root = bodyRef.current;
		const sentinel = sentinelRef.current;

		if ( ! root || ! sentinel || typeof IntersectionObserver === 'undefined' ) {
			return undefined;
		}

		const observer = new IntersectionObserver(
			( entries ) => {
				const entry = entries[ 0 ];

				if ( ! entry || ! entry.isIntersecting ) {
					return;
				}

				if (
					loadingRef.current ||
					loadingMoreRef.current ||
					loadMoreLock.current
				) {
					return;
				}

				const currentPage = pageRef.current;
				const pages = totalPagesRef.current;

				if ( currentPage >= pages ) {
					return;
				}

				if ( runSearchRef.current ) {
					runSearchRef.current( currentPage + 1, true );
				}
			},
			{
				root,
				rootMargin: '180px 0px',
				threshold: 0,
			}
		);

		observer.observe( sentinel );

		return () => {
			observer.disconnect();
		};
	}, [ canAutoLoadMore, page, totalPages, images.length, provider ] );

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
				ref={ bodyRef }
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

				{ canAutoLoadMore || loadingMore ? (
					<div className="imgv-app__load-more">
						<div ref={ sentinelRef } className="imgv-app__scroll-sentinel" />
						{ loadingMore ? (
							<p className="imgv-app__status" role="status">
								Loading more…
							</p>
						) : null }
					</div>
				) : null }

				{ canManualLoadMore ? (
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

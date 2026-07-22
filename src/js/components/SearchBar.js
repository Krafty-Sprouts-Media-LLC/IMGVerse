/**
 * Search input and Openverse filter controls for IMGVerse.
 *
 * @package IMGVerse
 */

/* global imgvData */

import {
	DEFAULT_OPENVERSE_SOURCES,
	LICENSE_OPTIONS,
} from '../constants/providers';

/**
 * Resolve Openverse sources from localization or defaults.
 *
 * @return {Array<{ value: string, label: string }>} Source options.
 */
function getOpenverseSources() {
	if (
		typeof imgvData !== 'undefined' &&
		imgvData &&
		Array.isArray( imgvData.openverseSources ) &&
		imgvData.openverseSources.length
	) {
		const hasAll = imgvData.openverseSources.some(
			( item ) => '' === item.value
		);

		if ( hasAll ) {
			return imgvData.openverseSources;
		}

		return [
			{ value: '', label: 'All Sources' },
			...imgvData.openverseSources,
		];
	}

	return DEFAULT_OPENVERSE_SOURCES;
}

/**
 * SearchBar component.
 *
 * @param {Object}   props           Component props.
 * @param {string}   props.query     Current query.
 * @param {string}   props.provider  Active provider.
 * @param {string}   props.source    Openverse source.
 * @param {string}   props.license   License filter.
 * @param {boolean}  props.loading   Whether a search is in progress.
 * @param {Function} props.onQueryChange   Query change handler.
 * @param {Function} props.onSourceChange  Source change handler.
 * @param {Function} props.onLicenseChange License change handler.
 * @param {Function} props.onSubmit        Search submit handler.
 * @return {JSX.Element} Search bar markup.
 */
export default function SearchBar( {
	query,
	provider,
	source,
	license,
	loading,
	onQueryChange,
	onSourceChange,
	onLicenseChange,
	onSubmit,
} ) {
	const sources = getOpenverseSources();
	const isOpenverse = 'openverse' === provider;

	/**
	 * Handle form submit.
	 *
	 * @param {Event} event Submit event.
	 */
	function handleSubmit( event ) {
		event.preventDefault();
		onSubmit();
	}

	return (
		<form className="imgv-search-bar" onSubmit={ handleSubmit }>
			<div className="imgv-search-bar__row">
				<label className="imgv-search-bar__field imgv-search-bar__field--query">
					<span className="screen-reader-text">Search images</span>
					<input
						type="search"
						className="imgv-search-bar__input"
						value={ query }
						placeholder="Search images…"
						onChange={ ( event ) =>
							onQueryChange( event.target.value )
						}
						disabled={ loading }
					/>
				</label>
				<button
					type="submit"
					className="imgv-search-bar__submit"
					disabled={ loading || ! query.trim() }
				>
					{ loading ? 'Searching…' : 'Search' }
				</button>
			</div>
			{ isOpenverse ? (
				<div className="imgv-search-bar__filters">
					<label className="imgv-search-bar__field">
						<span className="imgv-search-bar__label">Source</span>
						<select
							className="imgv-search-bar__select"
							value={ source }
							onChange={ ( event ) =>
								onSourceChange( event.target.value )
							}
							disabled={ loading }
						>
							{ sources.map( ( option ) => (
								<option
									key={ option.value || 'all' }
									value={ option.value }
								>
									{ option.label }
								</option>
							) ) }
						</select>
					</label>
					<label className="imgv-search-bar__field">
						<span className="imgv-search-bar__label">License</span>
						<select
							className="imgv-search-bar__select"
							value={ license }
							onChange={ ( event ) =>
								onLicenseChange( event.target.value )
							}
							disabled={ loading }
						>
							{ LICENSE_OPTIONS.map( ( option ) => (
								<option
									key={ option.value || 'all-licenses' }
									value={ option.value }
								>
									{ option.label }
								</option>
							) ) }
						</select>
					</label>
				</div>
			) : null }
		</form>
	);
}

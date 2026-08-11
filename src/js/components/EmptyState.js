/**
 * Empty, welcome, missing API key, and error states for the IMGVerse editor UI.
 *
 * @package IMGVerse
 */

/* global imgvData */

const DEFAULT_STRINGS = {
	missing_api_key:
		'Add an API key in IMGVerse settings to search this provider.',
	missing_api_key_settings: 'Open settings',
	no_results: 'No images found. Try different search terms.',
	error: 'Error occurred. Please try again.',
	welcome:
		'Search millions of free stock photos from Openverse, Unsplash, Pixabay, and Pexels.',
};

/**
 * Read localized strings and settings URL when available in the browser.
 *
 * @return {{ strings: Object, settingsUrl: string }} Localized config.
 */
function getLocalizedConfig() {
	if ( typeof imgvData === 'undefined' ) {
		return {
			strings: {},
			settingsUrl: '',
		};
	}

	return {
		strings: imgvData.strings || {},
		settingsUrl: imgvData.settingsUrl || '',
	};
}

/**
 * EmptyState component.
 *
 * @param {Object} props           Component props.
 * @param {string} props.reason    One of welcome, missing_api_key, no_results, or error.
 * @param {string} [props.message] Optional error detail when reason is error.
 * @return {JSX.Element} Empty state markup.
 */
export default function EmptyState( { reason, message } ) {
	const { strings, settingsUrl } = getLocalizedConfig();
	const merged = { ...DEFAULT_STRINGS, ...strings };

	let body = merged.error;

	if ( 'welcome' === reason ) {
		body = merged.welcome;
	} else if ( 'missing_api_key' === reason ) {
		body = merged.missing_api_key;
	} else if ( 'no_results' === reason ) {
		body = merged.no_results;
	} else if ( 'error' === reason ) {
		body = message || merged.error;
	}

	return (
		<div className="imgv-empty-state" role="status">
			<p className="imgv-empty-state__message">{ body }</p>
			{ 'missing_api_key' === reason && settingsUrl ? (
				<a
					className="imgv-empty-state__settings-link"
					href={ settingsUrl }
				>
					{ merged.missing_api_key_settings }
				</a>
			) : null }
		</div>
	);
}

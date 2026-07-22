/**
 * Provider list and Openverse source constants for the IMGVerse UI.
 *
 * @package IMGVerse
 */

/**
 * Supported image providers shown in the provider nav.
 *
 * @type {Array<{ id: string, label: string }>}
 */
export const PROVIDERS = [
	{ id: 'openverse', label: 'Openverse' },
	{ id: 'unsplash', label: 'Unsplash' },
	{ id: 'pixabay', label: 'Pixabay' },
	{ id: 'pexels', label: 'Pexels' },
];

/**
 * Default Openverse source filter options (includes iNaturalist).
 *
 * @type {Array<{ value: string, label: string }>}
 */
export const DEFAULT_OPENVERSE_SOURCES = [
	{ value: '', label: 'All Sources' },
	{ value: 'flickr', label: 'Flickr' },
	{ value: 'wikimedia', label: 'Wikimedia Commons' },
	{ value: 'inaturalist', label: 'iNaturalist' },
	{ value: 'met', label: 'Metropolitan Museum' },
	{ value: 'nypl', label: 'NYPL' },
	{ value: 'rawpixel', label: 'Rawpixel' },
	{ value: 'smithsonian', label: 'Smithsonian' },
];

/**
 * License filter options for Openverse searches.
 *
 * @type {Array<{ value: string, label: string }>}
 */
export const LICENSE_OPTIONS = [
	{ value: '', label: 'All Licenses' },
	{ value: 'cc0', label: 'CC0' },
	{ value: 'by', label: 'CC BY' },
	{ value: 'by-sa', label: 'CC BY-SA' },
	{ value: 'by-nc', label: 'CC BY-NC' },
	{ value: 'by-nc-sa', label: 'CC BY-NC-SA' },
	{ value: 'by-nc-nd', label: 'CC BY-NC-ND' },
	{ value: 'by-nd', label: 'CC BY-ND' },
];

/**
 * Whether a provider requires an API key that is not configured.
 *
 * @param {string} providerId      Provider slug.
 * @param {Object} providersConfig Localized imgvData.providers map.
 * @return {boolean} True when the UI should show missing_api_key.
 */
export function providerMissingKey( providerId, providersConfig ) {
	if ( ! providersConfig || ! providersConfig[ providerId ] ) {
		return false;
	}

	const meta = providersConfig[ providerId ];

	return Boolean( meta.needsKey ) && ! meta.hasKey;
}

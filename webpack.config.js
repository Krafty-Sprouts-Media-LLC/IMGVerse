/**
 * Webpack configuration for IMGVerse editor bundles.
 *
 * @package IMGVerse
 */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'media-modal': path.resolve( process.cwd(), 'src/js/media-modal.js' ),
		'plugin-sidebar': path.resolve( process.cwd(), 'src/js/plugin-sidebar.js' ),
	},
};

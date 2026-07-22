/**
 * Block editor plugin sidebar: mounts shared App with insert/featured actions.
 *
 * @package IMGVerse
 */

import { registerPlugin } from '@wordpress/plugins';
import {
	PluginSidebar,
	PluginSidebarMoreMenuItem,
} from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import '../scss/style.scss';
import App from './components/App';

/**
 * IMGVerse sidebar plugin render.
 *
 * @return {JSX.Element} Sidebar chrome + App.
 */
function ImgVerseSidebarPlugin() {
	return (
		<>
			<PluginSidebarMoreMenuItem
				target="imgverse-sidebar"
				icon="format-image"
			>
				{ __( 'IMGVerse', 'imgverse' ) }
			</PluginSidebarMoreMenuItem>
			<PluginSidebar
				name="imgverse-sidebar"
				title={ __( 'IMGVerse', 'imgverse' ) }
				icon="format-image"
			>
				<div className="imgv-plugin-sidebar">
					<App context="sidebar" />
				</div>
			</PluginSidebar>
		</>
	);
}

registerPlugin( 'imgverse', {
	render: ImgVerseSidebarPlugin,
	icon: 'format-image',
} );

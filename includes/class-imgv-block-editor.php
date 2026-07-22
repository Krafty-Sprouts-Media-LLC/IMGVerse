<?php
/**
 * IMGVerse Block Editor Integration (legacy stub).
 *
 * React sidebar assets are enqueued by IMGV_Assets::enqueue_sidebar().
 *
 * @package IMGVerse
 * @author Krafty Sprouts Media, LLC
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IMGV_Block_Editor Class
 *
 * Retained for backward-compatible component boot. Block editor scripts
 * now live in build/plugin-sidebar.js via IMGV_Assets.
 *
 * @since 1.0.0
 */
class IMGV_Block_Editor {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Assets: IMGV_Assets::enqueue_sidebar() on enqueue_block_editor_assets.
	}
}

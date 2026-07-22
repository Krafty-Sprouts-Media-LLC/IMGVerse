<?php
/**
 * IMGVerse Media Tab Handler
 *
 * Legacy Backbone templates and assets/js/imgv-media-tab.js were removed in 2.0.
 * React MediaFrame mounting and enqueue live in IMGV_Assets + src/js/media-modal.js.
 *
 * @package IMGVerse
 * @author Krafty Sprouts Media, LLC
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IMGV_Media_Tab Class
 *
 * Kept as a lightweight component hook for compatibility; media scripts are
 * enqueued by IMGV_Assets on wp_enqueue_media.
 *
 * @since 1.0.0
 */
class IMGV_Media_Tab {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// React media modal assets are registered in IMGV_Assets::enqueue_media().
	}
}

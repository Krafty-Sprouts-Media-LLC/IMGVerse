<?php
/**
 * IMGVerse asset enqueue and script localization for the React editor UI.
 *
 * @package IMGVerse
 * @author Krafty Sprouts Media, LLC
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IMGV_Assets class.
 *
 * @since 2.0.0
 */
class IMGV_Assets {

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_action( 'wp_enqueue_media', array( $this, 'enqueue_media' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_sidebar' ) );
	}

	/**
	 * Enqueue the React media-modal bundle when wp_enqueue_media runs.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function enqueue_media() {
		$asset_file = IMGV_PLUGIN_PATH . 'build/media-modal.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;
		$deps  = ( isset( $asset['dependencies'] ) && is_array( $asset['dependencies'] ) )
			? $asset['dependencies']
			: array();
		$deps  = array_values( array_unique( array_merge( $deps, array( 'media-views', 'jquery' ) ) ) );
		$ver   = isset( $asset['version'] ) ? $asset['version'] : IMGV_VERSION;

		wp_enqueue_script(
			'imgv-media-modal',
			IMGV_PLUGIN_URL . 'build/media-modal.js',
			$deps,
			$ver,
			true
		);

		$style_path = IMGV_PLUGIN_PATH . 'build/style-media-modal.css';

		if ( file_exists( $style_path ) ) {
			wp_enqueue_style(
				'imgv-media-modal',
				IMGV_PLUGIN_URL . 'build/style-media-modal.css',
				array(),
				$ver
			);

			if ( file_exists( IMGV_PLUGIN_PATH . 'build/style-media-modal-rtl.css' ) ) {
				wp_style_add_data( 'imgv-media-modal', 'rtl', 'replace' );
			}
		}

		wp_localize_script( 'imgv-media-modal', 'imgvData', $this->get_localize_data() );
	}

	/**
	 * Enqueue the React plugin-sidebar bundle in the block editor.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function enqueue_sidebar() {
		$asset_file = IMGV_PLUGIN_PATH . 'build/plugin-sidebar.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;
		$deps  = ( isset( $asset['dependencies'] ) && is_array( $asset['dependencies'] ) )
			? $asset['dependencies']
			: array();
		$ver   = isset( $asset['version'] ) ? $asset['version'] : IMGV_VERSION;

		wp_enqueue_script(
			'imgv-plugin-sidebar',
			IMGV_PLUGIN_URL . 'build/plugin-sidebar.js',
			$deps,
			$ver,
			true
		);

		$style_path = IMGV_PLUGIN_PATH . 'build/style-media-modal.css';

		if ( file_exists( $style_path ) ) {
			wp_enqueue_style(
				'imgv-plugin-sidebar',
				IMGV_PLUGIN_URL . 'build/style-media-modal.css',
				array(),
				$ver
			);

			if ( file_exists( IMGV_PLUGIN_PATH . 'build/style-media-modal-rtl.css' ) ) {
				wp_style_add_data( 'imgv-plugin-sidebar', 'rtl', 'replace' );
			}
		}

		wp_localize_script( 'imgv-plugin-sidebar', 'imgvData', $this->get_localize_data() );
		wp_set_script_translations( 'imgv-plugin-sidebar', 'imgverse' );
	}

	/**
	 * Build client script data. Never includes raw API keys.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_localize_data() {
		$settings = get_option( 'imgv_settings', array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$post_id = $this->get_current_post_id();

		return array(
			'restUrl'           => esc_url_raw( rest_url( 'imgverse/v1/' ) ),
			'nonce'             => wp_create_nonce( 'wp_rest' ),
			'postId'            => $post_id,
			'settingsUrl'       => esc_url_raw( admin_url( 'options-general.php?page=imgverse-settings' ) ),
			'providers'         => array(
				'openverse' => array(
					'needsKey' => false,
				),
				'unsplash'  => array(
					'needsKey' => true,
					'hasKey'   => ! empty( $settings['unsplash_access_key'] ),
				),
				'pixabay'   => array(
					'needsKey' => true,
					'hasKey'   => ! empty( $settings['pixabay_api_key'] ),
				),
				'pexels'    => array(
					'needsKey' => true,
					'hasKey'   => ! empty( $settings['pexels_api_key'] ),
				),
			),
			'openverseSources'  => $this->get_openverse_sources(),
			'defaultInsertSize' => sanitize_key(
				isset( $settings['default_image_size'] ) ? $settings['default_image_size'] : 'large'
			),
			'resultsPerPage'    => max(
				10,
				min(
					100,
					isset( $settings['results_per_page'] ) ? (int) $settings['results_per_page'] : 20
				)
			),
			'gridColumns'       => max(
				2,
				min(
					6,
					isset( $settings['grid_columns'] ) ? (int) $settings['grid_columns'] : 4
				)
			),
			'tabTitle'          => __( 'IMGVerse', 'imgverse' ),
			'strings'           => array(
				'missing_api_key'          => __(
					'Add an API key in IMGVerse settings to search this provider.',
					'imgverse'
				),
				'missing_api_key_settings' => __( 'Open settings', 'imgverse' ),
				'no_results'               => __(
					'No images found. Try different search terms.',
					'imgverse'
				),
				'error'                    => __(
					'Error occurred. Please try again.',
					'imgverse'
				),
				'welcome'                  => __(
					'Search millions of free stock photos from Openverse, Unsplash, Pixabay, and Pexels.',
					'imgverse'
				),
				'search_placeholder'       => __( 'Search images…', 'imgverse' ),
			),
		);
	}

	/**
	 * Openverse source options for the search filter (includes iNaturalist).
	 *
	 * @since 2.0.0
	 * @return array
	 */
	private function get_openverse_sources() {
		return array(
			array(
				'value' => '',
				'label' => __( 'All Sources', 'imgverse' ),
			),
			array(
				'value' => 'flickr',
				'label' => __( 'Flickr', 'imgverse' ),
			),
			array(
				'value' => 'wikimedia',
				'label' => __( 'Wikimedia Commons', 'imgverse' ),
			),
			array(
				'value' => 'inaturalist',
				'label' => __( 'iNaturalist', 'imgverse' ),
			),
			array(
				'value' => 'met',
				'label' => __( 'Metropolitan Museum', 'imgverse' ),
			),
			array(
				'value' => 'nypl',
				'label' => __( 'NYPL', 'imgverse' ),
			),
			array(
				'value' => 'rawpixel',
				'label' => __( 'Rawpixel', 'imgverse' ),
			),
			array(
				'value' => 'smithsonian',
				'label' => __( 'Smithsonian', 'imgverse' ),
			),
		);
	}

	/**
	 * Best-effort current post ID for import attachment.
	 *
	 * @since 2.0.0
	 * @return int
	 */
	private function get_current_post_id() {
		$post_id = get_the_ID();

		if ( $post_id ) {
			return (int) $post_id;
		}

		if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return absint( $_GET['post'] );
		}

		global $post;

		if ( $post instanceof WP_Post ) {
			return (int) $post->ID;
		}

		return 0;
	}
}

<?php
/**
 * IMGVerse REST API routes for search and import.
 *
 * @package IMGVerse
 * @author Krafty Sprouts Media, LLC
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	if ( ! defined( 'IMGV_TEST_BOOTSTRAP' ) ) {
		exit;
	}
}

/**
 * IMGV_REST class.
 *
 * @since 2.0.0
 */
class IMGV_REST {

	/**
	 * API handler.
	 *
	 * @since 2.0.0
	 * @var IMGV_API
	 */
	private $api;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @param IMGV_API $api API instance.
	 */
	public function __construct( IMGV_API $api ) {
		$this->api = $api;
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes under imgverse/v1.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'imgverse/v1',
			'/search',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search' ),
				'permission_callback' => array( $this, 'can_upload' ),
				'args'                => array(
					'q'        => array(
						'description'       => __( 'Search query.', 'imgverse' ),
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'provider' => array(
						'description'       => __( 'Provider slug.', 'imgverse' ),
						'type'              => 'string',
						'default'           => 'openverse',
						'sanitize_callback' => 'sanitize_key',
					),
					'source'   => array(
						'description'       => __( 'Source filter (Openverse).', 'imgverse' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'license'  => array(
						'description'       => __( 'License filter.', 'imgverse' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'page'     => array(
						'description'       => __( 'Page number.', 'imgverse' ),
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'imgverse/v1',
			'/import',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'import_image' ),
				'permission_callback' => array( $this, 'can_upload' ),
				'args'                => array(
					'url'      => array(
						'description'       => __( 'Full image URL to download.', 'imgverse' ),
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'esc_url_raw',
					),
					'title'    => array(
						'description'       => __( 'Attachment title.', 'imgverse' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'alt'      => array(
						'description'       => __( 'Alt text.', 'imgverse' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'caption'  => array(
						'description'       => __( 'Caption / attribution.', 'imgverse' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'wp_kses_post',
					),
					'provider' => array(
						'description'       => __( 'Provider slug.', 'imgverse' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
					),
					'source'   => array(
						'description'       => __( 'Source slug.', 'imgverse' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
					),
					'post_id'  => array(
						'description'       => __( 'Post to attach the image to.', 'imgverse' ),
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Permission: users who can upload media.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function can_upload() {
		return current_user_can( 'upload_files' );
	}

	/**
	 * GET /imgverse/v1/search
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function search( $request ) {
		$query = $request->get_param( 'q' );
		if ( empty( $query ) ) {
			return new WP_Error(
				'imgv_missing_query',
				__( 'Search query is required.', 'imgverse' ),
				array( 'status' => 400 )
			);
		}

		$provider = $request->get_param( 'provider' );
		if ( empty( $provider ) ) {
			$provider = 'openverse';
		}

		$results = $this->api->search_images(
			$query,
			$provider,
			array(
				'source'  => (string) $request->get_param( 'source' ),
				'license' => (string) $request->get_param( 'license' ),
				'page'    => max( 1, (int) $request->get_param( 'page' ) ),
			)
		);

		return rest_ensure_response( $results );
	}

	/**
	 * POST /imgverse/v1/import
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function import_image( $request ) {
		$url = $request->get_param( 'url' );
		if ( empty( $url ) ) {
			return new WP_Error(
				'imgv_missing_url',
				__( 'Image URL is required.', 'imgverse' ),
				array( 'status' => 400 )
			);
		}

		$post_id = (int) $request->get_param( 'post_id' );
		if ( ! IMGV_API::user_can_attach_to_post( $post_id ) ) {
			return new WP_Error(
				'imgv_cannot_edit_post',
				__( 'You are not allowed to attach media to this post.', 'imgverse' ),
				array( 'status' => 403 )
			);
		}

		$result = $this->api->import_image(
			$url,
			(string) $request->get_param( 'title' ),
			(string) $request->get_param( 'caption' ),
			(string) $request->get_param( 'alt' ),
			'full',
			$post_id,
			(string) $request->get_param( 'provider' ),
			(string) $request->get_param( 'source' )
		);

		if ( empty( $result['success'] ) ) {
			return new WP_Error(
				'imgv_import_failed',
				isset( $result['message'] ) ? $result['message'] : __( 'Import failed.', 'imgverse' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response( $result );
	}
}

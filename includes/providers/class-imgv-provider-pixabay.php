<?php
/**
 * Pixabay image search provider.
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

if ( ! class_exists( 'IMGV_Normalizer' ) ) {
	require_once dirname( __DIR__ ) . '/class-imgv-normalizer.php';
}

if ( ! interface_exists( 'IMGV_Provider_Interface' ) ) {
	require_once __DIR__ . '/class-imgv-provider-interface.php';
}

/**
 * IMGV_Provider_Pixabay class.
 *
 * @since 2.0.0
 */
class IMGV_Provider_Pixabay implements IMGV_Provider_Interface {

	/**
	 * Pixabay search endpoint.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	const ENDPOINT = 'https://pixabay.com/api/';

	/**
	 * Search Pixabay images.
	 *
	 * @since 2.0.0
	 * @param string $query Search query.
	 * @param array  $args  Provider args (page, page_size).
	 * @return array
	 */
	public function search( $query, $args = array() ) {
		$page      = isset( $args['page'] ) ? max( 1, (int) $args['page'] ) : 1;
		$page_size = isset( $args['page_size'] ) ? max( 1, min( 200, (int) $args['page_size'] ) ) : 20;
		$api_key   = $this->get_api_key();

		if ( '' === $api_key ) {
			return $this->missing_key_result( $page );
		}

		$params = array(
			'key'      => $api_key,
			'q'        => $query,
			'page'     => $page,
			'per_page' => $page_size,
			'image_type' => 'photo',
		);

		$url = self::ENDPOINT . '?' . http_build_query( $params );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional failure logging without secrets.
			error_log( 'IMGVerse Pixabay search WP_Error: ' . $error_message );

			return $this->error_result( $page, $error_message );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$data   = json_decode( $body, true );

		if ( 200 !== $status || ! is_array( $data ) || ! isset( $data['hits'] ) || ! is_array( $data['hits'] ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional failure logging without secrets.
			error_log( 'IMGVerse Pixabay search failed: HTTP ' . $status );

			return $this->error_result(
				$page,
				function_exists( '__' ) ? __( 'Invalid API response', 'imgverse' ) : 'Invalid API response'
			);
		}

		$images = array();
		foreach ( $data['hits'] as $raw ) {
			if ( ! is_array( $raw ) ) {
				continue;
			}
			$images[] = self::map_item( $raw );
		}

		$total_results = isset( $data['totalHits'] ) ? (int) $data['totalHits'] : 0;
		$total_pages   = $page_size > 0 ? (int) ceil( $total_results / $page_size ) : 0;

		return array(
			'success'       => true,
			'images'        => $images,
			'page'          => $page,
			'total_pages'   => $total_pages,
			'total_results' => $total_results,
			'message'       => '',
		);
	}

	/**
	 * Map a raw Pixabay hit to the shared normalized shape.
	 *
	 * @since 2.0.0
	 * @param array $raw Pixabay hit item.
	 * @return array
	 */
	public static function map_item( $raw ) {
		$thumb = '';
		if ( ! empty( $raw['previewURL'] ) ) {
			$thumb = $raw['previewURL'];
		} elseif ( ! empty( $raw['webformatURL'] ) ) {
			$thumb = $raw['webformatURL'];
		}

		$full = isset( $raw['largeImageURL'] ) ? $raw['largeImageURL'] : '';
		if ( '' === $full && ! empty( $raw['webformatURL'] ) ) {
			$full = $raw['webformatURL'];
		}

		$title = isset( $raw['tags'] ) ? $raw['tags'] : '';
		$user  = isset( $raw['user'] ) ? $raw['user'] : '';
		$user_id = isset( $raw['user_id'] ) ? $raw['user_id'] : '';

		$user_url = '';
		if ( '' !== $user && '' !== $user_id ) {
			$user_url = 'https://pixabay.com/users/' . rawurlencode( $user ) . '-' . rawurlencode( (string) $user_id ) . '/';
		}

		return IMGV_Normalizer::from_parts(
			array(
				'id'          => isset( $raw['id'] ) ? (string) $raw['id'] : '',
				'title'       => $title,
				'alt'         => $title,
				'thumb'       => $thumb,
				'full'        => $full,
				'user_name'   => $user,
				'user_url'    => $user_url,
				'user_photo'  => isset( $raw['userImageURL'] ) ? $raw['userImageURL'] : '',
				'license'     => 'pixabay',
				'license_url' => 'https://pixabay.com/service/license/',
				'attribution' => '',
				'provider'    => 'pixabay',
				'source'      => 'pixabay',
				'permalink'   => isset( $raw['pageURL'] ) ? $raw['pageURL'] : '',
				'width'       => isset( $raw['imageWidth'] ) ? (int) $raw['imageWidth'] : 0,
				'height'      => isset( $raw['imageHeight'] ) ? (int) $raw['imageHeight'] : 0,
			)
		);
	}

	/**
	 * Read Pixabay API key from settings.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	private function get_api_key() {
		if ( ! function_exists( 'get_option' ) ) {
			return '';
		}
		$settings = get_option( 'imgv_settings', array() );
		return isset( $settings['pixabay_api_key'] ) ? (string) $settings['pixabay_api_key'] : '';
	}

	/**
	 * Build a missing API key payload.
	 *
	 * @since 2.0.0
	 * @param int $page Page number.
	 * @return array
	 */
	private function missing_key_result( $page ) {
		return array(
			'success'       => false,
			'code'          => 'missing_api_key',
			'images'        => array(),
			'page'          => (int) $page,
			'total_pages'   => 0,
			'total_results' => 0,
			'message'       => function_exists( '__' )
				? __( 'Add your Pixabay API Key in IMGVerse settings to search Pixabay.', 'imgverse' )
				: 'Add your Pixabay API Key in IMGVerse settings to search Pixabay.',
		);
	}

	/**
	 * Build a failed search payload.
	 *
	 * @since 2.0.0
	 * @param int    $page    Page number.
	 * @param string $message Error message.
	 * @return array
	 */
	private function error_result( $page, $message ) {
		return array(
			'success'       => false,
			'images'        => array(),
			'page'          => (int) $page,
			'total_pages'   => 0,
			'total_results' => 0,
			'message'       => (string) $message,
		);
	}
}

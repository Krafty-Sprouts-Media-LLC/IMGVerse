<?php
/**
 * Pexels image search provider.
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
 * IMGV_Provider_Pexels class.
 *
 * @since 2.0.0
 */
class IMGV_Provider_Pexels implements IMGV_Provider_Interface {

	/**
	 * Pexels search endpoint.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	const ENDPOINT = 'https://api.pexels.com/v1/search';

	/**
	 * Search Pexels images.
	 *
	 * @since 2.0.0
	 * @param string $query Search query.
	 * @param array  $args  Provider args (page, page_size).
	 * @return array
	 */
	public function search( $query, $args = array() ) {
		$page      = isset( $args['page'] ) ? max( 1, (int) $args['page'] ) : 1;
		$page_size = isset( $args['page_size'] ) ? max( 1, min( 80, (int) $args['page_size'] ) ) : 20;
		$api_key   = $this->get_api_key();

		if ( '' === $api_key ) {
			return $this->missing_key_result( $page );
		}

		$params = array(
			'query'    => $query,
			'page'     => $page,
			'per_page' => $page_size,
		);

		$url = self::ENDPOINT . '?' . http_build_query( $params );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => $api_key,
					'Accept'        => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional failure logging without secrets.
			error_log( 'IMGVerse Pexels search WP_Error: ' . $error_message );

			return $this->error_result( $page, $error_message );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$data   = json_decode( $body, true );

		if ( 200 !== $status || ! is_array( $data ) || ! isset( $data['photos'] ) || ! is_array( $data['photos'] ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional failure logging without secrets.
			error_log( 'IMGVerse Pexels search failed: HTTP ' . $status );

			return $this->error_result(
				$page,
				function_exists( '__' ) ? __( 'Invalid API response', 'imgverse' ) : 'Invalid API response'
			);
		}

		$images = array();
		foreach ( $data['photos'] as $raw ) {
			if ( ! is_array( $raw ) ) {
				continue;
			}
			$images[] = self::map_item( $raw );
		}

		$total_results = isset( $data['total_results'] ) ? (int) $data['total_results'] : 0;
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
	 * Map a raw Pexels photo to the shared normalized shape.
	 *
	 * @since 2.0.0
	 * @param array $raw Pexels photo item.
	 * @return array
	 */
	public static function map_item( $raw ) {
		$src = isset( $raw['src'] ) && is_array( $raw['src'] ) ? $raw['src'] : array();

		$thumb = isset( $src['medium'] ) ? $src['medium'] : '';

		$full = '';
		if ( ! empty( $src['large2x'] ) ) {
			$full = $src['large2x'];
		} elseif ( ! empty( $src['original'] ) ) {
			$full = $src['original'];
		}

		$title = isset( $raw['alt'] ) ? $raw['alt'] : '';

		return IMGV_Normalizer::from_parts(
			array(
				'id'          => isset( $raw['id'] ) ? (string) $raw['id'] : '',
				'title'       => $title,
				'alt'         => $title,
				'thumb'       => $thumb,
				'full'        => $full,
				'user_name'   => isset( $raw['photographer'] ) ? $raw['photographer'] : '',
				'user_url'    => isset( $raw['photographer_url'] ) ? $raw['photographer_url'] : '',
				'user_photo'  => '',
				'license'     => 'pexels',
				'license_url' => 'https://www.pexels.com/license/',
				'attribution' => '',
				'provider'    => 'pexels',
				'source'      => 'pexels',
				'permalink'   => isset( $raw['url'] ) ? $raw['url'] : '',
				'width'       => isset( $raw['width'] ) ? (int) $raw['width'] : 0,
				'height'      => isset( $raw['height'] ) ? (int) $raw['height'] : 0,
			)
		);
	}

	/**
	 * Read Pexels API key from settings.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	private function get_api_key() {
		if ( ! function_exists( 'get_option' ) ) {
			return '';
		}
		$settings = get_option( 'imgv_settings', array() );
		return isset( $settings['pexels_api_key'] ) ? (string) $settings['pexels_api_key'] : '';
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
				? __( 'Add your Pexels API Key in IMGVerse settings to search Pexels.', 'imgverse' )
				: 'Add your Pexels API Key in IMGVerse settings to search Pexels.',
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

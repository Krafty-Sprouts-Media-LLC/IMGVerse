<?php
/**
 * Unsplash image search provider.
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
 * IMGV_Provider_Unsplash class.
 *
 * @since 2.0.0
 */
class IMGV_Provider_Unsplash implements IMGV_Provider_Interface {

	/**
	 * Unsplash search endpoint.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	const ENDPOINT = 'https://api.unsplash.com/search/photos';

	/**
	 * Search Unsplash images.
	 *
	 * @since 2.0.0
	 * @param string $query Search query.
	 * @param array  $args  Provider args (page, page_size).
	 * @return array
	 */
	public function search( $query, $args = array() ) {
		$page      = isset( $args['page'] ) ? max( 1, (int) $args['page'] ) : 1;
		$page_size = isset( $args['page_size'] ) ? max( 1, (int) $args['page_size'] ) : 20;
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
					'Authorization' => 'Client-ID ' . $api_key,
					'Accept'        => 'application/json',
					'Accept-Version' => 'v1',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional failure logging without secrets.
			error_log( 'IMGVerse Unsplash search WP_Error: ' . $error_message );

			return $this->error_result( $page, $error_message );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$data   = json_decode( $body, true );

		if ( 200 !== $status || ! is_array( $data ) || ! isset( $data['results'] ) || ! is_array( $data['results'] ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional failure logging without secrets.
			error_log( 'IMGVerse Unsplash search failed: HTTP ' . $status );

			return $this->error_result(
				$page,
				function_exists( '__' ) ? __( 'Invalid API response', 'imgverse' ) : 'Invalid API response'
			);
		}

		$images = array();
		foreach ( $data['results'] as $raw ) {
			if ( ! is_array( $raw ) ) {
				continue;
			}
			$images[] = self::map_item( $raw );
		}

		$total_results = isset( $data['total'] ) ? (int) $data['total'] : 0;
		$total_pages   = isset( $data['total_pages'] ) ? (int) $data['total_pages'] : (int) ceil( $total_results / $page_size );

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
	 * Map a raw Unsplash result to the shared normalized shape.
	 *
	 * @since 2.0.0
	 * @param array $raw Unsplash photo item.
	 * @return array
	 */
	public static function map_item( $raw ) {
		$urls  = isset( $raw['urls'] ) && is_array( $raw['urls'] ) ? $raw['urls'] : array();
		$user  = isset( $raw['user'] ) && is_array( $raw['user'] ) ? $raw['user'] : array();
		$links = isset( $raw['links'] ) && is_array( $raw['links'] ) ? $raw['links'] : array();

		$thumb = '';
		if ( ! empty( $urls['small'] ) ) {
			$thumb = $urls['small'];
		} elseif ( ! empty( $urls['thumb'] ) ) {
			$thumb = $urls['thumb'];
		}

		$full = '';
		if ( ! empty( $urls['regular'] ) ) {
			$full = $urls['regular'];
		} elseif ( ! empty( $urls['full'] ) ) {
			$full = $urls['full'];
		}

		$title = '';
		if ( ! empty( $raw['description'] ) ) {
			$title = $raw['description'];
		} elseif ( ! empty( $raw['alt_description'] ) ) {
			$title = $raw['alt_description'];
		}

		$user_url = '';
		if ( isset( $user['links'] ) && is_array( $user['links'] ) && ! empty( $user['links']['html'] ) ) {
			$user_url = $user['links']['html'];
		}

		$user_photo = '';
		if ( isset( $user['profile_image'] ) && is_array( $user['profile_image'] ) && ! empty( $user['profile_image']['small'] ) ) {
			$user_photo = $user['profile_image']['small'];
		}

		return IMGV_Normalizer::from_parts(
			array(
				'id'          => isset( $raw['id'] ) ? $raw['id'] : '',
				'title'       => $title,
				'alt'         => ! empty( $raw['alt_description'] ) ? $raw['alt_description'] : $title,
				'thumb'       => $thumb,
				'full'        => $full,
				'user_name'   => isset( $user['name'] ) ? $user['name'] : '',
				'user_url'    => $user_url,
				'user_photo'  => $user_photo,
				'license'     => 'unsplash',
				'license_url' => 'https://unsplash.com/license',
				'attribution' => '',
				'provider'    => 'unsplash',
				'source'      => 'unsplash',
				'permalink'   => isset( $links['html'] ) ? $links['html'] : '',
				'width'       => isset( $raw['width'] ) ? (int) $raw['width'] : 0,
				'height'      => isset( $raw['height'] ) ? (int) $raw['height'] : 0,
			)
		);
	}

	/**
	 * Read Unsplash access key from settings.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	private function get_api_key() {
		if ( ! function_exists( 'get_option' ) ) {
			return '';
		}
		$settings = get_option( 'imgv_settings', array() );
		return isset( $settings['unsplash_access_key'] ) ? (string) $settings['unsplash_access_key'] : '';
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
				? __( 'Add your Unsplash Access Key in IMGVerse settings to search Unsplash.', 'imgverse' )
				: 'Add your Unsplash Access Key in IMGVerse settings to search Unsplash.',
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

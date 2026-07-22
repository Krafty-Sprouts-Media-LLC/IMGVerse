<?php
/**
 * Openverse image search provider (includes iNaturalist source).
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
 * IMGV_Provider_Openverse class.
 *
 * @since 2.0.0
 */
class IMGV_Provider_Openverse implements IMGV_Provider_Interface {

	/**
	 * Openverse images endpoint.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	const ENDPOINT = 'https://api.openverse.org/v1/images/';

	/**
	 * Search Openverse images.
	 *
	 * @since 2.0.0
	 * @param string $query Search query.
	 * @param array  $args  Provider args (source, license, page, page_size).
	 * @return array
	 */
	public function search( $query, $args = array() ) {
		$page      = isset( $args['page'] ) ? max( 1, (int) $args['page'] ) : 1;
		$page_size = isset( $args['page_size'] ) ? max( 1, (int) $args['page_size'] ) : 20;
		$source    = isset( $args['source'] ) ? sanitize_key( $args['source'] ) : '';
		$license   = isset( $args['license'] ) ? sanitize_text_field( $args['license'] ) : '';

		$params = array(
			'q'         => $query,
			'page'      => $page,
			'page_size' => $page_size,
		);

		if ( '' !== $source ) {
			$params['source'] = $source;
		}

		if ( '' !== $license ) {
			$params['license'] = $license;
		}

		$version = defined( 'IMGV_VERSION' ) ? IMGV_VERSION : '2.0.0';
		$url     = self::ENDPOINT . '?' . http_build_query( $params );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'User-Agent' => 'IMGVerseWordPressPlugin/' . $version,
					'Accept'     => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional failure logging without secrets.
			error_log( 'IMGVerse Openverse search WP_Error: ' . $error_message );

			return $this->error_result( $page, $error_message );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$data   = json_decode( $body, true );

		if ( 200 !== $status || ! is_array( $data ) || ! isset( $data['results'] ) || ! is_array( $data['results'] ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional failure logging without secrets.
			error_log( 'IMGVerse Openverse search failed: HTTP ' . $status );

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

		$total_results = isset( $data['result_count'] ) ? (int) $data['result_count'] : 0;
		$total_pages   = isset( $data['page_count'] ) ? (int) $data['page_count'] : (int) ceil( $total_results / $page_size );

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
	 * Map a raw Openverse result to the shared normalized shape.
	 *
	 * @since 2.0.0
	 * @param array $raw Openverse result item.
	 * @return array
	 */
	public static function map_item( $raw ) {
		$title = isset( $raw['title'] ) ? $raw['title'] : '';
		$full  = isset( $raw['url'] ) ? $raw['url'] : '';
		$thumb = isset( $raw['thumbnail'] ) ? $raw['thumbnail'] : '';

		return IMGV_Normalizer::from_parts(
			array(
				'id'          => isset( $raw['id'] ) ? $raw['id'] : '',
				'title'       => $title,
				'alt'         => $title,
				'thumb'       => $thumb,
				'full'        => $full,
				'user_name'   => isset( $raw['creator'] ) ? $raw['creator'] : '',
				'user_url'    => isset( $raw['creator_url'] ) ? $raw['creator_url'] : '',
				'user_photo'  => '',
				'license'     => isset( $raw['license'] ) ? $raw['license'] : '',
				'license_url' => isset( $raw['license_url'] ) ? $raw['license_url'] : '',
				'attribution' => isset( $raw['attribution'] ) ? $raw['attribution'] : '',
				'provider'    => 'openverse',
				'source'      => isset( $raw['source'] ) ? $raw['source'] : '',
				'permalink'   => isset( $raw['foreign_landing_url'] ) ? $raw['foreign_landing_url'] : '',
			)
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

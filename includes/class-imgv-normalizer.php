<?php
/**
 * Normalize provider results to a shared shape.
 *
 * @package IMGVerse
 * @author Krafty Sprouts Media, LLC
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Allow unit tests to load without WordPress when ABSPATH undefined.
	if ( ! defined( 'IMGV_TEST_BOOTSTRAP' ) ) {
		exit;
	}
}

/**
 * IMGV_Normalizer class.
 *
 * @since 2.0.0
 */
class IMGV_Normalizer {

	/**
	 * Build a normalized result array.
	 *
	 * @since 2.0.0
	 * @param array $parts Input parts.
	 * @return array
	 */
	public static function from_parts( $parts ) {
		$full  = isset( $parts['full'] ) ? esc_url_raw( $parts['full'] ) : '';
		$thumb = isset( $parts['thumb'] ) ? esc_url_raw( $parts['thumb'] ) : '';
		if ( '' === $thumb ) {
			$thumb = $full;
		}

		$width  = isset( $parts['width'] ) ? (int) $parts['width'] : 0;
		$height = isset( $parts['height'] ) ? (int) $parts['height'] : 0;
		if ( $width < 0 ) {
			$width = 0;
		}
		if ( $height < 0 ) {
			$height = 0;
		}

		return array(
			'id'          => sanitize_text_field( $parts['id'] ?? '' ),
			'title'       => sanitize_text_field( $parts['title'] ?? '' ),
			'alt'         => sanitize_text_field( $parts['alt'] ?? '' ),
			'urls'        => array(
				'thumb' => $thumb,
				'full'  => $full,
			),
			'width'       => $width,
			'height'      => $height,
			'user'        => array(
				'name'  => sanitize_text_field( $parts['user_name'] ?? '' ),
				'url'   => esc_url_raw( $parts['user_url'] ?? '' ),
				'photo' => esc_url_raw( $parts['user_photo'] ?? '' ),
			),
			'license'     => sanitize_text_field( $parts['license'] ?? '' ),
			'license_url' => esc_url_raw( $parts['license_url'] ?? '' ),
			'attribution' => wp_kses_post( $parts['attribution'] ?? '' ),
			'provider'    => sanitize_key( $parts['provider'] ?? '' ),
			'source'      => sanitize_key( $parts['source'] ?? '' ),
			'permalink'   => esc_url_raw( $parts['permalink'] ?? '' ),
		);
	}
}

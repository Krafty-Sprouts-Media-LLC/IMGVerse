<?php
/**
 * PHPUnit bootstrap for IMGVerse unit tests.
 *
 * @package IMGVerse
 * @since 2.0.0
 */

define( 'IMGV_TEST_BOOTSTRAP', true );

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * Stub esc_url_raw for unit tests.
	 *
	 * @since 2.0.0
	 * @param string $url URL.
	 * @return string
	 */
	function esc_url_raw( $url ) {
		return (string) $url;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Stub sanitize_text_field for unit tests.
	 *
	 * @since 2.0.0
	 * @param string $str String.
	 * @return string
	 */
	function sanitize_text_field( $str ) {
		return (string) $str;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * Stub sanitize_key for unit tests.
	 *
	 * @since 2.0.0
	 * @param string $key Key.
	 * @return string
	 */
	function sanitize_key( $key ) {
		return (string) $key;
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * Stub wp_kses_post for unit tests.
	 *
	 * @since 2.0.0
	 * @param string $data Data.
	 * @return string
	 */
	function wp_kses_post( $data ) {
		return (string) $data;
	}
}

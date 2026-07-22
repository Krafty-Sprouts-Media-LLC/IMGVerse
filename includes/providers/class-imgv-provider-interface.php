<?php
/**
 * Provider search contract.
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
 * IMGV_Provider_Interface interface.
 *
 * @since 2.0.0
 */
interface IMGV_Provider_Interface {

	/**
	 * Search the provider for images.
	 *
	 * @since 2.0.0
	 * @param string $query Search query.
	 * @param array  $args  Provider args.
	 * @return array {
	 *     @type bool   $success       Whether the search succeeded.
	 *     @type array  $images        Normalized image results.
	 *     @type int    $page          Current page.
	 *     @type int    $total_pages   Total pages.
	 *     @type int    $total_results Total result count.
	 *     @type string $message       Status or error message.
	 * }
	 */
	public function search( $query, $args = array() );
}

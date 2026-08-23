<?php
/**
 * Tests for IMGV_Provider_Openverse.
 *
 * @package IMGVerse
 * @since 2.0.0
 */

class Test_IMGV_Provider_Openverse extends PHPUnit\Framework\TestCase {

	/**
	 * Openverse-shaped fixture including iNaturalist source.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	private function inaturalist_fixture() {
		return array(
			'id'                  => 'c2da0d66-f9e8-4dfd-81eb-844eed176a4c',
			'title'               => 'indigo-birds, whydahs',
			'url'                 => 'https://inaturalist-open-data.s3.amazonaws.com/photos/54771256/original.jpeg',
			'thumbnail'           => 'https://api.openverse.org/v1/images/c2da0d66-f9e8-4dfd-81eb-844eed176a4c/thumb/',
			'creator'             => 'Abubakar S. Ringim',
			'creator_url'         => 'https://www.inaturalist.org/users/1106223',
			'license'             => 'by-nc',
			'license_url'         => 'http://creativecommons.org/licenses/by-nc/4.0/',
			'foreign_landing_url' => 'https://www.inaturalist.org/photos/54771256',
			'attribution'         => '"indigo-birds, whydahs" by Abubakar S. Ringim is licensed under CC BY-NC 4.0.',
			'provider'            => 'inaturalist',
			'source'              => 'inaturalist',
			'width'               => 2048,
			'height'              => 1365,
		);
	}

	public function test_map_item_preserves_inaturalist_source_and_full_url() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-imgv-normalizer.php';
		require_once dirname( __DIR__, 2 ) . '/includes/providers/class-imgv-provider-interface.php';
		require_once dirname( __DIR__, 2 ) . '/includes/providers/class-imgv-provider-openverse.php';

		$result = IMGV_Provider_Openverse::map_item( $this->inaturalist_fixture() );

		$this->assertSame( 'inaturalist', $result['source'] );
		$this->assertSame(
			'https://inaturalist-open-data.s3.amazonaws.com/photos/54771256/original.jpeg',
			$result['urls']['full']
		);
		$this->assertSame( 'openverse', $result['provider'] );
		$this->assertSame( '', $result['attribution'] );
		$this->assertSame( 2048, $result['width'] );
		$this->assertSame( 1365, $result['height'] );
	}
}

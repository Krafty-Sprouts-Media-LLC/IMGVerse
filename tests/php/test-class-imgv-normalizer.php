<?php
/**
 * Tests for IMGV_Normalizer.
 *
 * @package IMGVerse
 * @since 2.0.0
 */

class Test_IMGV_Normalizer extends PHPUnit\Framework\TestCase {

	public function test_normalize_requires_id_and_full_url() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-imgv-normalizer.php';

		$result = IMGV_Normalizer::from_parts(
			array(
				'id'          => 'abc',
				'title'       => 'Millipede',
				'alt'         => 'Illacme',
				'thumb'       => 'https://example.com/t.jpg',
				'full'        => 'https://example.com/f.jpg',
				'user_name'   => 'Ada',
				'user_url'    => 'https://example.com/ada',
				'user_photo'  => '',
				'license'     => 'by',
				'license_url' => 'https://creativecommons.org/licenses/by/4.0/',
				'attribution' => '"Millipede" by Ada',
				'provider'    => 'openverse',
				'source'      => 'inaturalist',
				'permalink'   => 'https://example.com/photo',
			)
		);

		$this->assertSame( 'abc', $result['id'] );
		$this->assertSame( 'https://example.com/t.jpg', $result['urls']['thumb'] );
		$this->assertSame( 'https://example.com/f.jpg', $result['urls']['full'] );
		$this->assertSame( 'inaturalist', $result['source'] );
		$this->assertSame( 'openverse', $result['provider'] );
		$this->assertSame( 0, $result['width'] );
		$this->assertSame( 0, $result['height'] );
	}

	public function test_normalize_falls_back_thumb_to_full_when_thumb_empty() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-imgv-normalizer.php';

		$result = IMGV_Normalizer::from_parts(
			array(
				'id'          => 'x',
				'title'       => 'T',
				'alt'         => '',
				'thumb'       => '',
				'full'        => 'https://example.com/f.jpg',
				'user_name'   => '',
				'user_url'    => '',
				'user_photo'  => '',
				'license'     => '',
				'license_url' => '',
				'attribution' => '',
				'provider'    => 'unsplash',
				'source'      => 'unsplash',
				'permalink'   => '',
			)
		);

		$this->assertSame( 'https://example.com/f.jpg', $result['urls']['thumb'] );
	}

	public function test_normalize_includes_dimensions() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-imgv-normalizer.php';

		$result = IMGV_Normalizer::from_parts(
			array(
				'id'     => 'dim',
				'title'  => 'Wide',
				'full'   => 'https://example.com/f.jpg',
				'width'  => 1920,
				'height' => 1080,
			)
		);

		$this->assertSame( 1920, $result['width'] );
		$this->assertSame( 1080, $result['height'] );
	}
}

<?php
/**
 * Tests for Unsplash, Pixabay, and Pexels map_item fixtures.
 *
 * @package IMGVerse
 * @since 2.0.0
 */

class Test_IMGV_Provider_Mappers extends PHPUnit\Framework\TestCase {

	/**
	 * Unsplash-shaped search photo fixture.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	private function unsplash_fixture() {
		return array(
			'id'          => 'abc123',
			'description' => 'Ocean waves at sunset',
			'alt_description' => 'ocean waves',
			'urls'        => array(
				'raw'     => 'https://images.unsplash.com/photo-raw',
				'full'    => 'https://images.unsplash.com/photo-full',
				'regular' => 'https://images.unsplash.com/photo-regular',
				'small'   => 'https://images.unsplash.com/photo-small',
				'thumb'   => 'https://images.unsplash.com/photo-thumb',
			),
			'user'        => array(
				'name'     => 'Jane Doe',
				'username' => 'janedoe',
				'links'    => array(
					'html' => 'https://unsplash.com/@janedoe',
				),
				'profile_image' => array(
					'small' => 'https://images.unsplash.com/profile-small',
				),
			),
			'links'       => array(
				'html' => 'https://unsplash.com/photos/abc123',
			),
		);
	}

	/**
	 * Pixabay-shaped hit fixture.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	private function pixabay_fixture() {
		return array(
			'id'            => 123456,
			'tags'          => 'ocean, waves, sunset',
			'previewURL'    => 'https://cdn.pixabay.com/photo-preview.jpg',
			'webformatURL'  => 'https://cdn.pixabay.com/photo-web.jpg',
			'largeImageURL' => 'https://cdn.pixabay.com/photo-large.jpg',
			'user'          => 'pixabay_user',
			'user_id'       => 99,
			'pageURL'       => 'https://pixabay.com/photos/ocean-123456/',
			'userImageURL'  => 'https://cdn.pixabay.com/user.jpg',
		);
	}

	/**
	 * Pexels-shaped photo fixture.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	private function pexels_fixture() {
		return array(
			'id'               => 987654,
			'alt'              => 'Blue ocean water',
			'url'              => 'https://www.pexels.com/photo/blue-ocean-987654/',
			'photographer'     => 'Alex Photographer',
			'photographer_url' => 'https://www.pexels.com/@alex',
			'src'              => array(
				'original' => 'https://images.pexels.com/photos/987654/original.jpeg',
				'large2x'  => 'https://images.pexels.com/photos/987654/large2x.jpeg',
				'large'    => 'https://images.pexels.com/photos/987654/large.jpeg',
				'medium'   => 'https://images.pexels.com/photos/987654/medium.jpeg',
				'small'    => 'https://images.pexels.com/photos/987654/small.jpeg',
			),
		);
	}

	public function test_unsplash_map_item_uses_regular_and_small_urls() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-imgv-normalizer.php';
		require_once dirname( __DIR__, 2 ) . '/includes/providers/class-imgv-provider-interface.php';
		require_once dirname( __DIR__, 2 ) . '/includes/providers/class-imgv-provider-unsplash.php';

		$result = IMGV_Provider_Unsplash::map_item( $this->unsplash_fixture() );

		$this->assertSame( 'abc123', $result['id'] );
		$this->assertSame( 'https://images.unsplash.com/photo-small', $result['urls']['thumb'] );
		$this->assertSame( 'https://images.unsplash.com/photo-regular', $result['urls']['full'] );
		$this->assertSame( 'unsplash', $result['provider'] );
		$this->assertSame( 'unsplash', $result['source'] );
		$this->assertSame( 'Jane Doe', $result['user']['name'] );
	}

	public function test_pixabay_map_item_uses_preview_and_large_urls() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-imgv-normalizer.php';
		require_once dirname( __DIR__, 2 ) . '/includes/providers/class-imgv-provider-interface.php';
		require_once dirname( __DIR__, 2 ) . '/includes/providers/class-imgv-provider-pixabay.php';

		$result = IMGV_Provider_Pixabay::map_item( $this->pixabay_fixture() );

		$this->assertSame( '123456', $result['id'] );
		$this->assertSame( 'https://cdn.pixabay.com/photo-preview.jpg', $result['urls']['thumb'] );
		$this->assertSame( 'https://cdn.pixabay.com/photo-large.jpg', $result['urls']['full'] );
		$this->assertSame( 'pixabay', $result['provider'] );
		$this->assertSame( 'pixabay', $result['source'] );
		$this->assertSame( 'pixabay_user', $result['user']['name'] );
	}

	public function test_pexels_map_item_uses_medium_and_large2x_urls() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-imgv-normalizer.php';
		require_once dirname( __DIR__, 2 ) . '/includes/providers/class-imgv-provider-interface.php';
		require_once dirname( __DIR__, 2 ) . '/includes/providers/class-imgv-provider-pexels.php';

		$result = IMGV_Provider_Pexels::map_item( $this->pexels_fixture() );

		$this->assertSame( '987654', $result['id'] );
		$this->assertSame( 'https://images.pexels.com/photos/987654/medium.jpeg', $result['urls']['thumb'] );
		$this->assertSame( 'https://images.pexels.com/photos/987654/large2x.jpeg', $result['urls']['full'] );
		$this->assertSame( 'pexels', $result['provider'] );
		$this->assertSame( 'pexels', $result['source'] );
		$this->assertSame( 'Alex Photographer', $result['user']['name'] );
	}
}

<?php
/**
 * Tests for IMGV_API::maybe_resize_file and REST upload permission.
 *
 * @package IMGVerse
 * @since 2.0.0
 */

/**
 * Minimal WP_Error stand-in for unit tests.
 *
 * @since 2.0.0
 */
if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * WP_Error stub.
	 *
	 * @since 2.0.0
	 */
	class WP_Error {
		/**
		 * Error code.
		 *
		 * @var string
		 */
		public $code;

		/**
		 * Error message.
		 *
		 * @var string
		 */
		public $message;

		/**
		 * Constructor.
		 *
		 * @param string $code    Code.
		 * @param string $message Message.
		 */
		public function __construct( $code = '', $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}

		/**
		 * Get error message.
		 *
		 * @return string
		 */
		public function get_error_message() {
			return (string) $this->message;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * Stub is_wp_error.
	 *
	 * @param mixed $thing Value.
	 * @return bool
	 */
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Stub translate.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	/**
	 * Stub current_user_can (overridable via $GLOBALS).
	 *
	 * @param string $cap Capability.
	 * @return bool
	 */
	function current_user_can( $cap ) {
		if ( isset( $GLOBALS['imgv_test_caps'][ $cap ] ) ) {
			return (bool) $GLOBALS['imgv_test_caps'][ $cap ];
		}
		return false;
	}
}

/**
 * GD-backed image editor stub used by wp_get_image_editor.
 *
 * @since 2.0.0
 */
class IMGV_Test_GD_Image_Editor {

	/**
	 * File path.
	 *
	 * @var string
	 */
	private $file;

	/**
	 * GD resource/object.
	 *
	 * @var resource|\GdImage
	 */
	private $image;

	/**
	 * Current size.
	 *
	 * @var array
	 */
	private $size;

	/**
	 * Constructor.
	 *
	 * @param string $file File path.
	 */
	public function __construct( $file ) {
		$this->file  = $file;
		$info        = getimagesize( $file );
		$this->size  = array(
			'width'  => (int) $info[0],
			'height' => (int) $info[1],
		);
		$this->image = imagecreatefromjpeg( $file );
	}

	/**
	 * Get size.
	 *
	 * @return array
	 */
	public function get_size() {
		return $this->size;
	}

	/**
	 * Resize preserving aspect ratio (no upscale).
	 *
	 * @param int|null $max_w Max width.
	 * @param int|null $max_h Max height.
	 * @param bool     $crop  Unused.
	 * @return true|WP_Error
	 */
	public function resize( $max_w = null, $max_h = null, $crop = false ) {
		unset( $crop );
		$w = $this->size['width'];
		$h = $this->size['height'];

		$max_w = ( null === $max_w || (int) $max_w <= 0 ) ? $w : (int) $max_w;
		$max_h = ( null === $max_h || (int) $max_h <= 0 ) ? $h : (int) $max_h;

		$ratio = min( $max_w / $w, $max_h / $h, 1 );
		$nw    = (int) max( 1, round( $w * $ratio ) );
		$nh    = (int) max( 1, round( $h * $ratio ) );

		$dst = imagecreatetruecolor( $nw, $nh );
		if ( false === $dst ) {
			return new WP_Error( 'imgv_test_resize', 'Failed to create destination image.' );
		}

		imagecopyresampled( $dst, $this->image, 0, 0, 0, 0, $nw, $nh, $w, $h );
		imagedestroy( $this->image );
		$this->image = $dst;
		$this->size  = array(
			'width'  => $nw,
			'height' => $nh,
		);

		return true;
	}

	/**
	 * Save over the source file.
	 *
	 * @param string|null $dest Destination path.
	 * @return array|WP_Error
	 */
	public function save( $dest = null ) {
		$path = $dest ? $dest : $this->file;
		if ( ! imagejpeg( $this->image, $path, 90 ) ) {
			return new WP_Error( 'imgv_test_save', 'Failed to save image.' );
		}
		return array( 'path' => $path );
	}
}

if ( ! function_exists( 'wp_get_image_editor' ) ) {
	/**
	 * Stub wp_get_image_editor using GD.
	 *
	 * @param string $file File path.
	 * @return IMGV_Test_GD_Image_Editor|WP_Error
	 */
	function wp_get_image_editor( $file ) {
		if ( ! file_exists( $file ) ) {
			return new WP_Error( 'imgv_test_missing', 'Missing file.' );
		}
		return new IMGV_Test_GD_Image_Editor( $file );
	}
}

/**
 * Test_IMGV_API_Resize class.
 *
 * @since 2.0.0
 */
class Test_IMGV_API_Resize extends PHPUnit\Framework\TestCase {

	/**
	 * Load API class once.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function load_api() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-imgv-api.php';
	}

	/**
	 * Create a temporary JPEG for resize tests.
	 *
	 * @since 2.0.0
	 * @param int $width  Width.
	 * @param int $height Height.
	 * @return string Path.
	 */
	private function create_temp_jpeg( $width, $height ) {
		$path  = tempnam( sys_get_temp_dir(), 'imgv' ) . '.jpg';
		$image = imagecreatetruecolor( $width, $height );
		$color = imagecolorallocate( $image, 40, 120, 200 );
		imagefilledrectangle( $image, 0, 0, $width, $height, $color );
		imagejpeg( $image, $path, 90 );
		imagedestroy( $image );
		return $path;
	}

	/**
	 * Resize helper shrinks oversized JPEGs using max W/H.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_maybe_resize_file_shrinks_oversized_jpeg() {
		if ( ! function_exists( 'imagecreatetruecolor' ) || ! function_exists( 'imagejpeg' ) ) {
			$this->markTestSkipped( 'GD extension is required for resize unit tests.' );
		}

		$this->load_api();

		$path = $this->create_temp_jpeg( 3000, 2000 );
		try {
			$result = IMGV_API::maybe_resize_file( $path, 800, 800 );
			$this->assertTrue( $result );

			$info = getimagesize( $path );
			$this->assertNotFalse( $info );
			$this->assertLessThanOrEqual( 800, $info[0] );
			$this->assertLessThanOrEqual( 800, $info[1] );
			// Aspect ratio ~3:2 preserved (800x533).
			$this->assertSame( 800, $info[0] );
			$this->assertSame( 533, $info[1] );
		} finally {
			if ( file_exists( $path ) ) {
				unlink( $path );
			}
		}
	}

	/**
	 * Zero max dimensions disable resize.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_maybe_resize_file_skips_when_disabled() {
		if ( ! function_exists( 'imagecreatetruecolor' ) || ! function_exists( 'imagejpeg' ) ) {
			$this->markTestSkipped( 'GD extension is required for resize unit tests.' );
		}

		$this->load_api();

		$path = $this->create_temp_jpeg( 640, 480 );
		try {
			$result = IMGV_API::maybe_resize_file( $path, 0, 0 );
			$this->assertTrue( $result );

			$info = getimagesize( $path );
			$this->assertSame( 640, $info[0] );
			$this->assertSame( 480, $info[1] );
		} finally {
			if ( file_exists( $path ) ) {
				unlink( $path );
			}
		}
	}

	/**
	 * Anonymous users cannot use upload-gated REST routes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_rest_can_upload_false_for_anonymous() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-imgv-rest.php';

		$GLOBALS['imgv_test_caps'] = array(
			'upload_files' => false,
		);

		$reflection = new ReflectionClass( 'IMGV_REST' );
		$rest       = $reflection->newInstanceWithoutConstructor();

		$this->assertFalse( $rest->can_upload() );
	}
}

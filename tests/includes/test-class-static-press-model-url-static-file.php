<?php
/**
 * Class Static_Press_File_Scanner_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

use static_press\includes\Static_Press_Model_Url;
use static_press\includes\Static_Press_Model_Url_Static_File;
/**
 * StaticPress test case.
 */
class Static_Press_Model_Url_Static_File_Test extends \WP_UnitTestCase {
	/**
	 * Prepares file.
	 */
	public function setUp() {
		parent::setUp();
		file_put_contents( trailingslashit( ABSPATH ) . 'test.txt', '' );
		file_put_contents( trailingslashit( WP_CONTENT_DIR ) . 'test.txt', '' );
	}

	/**
	 * Puts up file.
	 */
	public function tearDown() {
		unlink( trailingslashit( WP_CONTENT_DIR ) . 'test.txt' );
		unlink( trailingslashit( ABSPATH ) . 'test.txt' );
	}

	/**
	 * Constructor should set.
	 * 
	 * @dataProvider provider_constructor
	 * 
	 * @param string $directory Directory.
	 * @param string $path      Path.
	 * @param string $expect    Expect.
	 */
	public function test_constructor( $directory, $path, $expect ) {
		$model_url_static_file = new Static_Press_Model_Url_Static_File( Static_Press_Model_Url::TYPE_STATIC_FILE, $directory, $path );
		$this->assertEquals( $expect, $model_url_static_file->get_url() );
	}

	/**
	 * Function create_static_file() should create home page.
	 * Function create_static_file() should create seo files.
	 * 
	 * @return array[]
	 */
	public function provider_constructor() {
		return array(
			array( trailingslashit( ABSPATH ), trailingslashit( ABSPATH ) . 'test.txt', '/test.txt' ),
			array( trailingslashit( WP_CONTENT_DIR ), trailingslashit( WP_CONTENT_DIR ) . 'test.txt', '/test.txt' ),
		);
	}
}

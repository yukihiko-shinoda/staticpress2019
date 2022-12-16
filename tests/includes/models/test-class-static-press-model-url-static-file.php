<?php
/**
 * Class Static_Press_File_Scanner_Test
 *
 * @package static_press\tests\includes\models
 */

namespace static_press\tests\includes\models;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-polyfill-wp-unit-test-case.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/infrastructure/class-environment.php';
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\models\Static_Press_Model_Url_Static_File;
use static_press\tests\testlibraries\Polyfill_WP_UnitTestCase;
use static_press\tests\testlibraries\infrastructure\Environment;

/**
 * Static_Press_File_Scanner test case.
 */
class Static_Press_Model_Url_Static_File_Test extends Polyfill_WP_UnitTestCase {
	/**
	 * Prepares file.
	 */
	public function set_up() {
		parent::set_up();
		file_put_contents( trailingslashit( Environment::get_document_root() ) . 'test.txt', '' );
		file_put_contents( trailingslashit( WP_CONTENT_DIR ) . 'test.txt', '' );
	}

	/**
	 * Puts up file.
	 */
	public function tear_down() {
		unlink( trailingslashit( WP_CONTENT_DIR ) . 'test.txt' );
		unlink( trailingslashit( Environment::get_document_root() ) . 'test.txt' );
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
			array( trailingslashit( Environment::get_document_root() ), trailingslashit( Environment::get_document_root() ) . 'test.txt', '/test.txt' ),
			array( trailingslashit( WP_CONTENT_DIR ), trailingslashit( WP_CONTENT_DIR ) . 'test.txt', '/test.txt' ),
		);
	}
}

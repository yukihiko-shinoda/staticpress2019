<?php
/**
 * Class Static_Press_Model_Url_Fetched_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url-creator.php';
use static_press\includes\Static_Press_Model_Url;
use static_press\tests\testlibraries\Model_Url_Creator;

/**
 * Static_Press_Model_Url_Fetched test case.
 */
class Static_Press_Model_Url_Fetched_Test extends \WP_UnitTestCase {
	/**
	 * Test step for is_static_file().
	 * 
	 * @dataProvider provider_static_file_true
	 * @param string $file_type File type.
	 */
	public function test_is_static_file_true( $file_type ) {
		$url = Model_Url_Creator::create_model_url_fetched( 1, $file_type, 'test.txt', 1 );
		$this->assertTrue( $url->is_static_file() );
	}

	/**
	 * Function is_static_file() should return true when file type is static file.
	 */
	public function provider_static_file_true() {
		return array(
			array( Static_Press_Model_Url::TYPE_STATIC_FILE ),
			array( Static_Press_Model_Url::TYPE_CONTENT_FILE ),
		);
	}

	/**
	 * Test step for is_static_file().
	 * 
	 * @dataProvider provider_static_file_false
	 * @param string $file_type File type.
	 */
	public function test_is_static_file_false( $file_type ) {
		$url = Model_Url_Creator::create_model_url_fetched( 1, $file_type, 'test.txt', 1 );
		$this->assertFalse( $url->is_static_file() );
	}

	/**
	 * Function is_static_file() should return true when file type is static file.
	 */
	public function provider_static_file_false() {
		return array(
			array( Static_Press_Model_Url::TYPE_FRONT_PAGE ),
			array( Static_Press_Model_Url::TYPE_SINGLE ),
			array( Static_Press_Model_Url::TYPE_TERM_ARCHIVE ),
			array( Static_Press_Model_Url::TYPE_AUTHOR_ARCHIVE ),
			array( Static_Press_Model_Url::TYPE_SEO_FILES ),
			array( Static_Press_Model_Url::TYPE_OTHER_PAGE ),
		);
	}
}

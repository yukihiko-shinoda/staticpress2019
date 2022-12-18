<?php
/**
 * Class Static_Press_Model_Url_Single
 *
 * @package static_press\tests\includes\models
 */

namespace static_press\tests\includes\models;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-polyfill-wp-unittestcase.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/fixtures/class-fixture-post-single.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-mock-creator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-model-url-creator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-post-array-creator.php';
use static_press\includes\models\Static_Press_Model_Url;
use static_press\tests\testlibraries\Polyfill_WP_UnitTestCase;
use static_press\tests\testlibraries\fixtures\Fixture_Post_Single;
use static_press\tests\testlibraries\creators\Mock_Creator;
use static_press\tests\testlibraries\creators\Model_Url_Creator;
use static_press\tests\testlibraries\creators\Post_Array_Creator;

/**
 * Static_Press_Model_Url_Single test case.
 */
class Static_Press_Model_Url_Single_Test extends Polyfill_WP_UnitTestCase {
	/**
	 * Fixture post single.
	 * 
	 * @var Fixture_Post_Single
	 */
	private $fixture_post_single;

	/**
	 * Insert post.
	 */
	public function set_up() {
		$this->fixture_post_single = new Fixture_Post_Single( Post_Array_Creator::create_single() );
	}

	/**
	 * Delete post.
	 */
	public function tear_down() {
		$this->fixture_post_single->delete();
	}
	/**
	 * Constructor should set.
	 */
	public function test_constructor() {
		global $wp_version;
		// There is no clear basis that 5.0.0 is the border.
		if ( version_compare( $wp_version, '5.0.0', '<' ) ) {
			$expect = array(
				'type'          => Static_Press_Model_Url::TYPE_SINGLE,
				'url'           => '/?attachment_id=3/',
				'object_id'     => 3,
				'object_type'   => 'attachment',
				'pages'         => 1,
				'last_modified' => Mock_Creator::DATE_FOR_TEST,
				'enable'        => null,
			);
		} else {
			$expect = array(
				'type'          => Static_Press_Model_Url::TYPE_SINGLE,
				'url'           => '/?attachment_id=4/',
				'object_id'     => 4,
				'object_type'   => 'attachment',
				'pages'         => 1,
				'last_modified' => Mock_Creator::DATE_FOR_TEST,
				'enable'        => null,
			);
		}
		$array_url = Model_Url_Creator::create_model_url_single( $this->fixture_post_single )->to_array();
		$this->assertEquals( Static_Press_Model_Url::TYPE_SINGLE, $array_url['type'] );
		if ( version_compare( $wp_version, '5.9.0', '<' ) ) {
			$this->assertDirectoryNotExists( '/tmp/sub1' );
		} else {
			$this->assertMatchesRegularExpression( '/\/\?attachment_id=[0-9]*\//i', $array_url['url'] );
		}
		$this->assertTrue( is_int( $array_url['object_id'] ) );
		$this->assertEquals( 'attachment', $array_url['object_type'] );
		$this->assertEquals( 1, $array_url['pages'] );
		$this->assertEquals( Mock_Creator::DATE_FOR_TEST, $array_url['last_modified'] );
		$this->assertNull( $array_url['enable'] );
	}
}

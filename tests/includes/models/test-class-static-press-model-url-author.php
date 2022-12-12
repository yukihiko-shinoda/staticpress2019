<?php
/**
 * Class Static_Press_Model_Url_Author_Test
 *
 * @package static_press\tests\includes\models
 */

namespace static_press\tests\includes\models;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-model-url-creator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-post-array-creator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/fixtures/class-fixture-post-author.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-mock-creator.php';
use static_press\includes\models\Static_Press_Model_Url;
use static_press\tests\testlibraries\creators\Model_Url_Creator;
use static_press\tests\testlibraries\creators\Post_Array_Creator;
use static_press\tests\testlibraries\fixtures\Fixture_Post_Author;
use static_press\tests\testlibraries\creators\Mock_Creator;
/**
 * Static_Press_Model_Url_Author test case.
 */
class Static_Press_Model_Url_Author_Test extends \WP_UnitTestCase {
	/**
	 * Fixture post author.
	 * 
	 * @var Fixture_Post_Author
	 */
	private $fixture_post_author;

	/**
	 * Insert post.
	 */
	public function set_up() {
		$this->fixture_post_author = new Fixture_Post_Author( Post_Array_Creator::create_author( 1 ) );
	}

	/**
	 * Delete post.
	 */
	public function tear_down() {
		$this->fixture_post_author->delete();
	}
	/**
	 * Constructor should set appropriate value into properties.
	 */
	public function test() {
		$expect = array(
			'type'          => Static_Press_Model_Url::TYPE_AUTHOR_ARCHIVE,
			'url'           => '/?author=1/',
			'object_id'     => 1,
			'pages'         => 1,
			'last_modified' => Mock_Creator::DATE_FOR_TEST,
			'enable'        => null,
		);
		$this->assertEquals( $expect, Model_Url_Creator::create_model_url_author( $this->fixture_post_author )->to_array() );
	}
}

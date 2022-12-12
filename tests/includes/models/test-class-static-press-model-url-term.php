<?php
/**
 * Class Static_Press_Model_Url_Term_Test
 *
 * @package static_press\tests\includes\models
 */

namespace static_press\tests\includes\models;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/fixtures/class-fixture-post.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-mock-creator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-model-url-creator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-post-array-creator.php';
use static_press\includes\models\Static_Press_Model_Url;
use static_press\tests\testlibraries\fixtures\Fixture_Post;
use static_press\tests\testlibraries\creators\Mock_Creator;
use static_press\tests\testlibraries\creators\Model_Url_Creator;
use static_press\tests\testlibraries\creators\Post_Array_Creator;

/**
 * Static_Press_Model_Url_Term test case.
 */
class Static_Press_Model_Url_Term_Test extends \WP_UnitTestCase {
	/**
	 * Fixture category parent.
	 * 
	 * @var Fixture_Category
	 */
	private $fixture_category_parent;
	/**
	 * Fixture post term.
	 * 
	 * @var Fixture_Post
	 */
	private $fixture_post_term;

	/**
	 * Insert post.
	 */
	public function set_up() {
		$this->fixture_post_term = new Fixture_Post( Post_Array_Creator::create_term( $this->fixture_category_parent ) );
	}

	/**
	 * Delete post.
	 */
	public function tear_down() {
		$this->fixture_post_term->delete();
	}

	/**
	 * Constructor should set appropriate value into properties.
	 */
	public function test() {
		$expect = array(
			'type'          => Static_Press_Model_Url::TYPE_TERM_ARCHIVE,
			'url'           => '/?cat=1/',
			'object_id'     => 1,
			'object_type'   => 'category',
			'pages'         => 1,
			'parent'        => 0,
			'last_modified' => Mock_Creator::DATE_FOR_TEST,
			'enable'        => null,
		);
		$this->assertEquals( $expect, Model_Url_Creator::create_model_url_term()->to_array() );
	}
}

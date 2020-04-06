<?php
/**
 * Class Static_Press_Model_Url_Term_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-fixture-post.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-mock-creator.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url-creator.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-post-array-creator.php';
use static_press\includes\Static_Press_Model_Url;
use static_press\tests\testlibraries\Fixture_Post;
use static_press\tests\testlibraries\Mock_Creator;
use static_press\tests\testlibraries\Model_Url_Creator;
use static_press\tests\testlibraries\Post_Array_Creator;

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
	public function setUp() {
		$this->fixture_post_term = new Fixture_Post( Post_Array_Creator::create_term( $this->fixture_category_parent ) );
	}

	/**
	 * Delete post.
	 */
	public function tearDown() {
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

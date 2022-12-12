<?php
/**
 * Class Static_Press_Model_Url_Test
 *
 * @package static_press\tests\includes\models
 */

namespace static_press\tests\includes\models;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-category-array-creator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-mock-creator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-post-array-creator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/fixtures/class-fixture-category.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/fixtures/class-fixture-post.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/fixtures/class-fixture-post-single.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/fixtures/class-fixture-post-author.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-model-url-creator.php';
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\models\Static_Press_Model_Url_Front_Page;
use static_press\includes\models\Static_Press_Model_Url_Other;
use static_press\includes\models\Static_Press_Model_Url_Seo;
use static_press\includes\models\Static_Press_Model_Url_Static_File;
use static_press\includes\exceptions\Static_Press_Business_Logic_Exception;
use static_press\tests\testlibraries\creators\Category_Array_Creator;
use static_press\tests\testlibraries\creators\Mock_Creator;
use static_press\tests\testlibraries\creators\Post_Array_Creator;
use static_press\tests\testlibraries\creators\Model_Url_Creator;
use static_press\tests\testlibraries\fixtures\Fixture_Category;
use static_press\tests\testlibraries\fixtures\Fixture_Post;
use static_press\tests\testlibraries\fixtures\Fixture_Post_Author;
use static_press\tests\testlibraries\fixtures\Fixture_Post_Single;

/**
 * StaticPress test case.
 */
class Static_Press_Model_Url_Test extends \WP_UnitTestCase {
	/**
	 * Fixture category parent.
	 * 
	 * @var Fixture_Category
	 */
	private $fixture_category_parent;
	/**
	 * Fixture post single.
	 * 
	 * @var Fixture_Post_Single
	 */
	private $fixture_post_single;
	/**
	 * Fixture post author.
	 * 
	 * @var Fixture_Post_Author
	 */
	private $fixture_post_author;
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
		$this->fixture_category_parent = new Fixture_Category( Category_Array_Creator::create_parent() );
		$this->fixture_post_single     = new Fixture_Post_Single( Post_Array_Creator::create_single() );
		$this->fixture_post_author     = new Fixture_Post_Author( Post_Array_Creator::create_author( 1 ) );
		$this->fixture_post_term       = new Fixture_Post( Post_Array_Creator::create_term( $this->fixture_category_parent->category_id ) );
	}

	/**
	 * Delete post.
	 */
	public function tear_down() {
		$this->fixture_post_term->delete();
		$this->fixture_post_author->delete();
		$this->fixture_post_single->delete();
		$this->fixture_category_parent->delete();
	}

	/**
	 * Constant should be apporpriate string.
	 */
	public function test_constructor() {
		$this->assertEquals( 'author_archive', Static_Press_Model_Url::TYPE_AUTHOR_ARCHIVE );
		$this->assertEquals( 'content_file', Static_Press_Model_Url::TYPE_CONTENT_FILE );
		$this->assertEquals( 'front_page', Static_Press_Model_Url::TYPE_FRONT_PAGE );
		$this->assertEquals( 'other_page', Static_Press_Model_Url::TYPE_OTHER_PAGE );
		$this->assertEquals( 'seo_files', Static_Press_Model_Url::TYPE_SEO_FILES );
		$this->assertEquals( 'single', Static_Press_Model_Url::TYPE_SINGLE );
		$this->assertEquals( 'static_file', Static_Press_Model_Url::TYPE_STATIC_FILE );
		$this->assertEquals( 'term_archive', Static_Press_Model_Url::TYPE_TERM_ARCHIVE );
	}

	/**
	 * Function judge_to_dump() should be apporpriate string.
	 */
	public function test_judge_to_dump_seo() {
		$date_time_factory_mock = Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' );
		$this->execute_test_judge_to_dump( new Static_Press_Model_Url_Seo( '', $date_time_factory_mock ), 1 );
	}

	/**
	 * Function judge_to_dump() should be apporpriate string.
	 */
	public function test_judge_to_dump_term() {
		$this->execute_test_judge_to_dump( Model_Url_Creator::create_model_url_term(), 0 );
	}

	/**
	 * Function judge_to_dump() should be apporpriate string.
	 */
	public function test_judge_to_dump_other() {
		$date_time_factory_mock = Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' );
		$this->execute_test_judge_to_dump( new Static_Press_Model_Url_Other( '', $date_time_factory_mock ), 1 );
	}

	/**
	 * Function judge_to_dump() should be apporpriate string.
	 */
	public function test_judge_to_dump_author() {
		$this->execute_test_judge_to_dump( Model_Url_Creator::create_model_url_author( $this->fixture_post_author ), 0 );
	}

	/**
	 * Function judge_to_dump() should be apporpriate string.
	 */
	public function test_judge_to_dump_single() {
		$this->execute_test_judge_to_dump( Model_Url_Creator::create_model_url_single( $this->fixture_post_single ), 0 );
	}

	/**
	 * Function judge_to_dump() should be apporpriate string.
	 */
	public function test_judge_to_dump_front() {
		$date_time_factory_mock = Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' );
		$this->execute_test_judge_to_dump( new Static_Press_Model_Url_Front_Page( $date_time_factory_mock ), 1 );
	}

	/**
	 * Test step for judge_to_dump().
	 * 
	 * @dataProvider provider_judge_to_dump
	 * @param Static_Press_Model_Url $url    URL.
	 * @param int                    $expect Expect.
	 */
	private function execute_test_judge_to_dump( $url, $expect ) {
		$url->judge_to_dump();
		// Reason: This project no longer support PHP 5.5 nor lower.
		$this->assertEquals( $expect, $url->to_array()['enable'] ); // phpcs:ignore
	}

	/**
	 * Test step for judge_to_dump().
	 * 
	 * @dataProvider provider_judge_to_dump_exception
	 * @param Static_Press_Model_Url $url    URL.
	 */
	public function test_judge_to_dump_exception( $url ) {
		// Reason: This project no longer support PHP 5.5 nor lower.
		$this->expectException( Static_Press_Business_Logic_Exception::class ); // phpcs:ignore
		$url->judge_to_dump();
	}

	/**
	 * Function judge_to_dump() should be apporpriate string.
	 */
	public function provider_judge_to_dump_exception() {
		return array(
			array( new Static_Press_Model_Url_Static_File( Static_Press_Model_Url::TYPE_STATIC_FILE, ABSPATH, ABSPATH . '/' ) ),
			array( new Static_Press_Model_Url_Static_File( Static_Press_Model_Url::TYPE_CONTENT_FILE, WP_CONTENT_DIR, WP_CONTENT_DIR . '/' ) ),
		);
	}
}

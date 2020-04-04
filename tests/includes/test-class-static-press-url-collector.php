<?php
/**
 * Class Static_Press_Url_Collector_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-array-url-handler.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-mock-creator.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url-creator.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url-handler.php';
use static_press\includes\Static_Press_Model_Url;
use static_press\includes\Static_Press_Url_Collector;
use static_press\tests\testlibraries\Array_Url_Handler;
use static_press\tests\testlibraries\Mock_Creator;
use static_press\tests\testlibraries\Model_Url_Creator;
use static_press\tests\testlibraries\Model_Url_Handler;
/**
 * Reposistory test case.
 */
class Static_Press_Url_Collector_Test extends \WP_UnitTestCase {
	/**
	 * For WordPress
	 * 
	 * @var string
	 */
	private $url;
	/**
	 * For WordPress
	 * 
	 * @var string
	 */
	private $url_previous;
	/**
	 * For WordPress
	 * 
	 * @var string
	 */
	private $blog_id_another_blog;

	/**
	 * Function collect() should return urls of front page, static files, and SEO.
	 * 
	 * @runInSeparateProcess
	 */
	public function test_collect() {
		file_put_contents( ABSPATH . 'test.txt', '' );
		$expect_urls            = array_merge(
			Model_Url_Creator::get_expect_urls_front_page(),
			Model_Url_Creator::get_expect_urls_static_files( Mock_Creator::DATE_FOR_TEST ),
			Model_Url_Creator::get_expect_urls_seo( Mock_Creator::DATE_FOR_TEST )
		);
		$url_collector          = new Static_Press_Url_Collector(
			Mock_Creator::create_remote_getter_mock(),
			Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s', Mock_Creator::DATE_FOR_TEST )
		);
		$actual                 = $url_collector->collect();
		$array_array_url_expect = array();
		foreach ( $expect_urls as $url ) {
			$array_array_url_expect[] = $url->to_array();
		}
		$array_array_url_actual = array();
		foreach ( $actual as $url ) {
			$array_array_url_actual[] = $url->to_array();
		}
		Array_Url_Handler::assert_contains_urls( $this, $array_array_url_expect, $array_array_url_actual );
	}

	/**
	 * Function front_page_url() should return appropriate URLs.
	 */
	public function test_front_page_url() {
		$expect = Model_Url_Creator::get_expect_urls_front_page();
		$actual = $this->create_accessable_method(
			Mock_Creator::create_remote_getter_mock(),
			'front_page_url',
			array(),
			Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s', Mock_Creator::DATE_FOR_TEST )
		);
		Model_Url_Handler::assert_array_model_url( $this, $expect, $actual );
	}

	/**
	 * Function single_url() should return URLs of posts.
	 * Function single_url() should return number of pages by split post content by nextpage tag.
	 */
	public function test_single_url() {
		global $wp_version;
		// There is no clear basis that 5.0.0 is the border.
		if ( version_compare( $wp_version, '5.0.0', '<' ) ) {
			$expect = array(
				array(
					'type'          => Static_Press_Model_Url::TYPE_SINGLE,
					'url'           => '/?attachment_id=3/',
					'object_id'     => 3,
					'object_type'   => 'attachment',
					'pages'         => 1,
					'last_modified' => Mock_Creator::DATE_FOR_TEST,
					'enable'        => null,
				),
				array(
					'type'          => Static_Press_Model_Url::TYPE_SINGLE,
					'url'           => '/?attachment_id=4/',
					'object_id'     => 4,
					'object_type'   => 'attachment',
					'pages'         => 3,
					'last_modified' => Mock_Creator::DATE_FOR_TEST,
					'enable'        => null,
				),
			);
		} else {
			$expect = array(
				array(
					'type'          => Static_Press_Model_Url::TYPE_SINGLE,
					'url'           => '/?attachment_id=4/',
					'object_id'     => 4,
					'object_type'   => 'attachment',
					'pages'         => 1,
					'last_modified' => Mock_Creator::DATE_FOR_TEST,
					'enable'        => null,
				),
				array(
					'type'          => Static_Press_Model_Url::TYPE_SINGLE,
					'url'           => '/?attachment_id=5/',
					'object_id'     => 5,
					'object_type'   => 'attachment',
					'pages'         => 3,
					'last_modified' => Mock_Creator::DATE_FOR_TEST,
					'enable'        => null,
				),
			);
		}
		wp_insert_post(
			array(
				'post_title'   => 'Post Title 1',
				'post_content' => 'Post content 1.',
				'post_status'  => 'publish',
				'post_type'    => 'attachment',
			)
		);
		wp_insert_post(
			array(
				'post_title'   => 'Post Title 2',
				'post_content' => 'test<!--nextpage-->test<!--nextpage-->test',
				'post_status'  => 'publish',
				'post_type'    => 'attachment',
			)
		);
		$actual          = $this->create_accessable_method( null, 'single_url', array() );
		$array_array_url = array();
		foreach ( $actual as $url ) {
			$array_array_url[] = $url->to_array();
		}
		Model_Url_Handler::assert_urls( $this, $expect, $array_array_url );
	}

	/**
	 * Function terms_url() should return URLs of terms.
	 */
	public function test_terms_url() {
		$term_parent = wp_insert_category(
			array(
				'cat_name' => 'category parent',
			)
		);
		$term_child  = wp_insert_category(
			array(
				'cat_name'             => 'category child',
				'category_description' => '',
				'category_nicename'    => '',
				'category_parent'      => $term_parent,
			)
		);
		wp_insert_post(
			array(
				'post_title'    => 'Test Title',
				'post_content'  => 'Test content.',
				'post_status'   => 'publish',
				'post_type'     => 'post',
				'post_category' => array(
					$term_child,
				),
			)
		);
		$expect          = array(
			array(
				'type'          => Static_Press_Model_Url::TYPE_TERM_ARCHIVE,
				'url'           => '/?cat=3/',
				'object_id'     => 3,
				'object_type'   => 'category',
				'pages'         => 1,
				'parent'        => 2,
				'last_modified' => Mock_Creator::DATE_FOR_TEST,
				'enable'        => null,
			),
			array(
				'type'          => Static_Press_Model_Url::TYPE_TERM_ARCHIVE,
				'url'           => '/?cat=2/',
				'object_id'     => 2,
				'object_type'   => 'category',
				'pages'         => 1,
				'parent'        => 0,
				'last_modified' => Mock_Creator::DATE_FOR_TEST,
				'enable'        => null,
			),
			array(
				'type'          => Static_Press_Model_Url::TYPE_TERM_ARCHIVE,
				'url'           => '/?cat=3/',
				'object_id'     => 3,
				'object_type'   => 'category',
				'pages'         => 1,
				'parent'        => 2,
				'last_modified' => Mock_Creator::DATE_FOR_TEST,
				'enable'        => null,
			),
		);
		$actual          = $this->create_accessable_method( null, 'terms_url', array() );
		$array_array_url = array();
		foreach ( $actual as $url ) {
			$array_array_url[] = $url->to_array();
		}
		Model_Url_Handler::assert_urls( $this, $expect, $array_array_url );
	}

	/**
	 * Function author_url() should return URLs of authors.
	 */
	public function test_author_url() {
		$expect = array(
			array(
				'type'          => Static_Press_Model_Url::TYPE_AUTHOR_ARCHIVE,
				'url'           => '/?author=1/',
				'object_id'     => 1,
				'pages'         => 1,
				'last_modified' => Mock_Creator::DATE_FOR_TEST,
				'enable'        => null,
			),
		);
		wp_insert_post(
			array(
				'post_title'   => 'Post Title 1',
				'post_content' => 'Post content 1.',
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'post_author'  => 1,
			)
		);
		$actual          = $this->create_accessable_method( null, 'author_url', array() );
		$array_array_url = array();
		foreach ( $actual as $url ) {
			$array_array_url[] = $url->to_array();
		}
		Model_Url_Handler::assert_urls( $this, $expect, $array_array_url );
	}

	/**
	 * Function static_files_url() should return URLs of authors.
	 */
	public function test_static_files_url() {
		file_put_contents( ABSPATH . 'test.txt', '' );
		$expect = Model_Url_Creator::get_expect_urls_static_files( Mock_Creator::DATE_FOR_TEST );
		wp_insert_post(
			array(
				'post_title'   => 'Post Title 1',
				'post_content' => 'Post content 1.',
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'post_author'  => 1,
			)
		);
		$actual                 = $this->create_accessable_method( null, 'static_files_url', array() );
		$array_array_url_expect = array();
		foreach ( $expect as $url ) {
			$array_array_url_expect[] = $url->to_array();
		}
		$array_array_url_actual = array();
		foreach ( $actual as $url ) {
			$array_array_url_actual[] = $url->to_array();
		}
		Array_Url_Handler::assert_contains_urls( $this, $array_array_url_expect, $array_array_url_actual );
	}

	/**
	 * Function seo_url() should trancate database table for list URL.
	 */
	public function test_seo_url() {
		$expect_urls     = Model_Url_Creator::get_expect_urls_seo( Mock_Creator::DATE_FOR_TEST );
		$actual          = $this->create_accessable_method( Mock_Creator::set_up_seo_url( 'http://example.org/' ), 'seo_url', array(), Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' ) );
		$array_array_url = array();
		foreach ( $actual as $url ) {
			$array_array_url[] = $url->to_array();
		}
		Model_Url_Handler::assert_array_model_url( $this, $expect_urls, $actual );
	}

	/**
	 * Creates accessable method.
	 * 
	 * @param mixed         $remote_getter_mock     Mock for Remote Getter.
	 * @param string        $method_name            Method name.
	 * @param array         $array_parameter        Array of parameter.
	 * @param MockInterface $date_time_factory_mock Mock interface for Date time factory.
	 */
	private function create_accessable_method( $remote_getter_mock, $method_name, $array_parameter, $date_time_factory_mock = null ) {
		$url_collector = new Static_Press_Url_Collector( $remote_getter_mock, $date_time_factory_mock, Mock_Creator::create_docuemnt_root_getter_mock() );
		$reflection    = new \ReflectionClass( get_class( $url_collector ) );
		$method        = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $url_collector, $array_parameter );
	}
}

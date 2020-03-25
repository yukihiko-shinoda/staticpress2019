<?php
/**
 * Model_Url_Handler
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

require_once dirname( __FILE__ ) . '/./class-business-logic-exception.php';
use static_press\includes\Static_Press_Model_Url;
use static_press\includes\Static_Press_Model_Url_Author;
use static_press\includes\Static_Press_Model_Url_Fetched;
use static_press\includes\Static_Press_Model_Url_Single;
use static_press\includes\Static_Press_Model_Url_Term;
use static_press\includes\Static_Press_Repository;
use static_press\tests\testlibraries\Business_Logic_Exception;

/**
 * Class Model_Url_Handler
 */
class Model_Url_Handler {
	/**
	 * Creates model URL term.
	 * 
	 * @return Static_Press_Model_Url_Term Model URL term.
	 */
	public static function create_model_url_term() {
		$date_time_factory_mock = Test_Utility::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' );
		$repository             = new Static_Press_Repository();
		$category               = wp_insert_category(
			array(
				'cat_name' => 'category parent',
			)
		);
		wp_insert_post(
			array(
				'post_title'    => 'Test Title',
				'post_content'  => 'Test content.',
				'post_status'   => 'publish',
				'post_type'     => 'post',
				'post_category' => array(
					$category,
				),
			)
		);
		$taxonomies = get_taxonomies( array( 'public' => true ) );
		$taxonomy   = $taxonomies['category'];
		$terms      = get_terms( $taxonomy );
		$term       = $terms[0];
		get_term_link( $term->slug, $taxonomy );
		return new Static_Press_Model_Url_Term( $term, $taxonomy, $repository, $date_time_factory_mock );
	}

	/**
	 * Creates model URL author.
	 * 
	 * @return Static_Press_Model_Url_Author Model URL author.
	 */
	public static function create_model_url_author() {
		wp_insert_post(
			array(
				'post_title'   => 'Post Title 1',
				'post_content' => 'Post content 1.',
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'post_author'  => 1,
			)
		);
		$post_types  = get_post_types( array( 'public' => true ) );
		$repository  = new Static_Press_Repository();
		$authors     = $repository->get_post_authors( $post_types );
		$author      = $authors[1];
		$author_data = get_userdata( $author->post_author );
		return new Static_Press_Model_Url_Author( $author, $author_data );
	}

	/**
	 * Creates model URL single.
	 * 
	 * @return Static_Press_Model_Url_Author Model URL single.
	 */
	public static function create_model_url_single() {
		$post_types = get_post_types( array( 'public' => true ) );
		$repository = new Static_Press_Repository();
		$posts      = $repository->get_posts( $post_types );
		$post       = $posts[0];
		return new Static_Press_Model_Url_Single( $post );
	}

	/**
	 * Creates model URL fetched.
	 * 
	 * @param int    $id        ID.
	 * @param string $file_type File type.
	 * @param string $url       URL.
	 * @param int    $pages     Page.
	 * @return Static_Press_Model_Url_Fetched Model URL fetched.
	 */
	public static function create_model_url_fetched( $id, $file_type, $url, $pages ) {
		$url_object = new Model_Url( (string) $id, $file_type, $url, null, null, null, (string) $pages, null, null, null, null, null, null, null );
		return new Static_Press_Model_Url_Fetched( $url_object );
	}

	/**
	 * Asserts that URLs contains.
	 * 
	 * @param PHPUnit_Framework_TestCase $test_case   Test case.
	 * @param array                      $expect_urls Expect URLs.
	 * @param array                      $actual_urls Actual URLs.
	 * @throws Business_Logic_Exception Case when not contains.
	 */
	public static function assert_contains_urls( $test_case, $expect_urls, $actual_urls ) {
		$copy_expect_urls         = $expect_urls;
		$copy_actual_urls         = $actual_urls;
		$expect_url_not_contained = array();
		foreach ( $copy_expect_urls as $expect_url ) {
			try {
				$copy_actual_urls = self::assert_contains_url( $expect_url, $copy_actual_urls );
			} catch ( Business_Logic_Exception $exception ) {
				$expect_url_not_contained[] = $expect_url;
			}
		}
		$test_case->assertFalse(
			empty( $expect_url_not_contained ),
			"Actual URLs does not contain Expect URL. Not contained:\n" . var_export( $expect_url_not_contained, true )
		);
	}

	/**
	 * Asserts that URLs not contains.
	 * 
	 * @param PHPUnit_Framework_TestCase $test_case   Test case.
	 * @param Static_Press_Model_Url[]   $expect_urls Expect URLs.
	 * @param Static_Press_Model_Url[]   $actual_urls Actual URLs.
	 * @throws Business_Logic_Exception Case when contains.
	 */
	public static function assert_not_contains_urls( $test_case, $expect_urls, $actual_urls ) {
		$copy_expect_urls     = $expect_urls;
		$copy_actual_urls     = $actual_urls;
		$expect_url_contained = array();
		foreach ( $copy_expect_urls as $expect_url ) {
			try {
				$copy_actual_urls       = self::assert_contains_url( $expect_url, $copy_actual_urls );
				$expect_url_contained[] = $expect_url;
			} catch ( Business_Logic_Exception $exception ) {
				continue;
			}
		}
		$test_case->assertTrue(
			empty( $expect_url_contained ),
			"Actual URLs contains Expect URL. Contained:\nExpect URL contained:\n" . var_export( $expect_url_contained, true )
		);
	}

	/**
	 * Asserts that URL contains.
	 * 
	 * @param Static_Press_Model_Url   $expect_url  Expect URL.
	 * @param Static_Press_Model_Url[] $actual_urls Actual URLs.
	 * @return array Remaining actual URLs.
	 * @throws Business_Logic_Exception Case when not contains.
	 */
	private static function assert_contains_url( $expect_url, $actual_urls ) {
		$length_actual = count( $actual_urls );
		for ( $index = 0; $index < $length_actual; $index ++ ) {
			$actual_url = $actual_urls[ $index ];
			try {
				self::compare_url( $expect_url, $actual_url );
			} catch ( Business_Logic_Exception $exception ) {
				continue;
			}
			return array_splice( $actual_urls, $index, 1 );
		}
		throw new Business_Logic_Exception();
	}

	/**
	 * Compares two URLs.
	 * 
	 * @param Static_Press_Model_Url $expect Expect URL.
	 * @param Static_Press_Model_Url $actual Actual URL.
	 * @throws Business_Logic_Exception Case when different.
	 */
	private static function compare_url( $expect, $actual ) {
		if ( ! $expect instanceof $actual ) {
			throw new Business_Logic_Exception( 'Instance type is different. ' . self::debug_urls_instance_type( $expect, $actual ) );
		}
		if ( ! $expect->equals( $actual ) ) {
			throw new Business_Logic_Exception( 'Expect is not same with actual.' );
		}
	}

	/**
	 * Debug URLs.
	 * 
	 * @param mixed $expect Expect URL.
	 * @param mixed $actual Actual URL.
	 */
	private static function debug_urls_instance_type( $expect, $actual ) {
		return "EXPECT:\n" . get_class( $expect ) . "ACTUAL:\n" . get_class( $actual ) . "\n";
	}
}


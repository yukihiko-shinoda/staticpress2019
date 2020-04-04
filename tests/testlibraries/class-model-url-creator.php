<?php
/**
 * Class Model_Url_Creator
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

require_once dirname( __FILE__ ) . '/../testlibraries/class-expect-urls-static-files.php';
use static_press\includes\Static_Press_Model_Url;
use static_press\includes\Static_Press_Model_Url_Author;
use static_press\includes\Static_Press_Model_Url_Fetched;
use static_press\includes\Static_Press_Model_Url_Front_Page;
use static_press\includes\Static_Press_Model_Url_Seo;
use static_press\includes\Static_Press_Model_Url_Single;
use static_press\includes\Static_Press_Model_Url_Static_File;
use static_press\includes\Static_Press_Model_Url_Term;
use static_press\includes\Static_Press_Repository;
use static_press\tests\testlibraries\Expect_Urls_Static_Files;
/**
 * Model URL creator.
 */
class Model_Url_Creator {
	/**
	 * Creates model URL term.
	 * 
	 * @return Static_Press_Model_Url_Term Model URL term.
	 */
	public static function create_model_url_term() {
		$date_time_factory_mock = Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' );
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
	 * Gets expect URLs of front_page_url().
	 */
	public static function get_expect_urls_front_page() {
		return array(
			new Static_Press_Model_Url_Front_Page( Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' ) ),
		);
	}

	/**
	 * Gets expect URLs.
	 * 
	 * @return Static_Press_Model_Url_Static_File[] Array of model URL of static file.
	 * @throws \LogicException When fail to stat any file.
	 */
	public static function get_expect_urls_static_files() {
		/**
		 * To convert E_WARNING of filemtime(): stat failed to LogicException.
		 * 
		 * @see https://stackoverflow.com/questions/1241728/can-i-try-catch-a-warning/1241751#1241751
		 */
		set_error_handler(
			function( $errno, $errstr, $errfile, $errline, $errcontext ) {
				// error was suppressed with the @-operator.
				if ( 0 === error_reporting() ) {
					return false;
				}
				throw new \LogicException( $errstr, $errno );
			}
		);
		$expect                = array();
		$array_logic_exception = array();
		foreach ( Expect_Urls_Static_Files::EXPECT_URLS as $expect_url ) {
			try {
				$expect[] = new Static_Press_Model_Url_Static_File( Static_Press_Model_Url::TYPE_STATIC_FILE, ABSPATH, ABSPATH . ltrim( $expect_url, '/' ) );
			} catch ( \LogicException $exception ) {
				$array_logic_exception[] = $exception;
			}
		}
		restore_error_handler();
		if ( ! empty( $array_logic_exception ) ) {
			$message = "filemtime(): stat failed\n";
			foreach ( $array_logic_exception as $logic_exception ) {
				$message .= "{$logic_exception->getMessage()}\n";
			}
			throw new \LogicException( $message );
		}
		return $expect;
	}

	/**
	 * Gets expect URLs of seo_url().
	 * 
	 * @return Static_Press_Model_Url_Seo[] Array of model URL of SEO.
	 */
	public static function get_expect_urls_seo() {
		$date_time_factory = Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' );
		return array(
			new Static_Press_Model_Url_Seo( '/robots.txt', $date_time_factory ),
			new Static_Press_Model_Url_Seo( '/sitemap.xml', $date_time_factory ),
			new Static_Press_Model_Url_Seo( '/sitemap-misc.xml', $date_time_factory ),
			new Static_Press_Model_Url_Seo( '/sitemap-tax-category.xml', $date_time_factory ),
			new Static_Press_Model_Url_Seo( '/sitemap-pt-post-2020-02.xml', $date_time_factory ),
		);
	}
}

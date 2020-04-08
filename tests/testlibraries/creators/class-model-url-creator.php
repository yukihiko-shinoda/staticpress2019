<?php
/**
 * Class Model_Url_Creator
 *
 * @package static_press\tests\testlibraries\creators
 */

namespace static_press\tests\testlibraries\creators;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-mock-creator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-expect-urls-static-files.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/repositories/class-repository-for-test.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-model-url.php';
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\models\Static_Press_Model_Url_Author;
use static_press\includes\models\Static_Press_Model_Url_Fetched;
use static_press\includes\models\Static_Press_Model_Url_Front_Page;
use static_press\includes\models\Static_Press_Model_Url_Seo;
use static_press\includes\models\Static_Press_Model_Url_Single;
use static_press\includes\models\Static_Press_Model_Url_Static_File;
use static_press\includes\models\Static_Press_Model_Url_Term;
use static_press\tests\testlibraries\creators\Mock_Creator;
use static_press\tests\testlibraries\Expect_Urls_Static_Files;
use static_press\tests\testlibraries\Model_Url;
use static_press\tests\testlibraries\repositories\Repository_For_Test;
/**
 * Model URL creator.
 */
class Model_Url_Creator {
	/**
	 * Creates model URL single.
	 * 
	 * @param Fixture_Post $fixture_post Fixture post single.
	 * @return Static_Press_Model_Url_Author Model URL single.
	 */
	public static function create_model_url_single( $fixture_post ) {
		$repository = new Repository_For_Test();
		return new Static_Press_Model_Url_Single( $repository->get_attachment_post( $fixture_post->post_title ) );
	}

	/**
	 * Creates model URL term.
	 * 
	 * @return Static_Press_Model_Url_Term Model URL term.
	 */
	public static function create_model_url_term() {
		$taxonomies = get_taxonomies( array( 'public' => true ) );
		$taxonomy   = $taxonomies['category'];
		$terms      = get_terms( $taxonomy );
		$term       = $terms[0];
		get_term_link( $term->slug, $taxonomy );
		return new Static_Press_Model_Url_Term( $term, $taxonomy, Mock_Creator::DATE_FOR_TEST, 1 );
	}

	/**
	 * Creates model URL term.
	 * It is required to Create post fixture before executing this function.
	 * 
	 * @return Static_Press_Model_Url_Term[] Model URL term.
	 */
	public static function create_array_model_url_term() {
		$taxonomies    = get_taxonomies( array( 'public' => true ) );
		$taxonomy      = $taxonomies['category'];
		$terms_parent  = get_terms( $taxonomy );
		$term_parent_0 = array_shift( $terms_parent );
		get_term_link( $term_parent_0->slug, $taxonomy );
		$term_parent_1 = array_shift( $terms_parent );
		get_term_link( $term_parent_1->slug, $taxonomy );
		$term_parent_2 = array_shift( $terms_parent );
		get_term_link( $term_parent_2->slug, $taxonomy );
		return array(
			new Static_Press_Model_Url_Term( $term_parent_0, $taxonomy, Mock_Creator::DATE_FOR_TEST, 1 ),
			new Static_Press_Model_Url_Term( $term_parent_1, $taxonomy, null, 1 ),
			new Static_Press_Model_Url_Term( $term_parent_0, $taxonomy, Mock_Creator::DATE_FOR_TEST, 1 ),
			new Static_Press_Model_Url_Term( $term_parent_2, $taxonomy, Mock_Creator::DATE_FOR_TEST, 1 ),
		);
	}

	/**
	 * Creates model URL author.
	 * It is required to Create post fixture before executing this function.
	 * 
	 * @param Fixture_Post_Author $fixture_post_author Fixture post author.
	 * @return Static_Press_Model_Url_Author Model URL author.
	 * @throws \LogicException When failed to insert post.
	 */
	public static function create_model_url_author( $fixture_post_author ) {
		$post_types  = get_post_types( array( 'public' => true ) );
		$repository  = new Repository_For_Test();
		$author      = $repository->get_post_authors( $fixture_post_author->author_id, $post_types );
		$author_data = get_userdata( $author->post_author );
		return new Static_Press_Model_Url_Author( $author, $author_data );
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

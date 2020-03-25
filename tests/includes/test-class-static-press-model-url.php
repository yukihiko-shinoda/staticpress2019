<?php
/**
 * Class Static_Press_Model_Url_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url-handler.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-test-utility.php';

use static_press\includes\Static_Press_Business_Logic_Exception;
use static_press\includes\Static_Press_Model_Url;
use static_press\includes\Static_Press_Model_Url_Front_Page;
use static_press\includes\Static_Press_Model_Url_Other;
use static_press\includes\Static_Press_Model_Url_Seo;
use static_press\includes\Static_Press_Model_Url_Static_File;
use static_press\tests\testlibraries\Model_Url_Handler;
use static_press\tests\testlibraries\Test_Utility;

/**
 * StaticPress test case.
 */
class Static_Press_Model_Url_Test extends \WP_UnitTestCase {
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
	 * Test step for judge_to_dump().
	 * 
	 * @dataProvider provider_judge_to_dump
	 * @param Static_Press_Model_Url $url    URL.
	 * @param int                    $expect Expect.
	 */
	public function test_judge_to_dump( $url, $expect ) {
		$url->judge_to_dump();
		// Reason: This project no longer support PHP 5.5 nor lower.
		$this->assertEquals( $expect, $url->to_array()['enable'] ); // phpcs:ignore
	}

	/**
	 * Function judge_to_dump() should be apporpriate string.
	 */
	public function provider_judge_to_dump() {
		$date_time_factory_mock = Test_Utility::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' );
		return array(
			array( new Static_Press_Model_Url_Seo( '', $date_time_factory_mock ), 1 ),
			array( Model_Url_Handler::create_model_url_term(), 0 ),
			array( new Static_Press_Model_Url_Other( '', $date_time_factory_mock ), 1 ),
			array( Model_Url_Handler::create_model_url_author(), 0 ),
			array( Model_Url_Handler::create_model_url_single(), 0 ),
			array( new Static_Press_Model_Url_Front_Page( $date_time_factory_mock ), 1 ),
		);
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

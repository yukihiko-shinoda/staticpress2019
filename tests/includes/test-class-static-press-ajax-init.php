<?php
/**
 * Class Static_Press_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-array-url-handler.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-expect-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-repository-for-test.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-test-utility.php';
use static_press\includes\Static_Press_Ajax_Init;
use static_press\includes\Static_Press_Repository;
use static_press\tests\testlibraries\Array_Url_Handler;
use static_press\tests\testlibraries\Expect_Url;
use static_press\tests\testlibraries\Model_Url;
use static_press\tests\testlibraries\Repository_For_Test;
use static_press\tests\testlibraries\Test_Utility;
/**
 * StaticPress test case.
 *
 * @noinspection PhpUndefinedClassInspection
 */
class Static_Press_Ajax_Init_Test extends \WP_UnitTestCase {
	const DATE_FOR_TEST = '2019-12-23 12:34:56';
	/**
	 * Function get_urls() should trancate database table for list URL.
	 * Function get_urls() should return urls of front page, static files, and SEO.
	 */
	public function test_get_urls_trancate() {
		Repository_For_Test::insert_url(
			new Model_Url(
				1,
				'other_page',
				'/test/',
				0,
				'',
				0,
				1,
				0,
				'',
				'0000-00-00 00:00:00',
				0,
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00'
			)
		);
		$expect_database = array(
			new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
		);
		Expect_Url::assert_url( $this, $expect_database, Repository_For_Test::get_all_url() );
		$expect_urls = array_merge(
			Test_Utility::get_expect_urls_front_page( self::DATE_FOR_TEST ),
			Test_Utility::get_expect_urls_static_files( self::DATE_FOR_TEST ),
			Test_Utility::get_expect_urls_seo( self::DATE_FOR_TEST )
		);
		$actual      = $this->create_accessable_method( 'get_urls', array() );
		Array_Url_Handler::assert_contains_urls( $this, $expect_urls, $actual );
		Expect_Url::assert_url( $this, array(), Repository_For_Test::get_all_url() );
	}

	/**
	 * Creates accessable method.
	 * 
	 * @param string $method_name        Method name.
	 * @param array  $array_parameter    Array of parameter.
	 */
	private function create_accessable_method( $method_name, $array_parameter ) {
		$static_press = new Static_Press_Ajax_Init(
			null,
			null,
			new Static_Press_Repository(),
			Test_Utility::create_remote_getter_mock(),
			Test_Utility::create_terminator_mock()
		);
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $static_press, $array_parameter );
	}
}

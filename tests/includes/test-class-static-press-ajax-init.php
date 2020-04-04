<?php
/**
 * Class Static_Press_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-array-url-handler.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-die-exception.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-expect-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-mock-creator.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url-creator.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url-handler.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-repository-for-test.php';
use static_press\includes\Static_Press_Ajax_Init;
use static_press\includes\Static_Press_Model_Url;
use static_press\includes\Static_Press_Repository;
use static_press\tests\testlibraries\Die_Exception;
use static_press\tests\testlibraries\Mock_Creator;
use static_press\tests\testlibraries\Model_Url;
use static_press\tests\testlibraries\Model_Url_Creator;
use static_press\tests\testlibraries\Model_Url_Handler;
use static_press\tests\testlibraries\Repository_For_Test;
/**
 * StaticPress test case.
 *
 * @noinspection PhpUndefinedClassInspection
 */
class Static_Press_Ajax_Init_Test extends \WP_UnitTestCase {
	/**
	 * Function get_urls() should trancate database table for list URL.
	 * Function get_urls() should return urls of front page, static files, and SEO.
	 * 
	 * @runInSeparateProcess
	 */
	public function test_process_ajax_request_trancate() {
		Repository_For_Test::insert_url(
			new Model_Url(
				1,
				Static_Press_Model_Url::TYPE_OTHER_PAGE,
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
			Model_Url_Creator::create_model_url_fetched( 1, Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test/', 1 ),
		);
		$this->assertEquals( $expect_database, Repository_For_Test::get_all_url() );
		ob_start();
		try {
			$actual = $this->create_accessable_method( 'process_ajax_request', array() );
		} catch ( Die_Exception $exception ) {
			ob_get_clean();
			Model_Url_Handler::assert_not_contains_urls( $this, $expect_database, Repository_For_Test::get_all_url() );
			return;
		}
		$this->fail();
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
			Mock_Creator::create_remote_getter_mock(),
			Mock_Creator::create_terminator_mock()
		);
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $static_press, $array_parameter );
	}
}

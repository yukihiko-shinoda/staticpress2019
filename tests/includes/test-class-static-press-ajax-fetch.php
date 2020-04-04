<?php
/**
 * Class Static_Press_Ajax_Fetch_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-mock-creator.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-repository-for-test.php';
use static_press\includes\Static_Press_Ajax_Fetch;
use static_press\includes\Static_Press_Business_Logic_Exception;
use static_press\includes\Static_Press_Model_Url;
use static_press\includes\Static_Press_Repository;
use static_press\tests\testlibraries\Expect_Url;
use static_press\tests\testlibraries\Mock_Creator;
use static_press\tests\testlibraries\Model_Url;
use static_press\tests\testlibraries\Repository_For_Test;
/**
 * Static_Press_Ajax_Fetch test case.
 *
 * @noinspection PhpUndefinedClassInspection
 */
class Static_Press_Ajax_Fetch_Test extends \WP_UnitTestCase {
	/**
	 * Function fetch_url() should return false when URLs do not exist in database table.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_fetch_url_url_not_exists() {
		// Reason: This project no longer support PHP 5.5 nor lower.
		$this->expectException( Static_Press_Business_Logic_Exception::class ); // phpcs:ignore
		$this->create_accessable_method( 'fetch_url', array() );
	}

	/**
	 * Function fetch_url() should return URL of first record in database table when URLs exist in database table.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_fetch_url_url_exists() {
		Repository_For_Test::insert_url(
			new Model_Url(
				1,
				Static_Press_Model_Url::TYPE_OTHER_PAGE,
				'/test1/',
				0,
				'',
				0,
				1,
				1,
				'',
				'0000-00-00 00:00:00',
				0,
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00'
			)
		);
		Repository_For_Test::insert_url(
			new Model_Url(
				2,
				Static_Press_Model_Url::TYPE_OTHER_PAGE,
				'/test2/',
				0,
				'',
				0,
				1,
				1,
				'',
				'0000-00-00 00:00:00',
				0,
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00'
			)
		);
		$expect = array(
			new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test1/', '1' ),
		);
		Expect_Url::assert_url( $this, $expect, array( $this->create_accessable_method( 'fetch_url', array() ) ) );
	}

	/**
	 * Creates accessable method.
	 * 
	 * @param string $method_name        Method name.
	 * @param array  $array_parameter    Array of parameter.
	 */
	private function create_accessable_method( $method_name, $array_parameter ) {
		$static_press = new Static_Press_Ajax_Fetch(
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

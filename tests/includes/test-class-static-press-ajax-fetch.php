<?php
/**
 * Class Static_Press_Ajax_Fetch_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-repository-for-test.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-test-utility.php';
use static_press\includes\Static_Press_Ajax_Fetch;
use static_press\includes\Static_Press_Repository;
use static_press\tests\testlibraries\Expect_Url;
use static_press\tests\testlibraries\Model_Url;
use static_press\tests\testlibraries\Repository_For_Test;
use static_press\tests\testlibraries\Test_Utility;
/**
 * Static_Press_Ajax_Fetch test case.
 *
 * @noinspection PhpUndefinedClassInspection
 */
class Static_Press_Ajax_Fetch_Test extends \WP_UnitTestCase {
	/**
	 * Function fetch_last_id() should return 0 when parameter is not set.
	 */
	public function test_fetch_last_id_without_parameter_with_transient() {
		set_transient( 'static static', array( 'fetch_last_id' => 2 ), 3600 );
		$result = $this->create_accessable_method( 'fetch_last_id', array() );
		$this->assertEquals( $result, 2 );
	}

	/**
	 * Function fetch_last_id() should return 0 when parameter is not set.
	 */
	public function test_fetch_last_id_without_parameter_without_transient() {
		$result = $this->create_accessable_method( 'fetch_last_id', array() );
		$this->assertEquals( $result, 0 );
	}

	/**
	 * Test steps for constructor.
	 * 
	 * @dataProvider provider_fetch_last_id_with_paramter_with_transient
	 * 
	 * @param string $next_id ID to set next.
	 * @param string $expect  Expect return value.
	 */
	public function test_fetch_last_id_with_paramter_with_transient( $next_id, $expect ) {
		set_transient( 'static static', array( 'fetch_last_id' => 2 ), 3600 );
		$result = $this->create_accessable_method( 'fetch_last_id', array( $next_id ) );
		$this->assertEquals( $result, $expect );
	}

	/**
	 * Function fetch_last_id() should return Cached ID when $next_id is 0
	 * Function fetch_last_id() should return Cached ID when $next_id is false,
	 * Function fetch_last_id() should return  $next_id when $next_id is not 0 nor false.
	 */
	public function provider_fetch_last_id_with_paramter_with_transient() {
		return array(
			array( 0, 2 ),
			array( false, 2 ),
			array( 1, 1 ),
		);
	}

	/**
	 * Test steps for constructor.
	 * 
	 * @dataProvider provider_fetch_last_id_with_paramter_without_transient
	 * 
	 * @param string $next_id ID to set next.
	 * @param string $expect  Expect return value.
	 */
	public function test_fetch_last_id_with_paramter_without_transient( $next_id, $expect ) {
		$result = $this->create_accessable_method( 'fetch_last_id', array( $next_id ) );
		$this->assertEquals( $result, $expect );
	}

	/**
	 * Function fetch_last_id() should return Cached ID when $next_id is 0
	 * Function fetch_last_id() should return Cached ID when $next_id is false,
	 * Function fetch_last_id() should return  $next_id when $next_id is not 0 nor false.
	 */
	public function provider_fetch_last_id_with_paramter_without_transient() {
		return array(
			array( 0, 0 ),
			array( false, 0 ),
			array( 1, 1 ),
		);
	}

	/**
	 * Function fetch_url() should return false when URLs do not exist in database table.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_fetch_url_url_not_exists() {
		$this->assertFalse( $this->create_accessable_method( 'fetch_url', array() ) );
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
				'other_page',
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
				'other_page',
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
			new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test1/', '1' ),
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
			Test_Utility::EXTENSION_STATIC_FILE,
			Test_Utility::create_remote_getter_mock(),
			Test_Utility::create_terminator_mock()
		);
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $static_press, $array_parameter );
	}
}

<?php
/**
 * Class Static_Press_Transient_Service_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-test-utility.php';
use static_press\includes\Static_Press_Transient_Service;
use static_press\tests\testlibraries\Test_Utility;

/**
 * Static_Press_Transient_Service test case.
 *
 * @noinspection PhpUndefinedClassInspection
 */
class Static_Press_Transient_Service_Test extends \WP_UnitTestCase {
	const DATE_FOR_TEST    = '2019-12-23 12:34:56';
	/**
	 * Function fetch_last_id() should return 0 when parameter is not set.
	 */
	public function test_fetch_last_id_without_parameter_with_transient() {
		set_transient( 'static static', array( 'fetch_last_id' => 2 ), 3600 );
		$result = Static_Press_Transient_Service::fetch_last_id();
		$this->assertEquals( $result, 2 );
	}

	/**
	 * Function fetch_last_id() should return 0 when parameter is not set.
	 */
	public function test_fetch_last_id_without_parameter_without_transient() {
		$result = Static_Press_Transient_Service::fetch_last_id();
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
		$result = Static_Press_Transient_Service::fetch_last_id( $next_id );
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
		$result = Static_Press_Transient_Service::fetch_last_id( $next_id );
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
	 * Function fetch_start_time() should return current date time string
	 * when fetch_start_time in transient_key is not set.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_fetch_start_time() {
		$date_time_factory = Test_Utility::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s', self::DATE_FOR_TEST );
		$transient_service = new Static_Press_Transient_Service( $date_time_factory );
		$result            = $transient_service->fetch_start_time();
		$this->assertEquals( self::DATE_FOR_TEST, $result );
	}

	/**
	 * Function fetch_start_time() should return fetch_start_time in transient_key
	 * when fetch_start_time in transient_key is set.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_fetch_start_time_transient_key() {
		$start_time                = '2019-12-23 12:34:56';
		$param['fetch_start_time'] = $start_time;
		set_transient( 'static static', $param, 3600 );
		$transient_service = new Static_Press_Transient_Service();
		$result            = $transient_service->fetch_start_time();
		$this->assertEquals( $start_time, $result );
	}
}

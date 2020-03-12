<?php
/**
 * Class Static_Press_Repository_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

use static_press\includes\Static_Press_Transient_Manager;

/**
 * Transient manager test case.
 */
class Static_Press_Transient_Manager_Test extends \WP_UnitTestCase {
	/**
	 * Function get_transient_key() should return appropriate string when current user id is not set.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_get_transient_key() {
		$result = $this->call_private_method( 'get_transient_key', array() );
		$this->assertEquals( 'static static', $result );
	}

	/**
	 * Function get_transient_key() should return appropriate string when current user id is set.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_get_transient_key_current_user() {
		wp_set_current_user( 1 );
		$result = $this->call_private_method( 'get_transient_key', array() );
		$this->assertEquals( 'static static - 1', $result );
	}

	/**
	 * Function delete_transient() should delete transient.
	 */
	public function test_delete_transient() {
		set_transient( 'static static', array( 'fetch_last_id' => 2 ), 3600 );
		Static_Press_Transient_Manager::delete_transient();
		$this->assertFalse( get_transient( 'static static' ) );
	}

	/**
	 * Call private method.
	 * 
	 * @param string $method_name     Method name.
	 * @param array  $array_parameter Array of parameter.
	 */
	private function call_private_method( $method_name, $array_parameter ) {
		$transient_manager = new Static_Press_Transient_Manager();
		$reflection        = new \ReflectionClass( get_class( $transient_manager ) );
		$method            = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $transient_manager, $array_parameter );
	}
}

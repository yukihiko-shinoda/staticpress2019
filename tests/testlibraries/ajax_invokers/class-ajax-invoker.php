<?php
/**
 * Ajax_Invoker
 *
 * @package static_press\tests\testlibraries\ajax_invokers
 */

namespace static_press\tests\testlibraries\ajax_invokers;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/exceptions/class-die-exception.php';
use static_press\tests\testlibraries\exceptions\Die_Exception;

/**
 * Class Ajax_Invoker
 */
abstract class Ajax_Invoker {
	/**
	 * ExpectUrl constructor.
	 *
	 * @param PHPUnit_Framework_TestCase $test_case    Expect type of URL object.
	 * @param Static_Press               $static_press StaticPress.
	 */
	public function __construct( $test_case, $static_press ) {
		$this->test_case    = $test_case;
		$this->static_press = $static_press;
	}

	/**
	 * Requests.
	 * 
	 * @return array JSON responce.
	 */
	public function request() {
		ob_start();
		try {
			$this->invoke_ajax();
		} catch ( Die_Exception $exception ) {
			$output = ob_get_clean();
			$this->test_case->assertEquals( 'Dead!', $exception->getMessage() );
			return json_decode( $output, true );
		}
		$this->test_case->fail();
	}

	/**
	 * Invokes ajax function.
	 */
	abstract protected function invoke_ajax();
}

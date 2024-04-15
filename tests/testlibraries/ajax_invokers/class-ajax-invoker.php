<?php
/**
 * Ajax_Invoker
 *
 * @package static_press\tests\testlibraries\ajax_invokers
 */

namespace static_press\tests\testlibraries\ajax_invokers;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/exceptions/class-die-exception.php';
use static_press\tests\testlibraries\creators\Mock_Creator;
use static_press\tests\testlibraries\exceptions\Die_Exception;

/**
 * Class Ajax_Invoker
 */
abstract class Ajax_Invoker {
	/**
	 * Expect type of URL object.
	 *
	 * @var PHPUnit_Framework_TestCase
	 */
	private $test_case;
	/**
	 * StaticPress.
	 *
	 * @var Static_Press
	 */
	protected $static_press;
	/**
	 * The JSON string as response.
	 *
	 * @var string
	 */
	private $actual_json;
	/**
	 * The mock for Terminator to prevent to call die().
	 *
	 * @var MockInterface
	 */
	protected $terminator_mock;
	/**
	 * ExpectUrl constructor.
	 *
	 * @param PHPUnit_Framework_TestCase $test_case    Expect type of URL object.
	 * @param Static_Press               $static_press StaticPress.
	 */
	public function __construct( $test_case, $static_press ) {
		$this->test_case       = $test_case;
		$this->static_press    = $static_press;
		$this->actual_json     = "";
		$this->terminator_mock = Mock_Creator::create_terminator_mock( $this->actual_json );
	}

	/**
	 * Requests.
	 *
	 * @return array JSON response.
	 */
	public function request() {
		try {
			$this->invoke_ajax();
		} catch ( Die_Exception $exception ) {
			$this->test_case->assertEquals( 'Dead!', $exception->getMessage() );
			return json_decode( $this->actual_json, true );
		}
		$this->test_case->fail();
	}

	/**
	 * Invokes ajax function.
	 */
	abstract protected function invoke_ajax();
}

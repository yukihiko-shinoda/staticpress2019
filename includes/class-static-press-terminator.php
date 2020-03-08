<?php
/**
 * Class Static_Press_Terminator
 *
 * @package static_press\includes
 */

namespace static_press\includes;

/**
 * To isolate die() for testing.
 * 
 * @see https://stackoverflow.com/questions/1347794/how-do-you-use-phpunit-to-test-a-function-if-that-function-is-supposed-to-kill-p/21578225#21578225
 */
class Static_Press_Terminator {
	/**
	 * Calls die().
	 */
	public function terminate() {
		die();
	}
}

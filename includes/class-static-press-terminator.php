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
	 * 
	 * @param string  $content          The content of response.
	 * @param integer $http_status_code HTTP status code.
	 */
	public function terminate( $content = '', $http_status_code = 200 ) {
		// Since Second argument of wp_die() doesn't work correctly in PHP 4.3.
		status_header( $http_status_code );
		wp_die( $content, $http_status_code );
	}
}

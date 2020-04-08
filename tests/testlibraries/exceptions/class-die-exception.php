<?php
/**
 * Die_Exception
 * 
 * @see https://stackoverflow.com/questions/1347794/how-do-you-use-phpunit-to-test-a-function-if-that-function-is-supposed-to-kill-p/21578225#21578225
 * @package static_press\tests\testlibraries\exceptions
 */

namespace static_press\tests\testlibraries\exceptions;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/exceptions/class-business-logic-exception.php';
use static_press\tests\testlibraries\exceptions\Business_Logic_Exception;
/**
 * Class Die_Exception
 */
class Die_Exception extends Business_Logic_Exception {
}

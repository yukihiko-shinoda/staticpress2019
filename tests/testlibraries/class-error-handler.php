<?php
/**
 * Class Error_Handler
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

/**
 * Error handler.
 */
class Error_Handler {
	/**
	 * Handles error.
	 * 
	 * @param int    $errno      The first parameter, errno, contains the level of the error raised, as an integer.
	 * @param string $errstr     The second parameter, errstr, contains the error message, as a string.
	 * @param string $errfile    The third parameter is optional, errfile, which contains the filename that the error was raised in, as a string.
	 * @param int    $errline    The fourth parameter is optional, errline, which contains the line number the error was raised at, as an integer.
	 * @param array  $errcontext The fifth parameter is optional, errcontext, which is an array that points to the active symbol table at the point the error occurred.
	 *                           In other words, errcontext will contain an array of every variable that existed in the scope the error was triggered in.
	 *                           User error handler must not modify error context.
	 * @return false
	 * @throws \LogicException When error.
	 */
	public function handle( $errno, $errstr, $errfile, $errline, $errcontext ) {
		// error was suppressed with the @-operator.
		if ( 0 === error_reporting() ) {
			return false;
		}
		throw new \LogicException( $errstr, $errno );
	}

	/**
	 * Ignores error for issues due to WordPress Core's bug.
	 * 
	 * @param int    $errno      The first parameter, errno, contains the level of the error raised, as an integer.
	 * @param string $errstr     The second parameter, errstr, contains the error message, as a string.
	 * @param string $errfile    The third parameter is optional, errfile, which contains the filename that the error was raised in, as a string.
	 * @param int    $errline    The fourth parameter is optional, errline, which contains the line number the error was raised at, as an integer.
	 * @param array  $errcontext The fifth parameter is optional, errcontext, which is an array that points to the active symbol table at the point the error occurred.
	 *                           In other words, errcontext will contain an array of every variable that existed in the scope the error was triggered in.
	 *                           User error handler must not modify error context.
	 */
	public function ignore( $errno, $errstr, $errfile, $errline, $errcontext = null ) {
	}
}

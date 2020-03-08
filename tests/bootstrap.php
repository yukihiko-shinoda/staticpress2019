<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Staticpress
 */

$static_press_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $static_press_tests_dir ) {
	$static_press_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $static_press_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $static_press_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $static_press_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function static_press_manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/plugin.php';
}
tests_add_filter( 'muplugins_loaded', 'static_press_manually_load_plugin' );

// Start up the WP testing environment.
require $static_press_tests_dir . '/includes/bootstrap.php';

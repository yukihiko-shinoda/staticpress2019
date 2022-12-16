<?php
/**
 * Class Polyfill_WP_UnitTestCase
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;
global $wp_version;
if ( version_compare( $wp_version, '5.9.0', '<' ) ) {
	require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-polyfill-wp-unit-test-case-base.php';
} else {
	require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-polyfill-wp-unit-test-case-non-polyfill-base.php';
}
use static_press\tests\testlibraries\Polyfill_WP_UnitTestCase_Base;
/**
 * Polyfill WP unit test case.
 */
abstract class Polyfill_WP_UnitTestCase extends Polyfill_WP_UnitTestCase_Base {
}

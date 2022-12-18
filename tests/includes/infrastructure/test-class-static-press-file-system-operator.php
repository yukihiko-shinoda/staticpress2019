<?php
/**
 * Class Static_Press_File_System_Operator_Test
 *
 * @package static_press\tests\includes\infrastructure
 */

namespace static_press\tests\includes\infrastructure;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-polyfill-wp-unittestcase.php';
use static_press\includes\infrastructure\Static_Press_File_System_Operator;
use static_press\tests\testlibraries\Polyfill_WP_UnitTestCase;

/**
 * Static_Press_File_System_Operator test case.
 */
class Static_Press_File_System_Operator_Test extends Polyfill_WP_UnitTestCase {
	/**
	 * Put up test directories.
	 */
	public function tear_down() {
		rmdir( '/tmp/sub1/sub2' );
		rmdir( '/tmp/sub1' );
	}

	/**
	 * Function make_subdirectories() should make subdirectories.
	 */
	public function test_make_subdirectories() {
		global $wp_version;
		if ( version_compare( $wp_version, '5.9.0', '<' ) ) {
			$this->assertDirectoryNotExists( '/tmp/sub1' );
		} else {
			$this->assertDirectoryDoesNotExist( '/tmp/sub1' );
		}
		Static_Press_File_System_Operator::make_subdirectories( '/tmp/sub1/sub2/file' );
		$this->assertDirectoryIsWritable( '/tmp' );
		$this->assertDirectoryIsWritable( '/tmp/sub1' );
		$this->assertDirectoryIsWritable( '/tmp/sub1/sub2' );
	}
}

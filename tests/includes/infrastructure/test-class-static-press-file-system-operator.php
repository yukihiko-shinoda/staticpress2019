<?php
/**
 * Class Static_Press_File_System_Operator_Test
 *
 * @package static_press\tests\includes\infrastructure
 */

namespace static_press\tests\includes\infrastructure;

use static_press\includes\infrastructure\Static_Press_File_System_Operator;

/**
 * Static_Press_File_System_Operator test case.
 */
class Static_Press_File_System_Operator_Test extends \WP_UnitTestCase {
	/**
	 * Put up test directories.
	 */
	public function tearDown() {
		rmdir( '/tmp/sub1/sub2' );
		rmdir( '/tmp/sub1' );
	}

	/**
	 * Function make_subdirectories() should make subdirectories.
	 */
	public function test_make_subdirectories() {
		$this->assertDirectoryNotExists( '/tmp/sub1' );
		Static_Press_File_System_Operator::make_subdirectories( '/tmp/sub1/sub2/file' );
		$this->assertDirectoryIsWritable( '/tmp' );
		$this->assertDirectoryIsWritable( '/tmp/sub1' );
		$this->assertDirectoryIsWritable( '/tmp/sub1/sub2' );
	}
}

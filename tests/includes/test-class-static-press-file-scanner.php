<?php
/**
 * Class Static_Press_File_Scanner_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

use static_press\includes\Static_Press_File_Scanner;
use static_press\includes\Static_Press_Model_Url_Static_File;
use static_press\includes\Static_Press_Model_Static_File;
/**
 * StaticPress test case.
 */
class Static_Press_File_Scanner_Test extends \WP_UnitTestCase {
	const DIRECTORY         = '/tmp/test';
	const DIRECTORY_SUB     = '/tmp/test/sub_directory';
	const DIRECTORY_SUB_SUB = '/tmp/test/sub_directory/sub_sub_directory';
	/**
	 * Extensions which is not static file.
	 */
	// Reason: This project no longer support PHP 5.5 nor lower.
	const EXTENSION_NOT_STATIC_FILE = array( //phpcs:ignore
		'xlsx', // Maybe, not intended.
	);
	/**
	 * Static files.
	 * 
	 * @var Static_Press_Model_Url_Static_File[]
	 */
	private $array_static_file;
	/**
	 * Not static files.
	 * 
	 * @var Static_Press_Model_Url_Static_File[]
	 */
	private $array_not_static_file;

	/**
	 * Removes test files and directories.
	 */
	public function setUp() {
		parent::setUp();
		$array_directory             = array( self::DIRECTORY, self::DIRECTORY_SUB, self::DIRECTORY_SUB_SUB );
		$this->array_static_file     = $this->list_files( $array_directory, Static_Press_Model_Static_File::get_filtered_array_extension() );
		$this->array_not_static_file = $this->list_files( $array_directory, self::EXTENSION_NOT_STATIC_FILE );
		if ( ! file_exists( self::DIRECTORY_SUB_SUB ) ) {
			mkdir( self::DIRECTORY_SUB_SUB, 0755, true );
		}
	}

	/**
	 * Removes test files and directories.
	 */
	public function tearDown() {
		self::rmdir( self::DIRECTORY );
		parent::tearDown();
	}

	/**
	 * Function scan_file should returns list of file.
	 */
	public function test_scan_file() {
		$static_press_file_scanner = new Static_Press_File_Scanner( Static_Press_Model_Static_File::get_filtered_array_extension() );
		$actual                    = $static_press_file_scanner->scan( trailingslashit( self::DIRECTORY ), true );
		$this->assertEquals( $this->array_static_file, $actual );
	}

	/**
	 * Lists files.
	 * 
	 * @param string[] $array_directory Array of directories.
	 * @param string[] $array_extension Array of extension.
	 * @return Static_Press_Model_Url_Static_File[] Array of model URL static file.
	 */
	private function list_files( $array_directory, $array_extension ) {
		$array_file = array();
		foreach ( $array_directory as $directory ) {
			foreach ( $array_extension as $extension ) {
				$file_path = $directory . '/test.' . $extension;
				file_put_contents( $file_path, '' );
				$array_file[] = new Static_Press_Model_Url_Static_File( $file_path );
			}
		}
		return $array_file;
	}
}

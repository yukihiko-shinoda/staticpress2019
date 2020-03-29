<?php
/**
 * Class Static_Press_File_Scanner_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-environment.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-test-utility.php';
use static_press\includes\Static_Press_Factory_Model_Url_Static_File;
use static_press\includes\Static_Press_File_Scanner;
use static_press\includes\Static_Press_Model_Url_Static_File;
use static_press\includes\Static_Press_Model_Static_File;
use static_press\includes\Static_Press_Model_Url;
use static_press\tests\testlibraries\Environment;
use static_press\tests\testlibraries\Test_Utility;

/**
 * StaticPress test case.
 */
class Static_Press_File_Scanner_Test extends \WP_UnitTestCase {
	// Reason: This project no longer support PHP 5.5 nor lower.
	const DIRECTORY_STATIC          = ABSPATH . 'test';                                         // phpcs:ignore
	const DIRECTORY_STATIC_SUB      = ABSPATH . 'test/sub_directory';                           // phpcs:ignore
	const DIRECTORY_STATIC_SUB_SUB  = ABSPATH . 'test/sub_directory/sub_sub_directory';         // phpcs:ignore
	const DIRECTORY_CONTENT         = WP_CONTENT_DIR . '/test';                                 // phpcs:ignore
	const DIRECTORY_CONTENT_SUB     = WP_CONTENT_DIR . '/test/sub_directory';                   // phpcs:ignore
	const DIRECTORY_CONTENT_SUB_SUB = WP_CONTENT_DIR . '/test/sub_directory/sub_sub_directory'; // phpcs:ignore
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
	 * Static files.
	 * 
	 * @var Static_Press_Model_Url_Static_File[]
	 */
	private $array_content_file;

	/**
	 * Removes test files and directories.
	 */
	public function setUp() {
		parent::setUp();
		if ( ! file_exists( self::DIRECTORY_STATIC_SUB_SUB ) ) {
			mkdir( self::DIRECTORY_STATIC_SUB_SUB, 0755, true );
		}
		if ( ! file_exists( self::DIRECTORY_CONTENT_SUB_SUB ) ) {
			mkdir( self::DIRECTORY_CONTENT_SUB_SUB, 0755, true );
		}
		$array_directory_static   = array( self::DIRECTORY_STATIC, self::DIRECTORY_STATIC_SUB, self::DIRECTORY_STATIC_SUB_SUB );
		$array_directory_content  = array( self::DIRECTORY_CONTENT, self::DIRECTORY_CONTENT_SUB, self::DIRECTORY_CONTENT_SUB_SUB );
		$this->array_static_file  = $this->list_files( $array_directory_static, Static_Press_Model_Static_File::get_filtered_array_extension(), Static_Press_Model_Url::TYPE_STATIC_FILE, ABSPATH );
		$this->array_content_file = $this->list_files( $array_directory_content, Static_Press_Model_Static_File::get_filtered_array_extension(), Static_Press_Model_Url::TYPE_CONTENT_FILE, WP_CONTENT_DIR );
		$this->list_files( $array_directory_static, self::EXTENSION_NOT_STATIC_FILE, Static_Press_Model_Url::TYPE_STATIC_FILE, ABSPATH );
		$this->list_files( $array_directory_content, self::EXTENSION_NOT_STATIC_FILE, Static_Press_Model_Url::TYPE_CONTENT_FILE, WP_CONTENT_DIR );
	}

	/**
	 * Removes test files and directories.
	 */
	public function tearDown() {
		self::rmdir( self::DIRECTORY_STATIC );
		self::rmdir( self::DIRECTORY_CONTENT );
		parent::tearDown();
	}

	/**
	 * Function scan() should returns list of file.
	 */
	public function test_scan_static() {
		$static_press_file_scanner = new Static_Press_File_Scanner(
			Static_Press_Model_Static_File::get_filtered_array_extension(),
			Static_Press_Model_Url::TYPE_STATIC_FILE,
			new Static_Press_Factory_Model_Url_Static_File( Test_Utility::create_docuemnt_root_getter_mock() )
		);
		$actual                    = $static_press_file_scanner->scan( '/test/', true );
		$this->assertEquals( $this->array_static_file, $actual );
	}

	/**
	 * Function scan() should returns list of file.
	 */
	public function test_scan_content() {
		$static_press_file_scanner = new Static_Press_File_Scanner(
			Static_Press_Model_Static_File::get_filtered_array_extension(),
			Static_Press_Model_Url::TYPE_CONTENT_FILE,
			new Static_Press_Factory_Model_Url_Static_File( Test_Utility::create_docuemnt_root_getter_mock() )
		);
		$actual                    = $static_press_file_scanner->scan( '/test/', true );
		$this->assertEquals( $this->array_content_file, $actual );
	}

	/**
	 * Lists files.
	 * 
	 * @param string[] $array_directory Array of directories.
	 * @param string[] $array_extension Array of extension.
	 * @param string   $file_type       File type.
	 * @return Static_Press_Model_Url_Static_File[] Array of model URL static file.
	 */
	private function list_files( $array_directory, $array_extension, $file_type ) {
		$array_file = array();
		foreach ( $array_directory as $directory ) {
			foreach ( $array_extension as $extension ) {
				$file_path = $directory . '/test.' . $extension;
				file_put_contents( $file_path, '' );
				$array_file[] = new Static_Press_Model_Url_Static_File( $file_type, trailingslashit( Environment::get_document_root() ), $file_path );
			}
		}
		return $array_file;
	}
}

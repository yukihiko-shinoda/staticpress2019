<?php
/**
 * Class Static_Press_File_Scanner_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

use static_press\includes\Static_Press_File_Scanner;

/**
 * StaticPress test case.
 */
class Static_Press_File_Scanner_Test extends \WP_UnitTestCase {
	const DIRECTORY         = '/tmp/test';
	const DIRECTORY_SUB     = '/tmp/test/sub_directory';
	const DIRECTORY_SUB_SUB = '/tmp/test/sub_directory/sub_sub_directory';
	/**
	 * Extensions which is static file.
	 */
	// Reason: This project no longer support PHP 5.5 nor lower.
	const EXTENSION_STATIC_FILE = array( //phpcs:ignore
		'html',
		'htm',
		'txt',
		'css',
		'js',
		'gif',
		'png',
		'jpg',
		'jpeg',
		'mp3',
		'ico',
		'ttf',
		'woff',
		'woff2',
		'otf',
		'eot',
		'svg',
		'svgz',
		'xml',
		'gz',
		'zip',
		'pdf',
		'swf',
		'xsl',
		'mov',
		'mp4',
		'wmv',
		'flv',
		'webm',
		'ogg',
		'oga',
		'ogv',
		'ogx',
		'spx',
		'opus',
	);
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
	 * @var array
	 */
	private $array_static_file;
	/**
	 * Not static files.
	 * 
	 * @var array
	 */
	private $array_not_static_file;

	/**
	 * Removes test files and directories.
	 */
	public function setUp() {
		parent::setUp();
		$this->listup_files( array( self::DIRECTORY_SUB_SUB, self::DIRECTORY_SUB, self::DIRECTORY ) );
		if ( ! file_exists( self::DIRECTORY_SUB_SUB ) ) {
			mkdir( self::DIRECTORY_SUB_SUB, 0755, true );
		}
		$this->create_files( $this->array_static_file );
		$this->create_files( $this->array_not_static_file );
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
		$static_press_file_scanner = new Static_Press_File_Scanner( apply_filters( 'StaticPress::static_files_filter', self::EXTENSION_STATIC_FILE ) );
		$actual                    = $static_press_file_scanner->scan( trailingslashit( self::DIRECTORY ), true );
		$this->assertEquals( $this->array_static_file, $actual );
	}

	/**
	 * Creates all files for test.
	 * 
	 * @param string $array_file Target directory.
	 */
	private function create_files( $array_file ) {
		foreach ( $array_file as $file ) {
			file_put_contents( $file, '' );
		}
	}

	/**
	 * Listups files.
	 * 
	 * @param string[] $array_directory Array of directories.
	 */
	private function listup_files( $array_directory ) {
		$this->array_static_file     = $this->listup( $array_directory, self::EXTENSION_STATIC_FILE );
		$this->array_not_static_file = $this->listup( $array_directory, self::EXTENSION_NOT_STATIC_FILE );
	}

	/**
	 * Listups files.
	 * 
	 * @param string[] $array_directory Array of directories.
	 */
	private function listup( $array_directory ) {
		$array_file = array();
		foreach ( $array_directory as $directory ) {
			foreach ( self::EXTENSION_STATIC_FILE as $extension ) {
				$array_file[] = $directory . '/test.' . $extension;
			}
		}
		return $array_file;
	}
}

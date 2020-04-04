<?php
/**
 * Class File_System_Operator
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

/**
 * File system operator.
 */
class File_System_Operator {
	const OUTPUT_DIRECTORY = '/tmp/static/';
	/**
	 * Gets test resource content.
	 * 
	 * @param string $file_name Related path based on testresources directory not start with '/'.
	 * @return string Content.
	 */
	public static function get_test_resource_content( $file_name ) {
		return file_get_contents( self::get_path_to_test_resource( $file_name ) );
	}

	/**
	 * Copies test resource content.
	 * 
	 * @param string $file_name   Related path based on testresources directory not start with '/'.
	 * @param string $target_path Target path.
	 * @return string Content.
	 */
	public static function copy_test_resource( $file_name, $target_path ) {
		self::ensure_directory( dirname( $target_path ) );
		return copy( self::get_path_to_test_resource( $file_name ), $target_path );
	}

	/**
	 * Copies test resource content.
	 * 
	 * @param string $file_name   Related path based on testresources directory not start with '/'.
	 * @return string Path to test resource.
	 */
	public static function get_path_to_test_resource( $file_name ) {
		return dirname( dirname( __FILE__ ) ) . '/testresources/' . $file_name;
	}

	/**
	 * Creates static file of active plugin.
	 * 
	 * @param string $path           Path.
	 */
	public static function create_file_with_directory( $path ) {
		self::ensure_directory( dirname( $path ) );
		file_put_contents( $path, '' );
	}

	/**
	 * Ensures directory.
	 * 
	 * @param string $directory Directory.
	 */
	public static function ensure_directory( $directory ) {
		if ( ! file_exists( $directory ) ) {
			mkdir( $directory, 0777, true );
		}
	}

	/**
	 * PHP delete function that deals with directories recursively.
	 *
	 * @see https://paulund.co.uk/php-delete-directory-and-files-in-directory
	 *
	 * @param string $target Example: '/path/for/the/directory/' .
	 */
	public static function delete_files( $target = self::OUTPUT_DIRECTORY ) {
		if ( is_dir( $target ) ) {
			$files = glob( $target . '*', GLOB_MARK ); // GLOB_MARK adds a slash to directories returned.
			foreach ( $files as $path ) {
				self::delete_files( $path );
			}
			rmdir( $target );
		} elseif ( is_file( $target ) ) {
			unlink( $target );
		}
	}

	/**
	 * Copy entire contents of a directory to another directory.
	 * 
	 * @see https://stackoverflow.com/questions/2050859/copy-entire-contents-of-a-directory-to-another-using-php/2050909#2050909
	 * @param string $src Source.
	 * @param string $dst Destination.
	 */
	public static function recurse_copy( $src, $dst ) {
		$dir = opendir( $src );
		if ( ! file_exists( $dst ) ) {
			mkdir( $dst );
		}
		while ( false !== ( $path = readdir( $dir ) ) ) {
			if ( ( '.' != $path ) && ( '..' != $path ) ) {
				if ( is_dir( $src . '/' . $path ) ) {
					self::recurse_copy( $src . '/' . $path, $dst . '/' . $path );
				} else {
					copy( $src . '/' . $path, $dst . '/' . $path );
				}
			}
		}
		closedir( $dir );
	}

	/**
	 * Gets array file in output directory.
	 */
	public static function get_array_file_in_output_directory() {
		return array_filter( self::rglob( self::OUTPUT_DIRECTORY . '*' ), 'is_file' );
	}

	/**
	 * Glob recursive.
	 * 
	 * @see https://stackoverflow.com/questions/17160696/php-glob-scan-in-subfolders-for-a-file/17161106#17161106
	 * @param string  $pattern Pattern.
	 * @param integer $flags   Flags.
	 * @return string[] Files.
	 */
	private static function rglob( $pattern, $flags = 0 ) {
		$files = glob( $pattern, $flags ); 
		foreach ( glob( dirname( $pattern ) . '/*', GLOB_ONLYDIR | GLOB_NOSORT ) as $dir ) {
			$files = array_merge( $files, self::rglob( $dir . '/' . basename( $pattern ), $flags ) );
		}
		return $files;
	}
}

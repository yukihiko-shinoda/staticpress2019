<?php
/**
 * Class Static_Press_File_Scanner
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Model_Url_Static_File' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-model-url-static-file.php';
}
use static_press\includes\Static_Press_Model_Url_Static_File;
/**
 * File scanner.
 * This class should be instantiated before entering loop since constructor includes loop process.
 */
class Static_Press_File_Scanner {
	/**
	 * Concatenated extensions of static file.
	 * 
	 * @var string
	 */
	private $concatenated_extension_static_file;

	/**
	 * Constructor.
	 * 
	 * @param array $array_extension_static_file Array of extension of static file.
	 */
	public function __construct( $array_extension_static_file ) {
		$static_files_filter = $array_extension_static_file;
		foreach ( $static_files_filter as &$file_ext ) {
			$file_ext = '*.' . $file_ext;
		}
		$this->concatenated_extension_static_file = '{' . implode( ',', $static_files_filter ) . '}';        
	}

	/**
	 * Scans files.
	 * 
	 * @param string $dir       Directory.
	 * @param bool   $recursive Whether scan recursive or not.
	 * @return Static_Press_Model_Url_Static_File[] Array of path.
	 */
	public function scan( $dir, $recursive = true ) {
		$list = array();
		foreach ( glob( $dir . $this->concatenated_extension_static_file, GLOB_BRACE ) as $path ) {
			$list[] = new Static_Press_Model_Url_Static_File( $path );
		}
		if ( ! $recursive ) {
			return $list;
		}
		foreach ( glob( $dir . '*/', GLOB_ONLYDIR ) as $child_dir ) {
			$tmp = $this->scan( $child_dir );
			if ( $tmp ) {
				$list = array_merge( $list, $tmp );
			}
		}
		return $list;
	}
}

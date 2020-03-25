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
	 * File type.
	 * 
	 * @var string
	 */
	private $file_type;
	/**
	 * Base directory.
	 * 
	 * @var string
	 */
	private $base_directory;

	/**
	 * Constructor.
	 * 
	 * @param array  $array_extension_static_file Array of extension of static file.
	 * @param string $file_type File type.
	 */
	public function __construct( $array_extension_static_file, $file_type = Static_Press_Model_Url::TYPE_STATIC_FILE ) {
		$static_files_filter = $array_extension_static_file;
		foreach ( $static_files_filter as &$file_ext ) {
			$file_ext = '*.' . $file_ext;
		}
		$this->concatenated_extension_static_file = '{' . implode( ',', $static_files_filter ) . '}';
		$this->file_type                          = $file_type;
		$this->base_directory                     = trailingslashit( Static_Press_Model_Url_Static_File::get_base_directory( $file_type ) );
	}

	/**
	 * Scans.
	 * 
	 * @param string $directory      Directory.
	 * @param bool   $recursive      Whether scan recursive or not.
	 * @return Static_Press_Model_Url_Static_File[] Array of path.
	 */
	public function scan( $directory, $recursive = true ) {
		return $this->scan_file( untrailingslashit( $this->base_directory ) . $directory, $recursive );
	}

	/**
	 * Scans files.
	 * 
	 * @param string $directory      Directory.
	 * @param bool   $recursive      Whether scan recursive or not.
	 * @return Static_Press_Model_Url_Static_File[] Array of path.
	 */
	private function scan_file( $directory, $recursive = true ) {
		$list = array();
		foreach ( glob( $directory . $this->concatenated_extension_static_file, GLOB_BRACE ) as $path ) {
			$list[] = new Static_Press_Model_Url_Static_File( $this->file_type, $this->base_directory, $path );
		}
		if ( ! $recursive ) {
			return $list;
		}
		foreach ( glob( $directory . '*/', GLOB_ONLYDIR ) as $child_dir ) {
			$tmp = $this->scan_file( $child_dir );
			if ( $tmp ) {
				$list = array_merge( $list, $tmp );
			}
		}
		return $list;
	}
}

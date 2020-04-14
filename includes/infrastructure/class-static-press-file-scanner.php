<?php
/**
 * Class Static_Press_File_Scanner
 *
 * @package static_press\includes\infrastructure
 */

namespace static_press\includes\infrastructure;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/models/class-static-press-model-url.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/models/class-static-press-model-url-static-file.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/factories/class-static-press-factory-model-url-static-file.php';
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\models\Static_Press_Model_Url_Static_File;
use static_press\includes\factories\Static_Press_Factory_Model_Url_Static_File;
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
	 * Factory of model URL static file.
	 * 
	 * @var Static_Press_Factory_Model_Url_Static_File
	 */
	private $factory_model_url_static_file;

	/**
	 * Constructor.
	 * 
	 * @param array                                      $array_extension_static_file   Array of extension of static file.
	 * @param string                                     $file_type                     File type.
	 * @param Static_Press_Factory_Model_Url_Static_File $factory_model_url_static_file Factory of model URL static file.
	 */
	public function __construct( $array_extension_static_file, $file_type = Static_Press_Model_Url::TYPE_STATIC_FILE, $factory_model_url_static_file = null ) {
		$static_files_filter = $array_extension_static_file;
		foreach ( $static_files_filter as &$file_ext ) {
			$file_ext = '*.' . $file_ext;
		}
		$this->concatenated_extension_static_file = '{' . implode( ',', $static_files_filter ) . '}';
		$this->file_type                          = $file_type;
		$this->base_directory                     = trailingslashit( Static_Press_Model_Url_Static_File::get_base_directory( $file_type ) );
		$this->factory_model_url_static_file      = $factory_model_url_static_file ? $factory_model_url_static_file : new Static_Press_Factory_Model_Url_Static_File();
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
			$list[] = $this->factory_model_url_static_file->create( $this->file_type, $path );
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

<?php
/**
 * Class Static_Press_Model_Url_Static_File
 *
 * @package static_press\includes\models
 */

namespace static_press\includes\models;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/repositories/class-static-press-repository.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-site-dependency.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/models/class-static-press-model-url.php';
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\repositories\Static_Press_Repository;
use static_press\includes\Static_Press_Site_Dependency;
/**
 * Model URL static file.
 */
class Static_Press_Model_Url_Static_File extends Static_Press_Model_Url {
	/**
	 * Constructor.
	 * 
	 * @param string $file_type   File type.
	 * @param string $directory   Directory.
	 * @param string $static_file Static file.
	 */
	public function __construct( $file_type, $directory, $static_file ) {
		$static_file_url = str_replace(
			$directory,
			trailingslashit( Static_Press_Site_Dependency::get_site_url() ),
			$static_file
		);
		parent::__construct(
			null,
			$file_type,
			apply_filters( 'StaticPress::get_url', $static_file_url ),
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			date( 'Y-m-d h:i:s', filemtime( $static_file ) )
		);
	}

	/**
	 * Converts to array.
	 * 
	 * @return array
	 */
	public function to_array() {
		return array_merge(
			$this->to_array_common(),
			array(
				Static_Press_Repository::FIELD_NAME_TYPE => $this->get_type(),
				Static_Press_Repository::FIELD_NAME_LAST_MODIFIED => $this->get_last_modified(),
			)
		);
	}

	/**
	 * Gets base directory.
	 * 
	 * @param string $file_type File type.
	 * @return string Base directory.
	 * @throws \LogicException Invalid file type.
	 */
	public static function get_base_directory( $file_type ) {
		switch ( $file_type ) {
			case self::TYPE_STATIC_FILE:
				return ABSPATH;
			case self::TYPE_CONTENT_FILE:
				return WP_CONTENT_DIR;
			default:
				throw new \LogicException();
		}
	}
}

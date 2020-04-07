<?php
/**
 * Class Static_File_Creator_For_Test
 *
 * @package static_press\tests\testlibraries\creators
 */

namespace static_press\tests\testlibraries\creators;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/infrastructure/class-environment.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/infrastructure/class-file-system-operator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-plugin-switcher.php';
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\models\Static_Press_Model_Url_Static_File;
use static_press\tests\testlibraries\infrastructure\Environment;
use static_press\tests\testlibraries\infrastructure\File_System_Operator;
use static_press\tests\testlibraries\Plugin_Switcher;
/**
 * Static file creator.
 */
class Static_File_Creator_For_Test {
	/**
	 * Creates static file of readme.
	 * 
	 * @return Static_Press_Model_Url_Static_File Static file of readme.
	 */
	public static function create_static_file_readme() {
		return self::create_static_file( Static_Press_Model_Url::TYPE_STATIC_FILE, ABSPATH . 'readme.txt' );
	}

	/**
	 * Creates static file of not exist.
	 * 
	 * @return Static_Press_Model_Url_Static_File Static file of not exist.
	 */
	public static function create_static_file_not_exist() {
		$path = ABSPATH . 'test.png';
		$url  = self::create_static_file( Static_Press_Model_Url::TYPE_STATIC_FILE, $path );
		unlink( $path );
		return $url;
	}

	/**
	 * Creates static file of not updated after last dump.
	 * 
	 * @return Static_Press_Model_Url_Static_File Static file of not updated after last dump.
	 */
	public static function create_static_file_not_updated() {
		File_System_Operator::create_file_with_directory( File_System_Operator::OUTPUT_DIRECTORY . Environment::DIRECTORY_NAME_WORD_PRESS . '/test.txt' );
		return self::create_static_file( Static_Press_Model_Url::TYPE_STATIC_FILE, ABSPATH . 'test.txt' );
	}

	/**
	 * Creates static file of active plugin.
	 * 
	 * @return Static_Press_Model_Url_Static_File Static file of active plugin.
	 * @throws \LogicException Case when failed to activate plugin.
	 */
	public static function create_static_file_active_plugin() {
		Plugin_Switcher::activate_plugin();
		return new Static_Press_Model_Url_Static_File( Static_Press_Model_Url::TYPE_STATIC_FILE, trailingslashit( Environment::get_document_root() ), ABSPATH . 'wp-content/plugins/akismet/_inc/akismet.css' );
	}

	/**
	 * Creates static file of active plugin.
	 * 
	 * @return Static_Press_Model_Url_Static_File Static file of active plugin.
	 * @throws \LogicException Case when failed to deactivate plugin.
	 */
	public static function create_static_file_non_active_plugin() {
		Plugin_Switcher::deactivate_plugin();
		return new Static_Press_Model_Url_Static_File( Static_Press_Model_Url::TYPE_STATIC_FILE, trailingslashit( Environment::get_document_root() ), ABSPATH . 'wp-content/plugins/akismet/_inc/akismet.css' );
	}

	/**
	 * Creates static file of active plugin.
	 * 
	 * @return Static_Press_Model_Url_Static_File Static file of active plugin.
	 */
	public static function create_static_file_not_plugin_nor_theme() {
		return self::create_static_file( Static_Press_Model_Url::TYPE_STATIC_FILE, ABSPATH . 'wp-content/uploads/2020/03/test.txt' );
	}

	/**
	 * Creates static file of active plugin.
	 * 
	 * @return Static_Press_Model_Url_Static_File Static file of active plugin.
	 */
	public static function create_content_file_not_plugin_nor_theme() {
		return self::create_static_file( Static_Press_Model_Url::TYPE_CONTENT_FILE, WP_CONTENT_DIR . '/app/uploads/2020/03/test.txt' );
	}

	/**
	 * Creates static file of active plugin.
	 * 
	 * @param string $file_type File type.
	 * @param string $path      Path.
	 * @return Static_Press_Model_Url_Static_File Static file of active plugin.
	 */
	public static function create_static_file( $file_type, $path ) {
		File_System_Operator::create_file_with_directory( $path );
		return new Static_Press_Model_Url_Static_File( $file_type, trailingslashit( Environment::get_document_root() ), $path );
	}
}

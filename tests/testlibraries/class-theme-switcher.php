<?php
/**
 * Class Theme_Switcher
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/infrastructure/class-environment.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/infrastructure/class-file-system-operator.php';
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\models\Static_Press_Model_Url_Static_File;
use static_press\tests\testlibraries\infrastructure\Environment;
use static_press\tests\testlibraries\infrastructure\File_System_Operator;
/**
 * URL Collector.
 */
class Theme_Switcher {
	/**
	 * Theme to not active.
	 * 
	 * @var string
	 */
	public $theme_to_not_activate;
	/**
	 * Theme to active.
	 * 
	 * @var string
	 */
	public $theme_to_activate;
	/**
	 * Theme of parent of activated.
	 * 
	 * @var string
	 */
	public $theme_parent_activated;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wp_version;
		if ( version_compare( $wp_version, '5.3.0', '<' ) ) {
			$this->theme_to_not_activate  = 'twentyfourteen';
			$this->theme_parent_activated = 'twentyfifteen';
			$this->theme_to_activate      = 'twentyfifteen-child';
		} elseif ( version_compare( $wp_version, '6.0.0', '<' ) ) {
			$this->theme_to_not_activate  = 'twentynineteen';
			$this->theme_parent_activated = 'twentytwenty';
			$this->theme_to_activate      = 'twentytwenty-child';
		} else {
			$this->theme_to_not_activate  = 'twentytwenty';
			$this->theme_parent_activated = 'twentytwentytwo';
			$this->theme_to_activate      = 'twentytwentytwo-child';
		}
		$this->switch_theme();
	}

	/**
	 * Creates static file of parent theme of activated.
	 */
	public function create_static_file_theme_parent_activated() {
		$this->deploy_child_theme();
		return new Static_Press_Model_Url_Static_File(
			Static_Press_Model_Url::TYPE_STATIC_FILE,
			trailingslashit( Environment::get_document_root() ),
			ABSPATH . "wp-content/themes/{$this->theme_parent_activated}/style.css"
		);
	}

	/**
	 * Creates static file of active theme.
	 */
	public function create_static_file_active_theme() {
		$this->deploy_child_theme();
		return new Static_Press_Model_Url_Static_File(
			Static_Press_Model_Url::TYPE_STATIC_FILE,
			trailingslashit( Environment::get_document_root() ),
			ABSPATH . "wp-content/themes/{$this->theme_to_activate}/style.css"
		);
	}

	/**
	 * Creates static file of non active theme.
	 */
	public function create_static_file_non_active_theme() {
		return new Static_Press_Model_Url_Static_File(
			Static_Press_Model_Url::TYPE_STATIC_FILE,
			trailingslashit( Environment::get_document_root() ),
			ABSPATH . "wp-content/themes/{$this->theme_to_not_activate}/style.css"
		);
	}

	/**
	 * Switches theme.
	 */
	public function switch_theme() {
		switch_theme( $this->theme_to_activate );
	}

	/**
	 * Deploys child theme.
	 */
	private function deploy_child_theme() {
		File_System_Operator::recurse_copy(
			File_System_Operator::get_path_to_test_resource( $this->theme_to_activate ),
			ABSPATH . 'wp-content/themes/' . $this->theme_to_activate
		);
	}
}

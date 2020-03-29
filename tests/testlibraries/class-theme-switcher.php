<?php
/**
 * Class Theme_Switcher
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

require_once dirname( __FILE__ ) . '/../testlibraries/class-environment.php';
use static_press\includes\Static_Press_Model_Url;
use static_press\includes\Static_Press_Model_Url_Static_File;
use static_press\tests\testlibraries\Environment;

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
	 * Constructor.
	 */
	public function __construct() {
		global $wp_version;
		if ( version_compare( $wp_version, '5.3.0', '<' ) ) {
			$this->theme_to_not_activate = 'twentyfourteen';
			$this->theme_to_activate     = 'twentyfifteen';
		} else {
			$this->theme_to_not_activate = 'twentynineteen';
			$this->theme_to_activate     = 'twentytwenty';
		}
		$this->switch_theme();
	}

	/**
	 * Creates static file of active theme.
	 */
	public function create_static_file_active_theme() {
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
}

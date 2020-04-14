<?php
/**
 * Class Static_Press_Url_Filter
 *
 * @package static_press\includes
 */

namespace static_press\includes;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/models/class-static-press-model-static-file.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-site-dependency.php';
use static_press\includes\models\Static_Press_Model_Static_File;
use static_press\includes\Static_Press_Site_Dependency;
/**
 * URL filter.
 * This class should be instantiated before entering loop since constructor includes loop process.
 */
class Static_Press_Url_Filter {
	/**
	 * Regex.
	 * 
	 * @var string
	 */
	private $regex;
	/**
	 * Constructor.
	 */
	public function __construct() {
		$static_files_filter = Static_Press_Model_Static_File::get_filtered_array_extension();
		$this->regex         = '#[^/]+\.' . implode( '|', array_merge( $static_files_filter, array( 'php' ) ) ) . '$#i';
	}

	/**
	 * Replaces URL to relative URL if URL is absolute URL of dynamic site,
	 * and be end with '/' if URL is not end with extension of static file or '.php'.
	 * 
	 * @param  string $url URL.
	 * @return string Replaced URL.
	 */
	public function replace_url( $url ) {
		$url_dynamic = trailingslashit( Static_Press_Site_Dependency::get_site_url() );
		$url         = trim( str_replace( $url_dynamic, '/', $url ) );
		if ( ! preg_match( $this->regex, $url ) ) {
			$url = trailingslashit( $url );
		}
		return $url;
	}
}

<?php
/**
 * Class Static_Press_Model_Url_Static_File
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Site_Dependency' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-site-dependency.php';
}
use static_press\includes\Static_Press_Site_Dependency;
/**
 * Model URL static file.
 */
class Static_Press_Model_Url_Static_File extends Static_Press_Model_Url {
	/**
	 * Constructor.
	 * 
	 * @param string $static_file Static file.
	 */
	public function __construct( $static_file ) {
		$static_file_url = str_replace(
			trailingslashit( ABSPATH ),
			trailingslashit( Static_Press_Site_Dependency::get_site_url() ),
			$static_file
		);
		parent::__construct(
			null,
			Static_Press_Model_Url::TYPE_STATIC_FILE,
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
		return array(
			Static_Press_Repository::FIELD_NAME_TYPE => $this->get_type(),
			Static_Press_Repository::FIELD_NAME_URL  => $this->get_url(),
			Static_Press_Repository::FIELD_NAME_LAST_MODIFIED => $this->get_last_modified(),
		);
	}
}
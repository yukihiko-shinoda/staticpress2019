<?php
/**
 * Class Static_Press_Model_Url_Other
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Model_Url' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-model-url.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Repository' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-repository.php';
}

use static_press\includes\Static_Press_Model_Url;
use static_press\includes\Static_Press_Repository;

/**
 * Model URL other.
 */
class Static_Press_Model_Url_Other extends Static_Press_Model_Url {
	/**
	 * Constructor.
	 * 
	 * @param string                         $url               URL.
	 * @param Static_Press_Date_Time_Factory $date_time_factory Date.
	 */
	public function __construct( $url, $date_time_factory ) {
		parent::__construct(
			null,
			Static_Press_Model_Url::TYPE_OTHER_PAGE,
			apply_filters( 'StaticPress::get_url', $url ),
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			$date_time_factory->create_date( 'Y-m-d h:i:s' )
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
}

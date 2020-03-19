<?php
/**
 * Class Static_Press_Model_Url_Seo
 *
 * @package static_press\includes
 */

namespace static_press\includes;

/**
 * Model URL SEO.
 */
class Static_Press_Model_Url_Seo extends Static_Press_Model_Url {
	/**
	 * Constructor.
	 * 
	 * @param string            $url               URL.
	 * @param Date_Time_Factory $date_time_factory Date time factory.
	 */
	public function __construct( $url, $date_time_factory ) {
		parent::__construct(
			null,
			Static_Press_Model_Url::TYPE_SEO_FILES,
			$url,
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
		return array(
			Static_Press_Repository::FIELD_NAME_TYPE => $this->get_type(),
			Static_Press_Repository::FIELD_NAME_URL  => $this->get_url(),
			Static_Press_Repository::FIELD_NAME_LAST_MODIFIED => $this->get_last_modified(),
		);
	}
}

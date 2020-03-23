<?php
/**
 * Class Static_Press_Model_Url_Succeed
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
 * Model URL succeed.
 */
class Static_Press_Model_Url_Succeed extends Static_Press_Model_Url {
	/**
	 * Constructor.
	 * 
	 * @param string            $file_type         File type.
	 * @param string            $url               URL.
	 * @param string            $file_dest         File destination.
	 * @param string            $file_date         File date.
	 * @param string            $http_code         HTTP status code.
	 * @param Date_Time_Factory $date_time_factory Date time factory.
	 */
	public function __construct( $file_type, $url, $file_dest, $file_date, $http_code, $date_time_factory ) {
		parent::__construct(
			null,
			$file_type,
			$url,
			null,
			null,
			null,
			null,
			null,
			$file_dest,
			$file_date,
			$http_code,
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
				Static_Press_Repository::FIELD_NAME_FILE_NAME => $this->get_file_name(),
				Static_Press_Repository::FIELD_NAME_FILE_DATE => $this->get_file_date(),
				Static_Press_Repository::FIELD_NAME_LAST_STATUS_CODE => $this->get_last_status_code(),
				Static_Press_Repository::FIELD_NAME_LAST_UPLOAD => $this->get_last_upload(),
			)
		);
	}
}

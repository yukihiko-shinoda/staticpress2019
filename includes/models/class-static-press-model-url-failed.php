<?php
/**
 * Class Static_Press_Model_Url_Failed
 *
 * @package static_press\includes\models
 */

namespace static_press\includes\models;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/models/class-static-press-model-url.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/repositories/class-static-press-repository.php';
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\repositories\Static_Press_Repository;
/**
 * Model URL failed.
 */
class Static_Press_Model_Url_Failed extends Static_Press_Model_Url {
	/**
	 * Constructor.
	 * 
	 * @param string            $file_type         File type.
	 * @param string            $url               URL.
	 * @param Date_Time_Factory $date_time_factory Date time factory.
	 */
	public function __construct( $file_type, $url, $date_time_factory ) {
		parent::__construct(
			null,
			$file_type,
			$url,
			null,
			null,
			null,
			null,
			null,
			'',
			null,
			404,
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
				Static_Press_Repository::FIELD_NAME_LAST_STATUS_CODE => $this->get_last_status_code(),
				Static_Press_Repository::FIELD_NAME_LAST_UPLOAD => $this->get_last_upload(),
			)
		);
	}
}

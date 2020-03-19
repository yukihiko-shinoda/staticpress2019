<?php
/**
 * Class Static_Press_Date_Time_Factory
 *
 * @package static_press\includes
 */

namespace static_press\includes;

/**
 * Date time factory.
 */
class Static_Press_Date_Time_Factory {
	/**
	 * Creates date.
	 * 
	 * @param string $format Format.
	 * @return string Date.
	 */
	public function create_date( $format ) {
		return date( $format );
	}

	/**
	 * Creates GM date.
	 * 
	 * @param string $format Format.
	 * @return string GM date.
	 */
	public function create_gmdate( $format ) {
		return gmdate( $format );
	}
}

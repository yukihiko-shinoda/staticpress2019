<?php
/**
 * Class Static_Press_Transient_Service
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Transient_Manager' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-transient-manager.php';
}
use static_press\includes\Static_Press_Transient_Manager;

/**
 * Transient service.
 */
class Static_Press_Transient_Service {
	/**
	 * Date time factory.
	 * 
	 * @var Static_Press_Date_Time_Factory
	 */
	private $date_time_factory;

	/**
	 * Constructor.
	 * 
	 * @param Static_Press_Date_Time_Factory $date_time_factory Date time factory.
	 */
	public function __construct( $date_time_factory = null ) {
		$this->date_time_factory = $date_time_factory ? $date_time_factory : new Static_Press_Date_Time_Factory();
	}
	/**
	 * Fetches start time.
	 * 
	 * @return string
	 */
	public function fetch_start_time() {
		$transient_manager = new Static_Press_Transient_Manager();
		$param             = $transient_manager->get_transient();
		if ( isset( $param['fetch_start_time'] ) ) {
			return $param['fetch_start_time'];
		} else {
			$start_time                = $this->date_time_factory->create_date( 'Y-m-d h:i:s' );
			$param['fetch_start_time'] = $start_time;
			$transient_manager->set_transient( $param );
			return $start_time;
		}
	}

	/**
	 * Fetches last ID.
	 * 
	 * @param  int|bool $next_id ID to set next.
	 * @return int Cached ID when $next_id is 0 or false, $next_id when $next_id is not 0 nor false.
	 */
	public static function fetch_last_id( $next_id = false ) {
		$transient_manager = new Static_Press_Transient_Manager();
		$param             = $transient_manager->get_transient();
		$last_id           = isset( $param['fetch_last_id'] ) ? intval( $param['fetch_last_id'] ) : 0;
		if ( $next_id ) {
			$last_id                = $next_id;
			$param['fetch_last_id'] = $next_id;
			$transient_manager->set_transient( $param );
		}
		return $last_id;
	}

	/**
	 * Deletes.
	 */
	public static function delete() {
		Static_Press_Transient_Manager::delete_transient();
	}
}

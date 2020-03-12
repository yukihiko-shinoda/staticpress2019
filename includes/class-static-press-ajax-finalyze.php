<?php
/**
 * Static_Press_Ajax_Finalyze
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Ajax_Finalyze' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-ajax_finalyze.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Transient_Manager' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-transient-manager.php';
}

use static_press\includes\Static_Press_Ajax_Processor;
use static_press\includes\Static_Press_Transient_Manager;

/**
 * Class Static_Press_Ajax_Init
 */
class Static_Press_Ajax_Finalyze extends Static_Press_Ajax_Processor {
	/**
	 * Creates 404 error page html static file, 
	 */
	protected function process_ajax_request() {
		$this->create_static_file( Static_Press_Url_Collector::get_site_url() . '404.html' );
		Static_Press_Transient_Manager::delete_transient();

		$result = array( 'result' => true );
		$this->json_output( apply_filters( 'StaticPress::ajax_finalyze', $result ) );
	}
}

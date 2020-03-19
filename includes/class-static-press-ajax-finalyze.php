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
if ( ! class_exists( 'static_press\includes\Static_Press_Response_Processor_200' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-response-processor-200.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Response_Processor_404_Dump' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-response-processor-404-dump.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Transient_Service' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-transient-service.php';
}

use static_press\includes\Static_Press_Ajax_Processor;
use static_press\includes\Static_Press_Response_Processor_200;
use static_press\includes\Static_Press_Response_Processor_404_Dump;
use static_press\includes\Static_Press_Transient_Service;

/**
 * Class Static_Press_Ajax_Init
 */
class Static_Press_Ajax_Finalyze extends Static_Press_Ajax_Processor {
	/**
	 * Creates 404 error page html static file, 
	 */
	protected function process_ajax_request() {
		$static_file_creator = $this->create_static_file_creator_remote(
			new Static_Press_Response_Processor_200(),
			new Static_Press_Response_Processor_404_Dump()
		);
		try {
			$static_file_creator->create( Static_Press_Url_Collector::get_site_url() . '404.html' );
		} catch ( Static_Press_Business_Logic_Exception $exception) {
			// TODO Original specification, although not good...
			// Case when static file does not exist.
		}
		Static_Press_Transient_Service::delete();

		$result = array( 'result' => true );
		$this->json_output( apply_filters( 'StaticPress::ajax_finalyze', $result ) );
	}
}

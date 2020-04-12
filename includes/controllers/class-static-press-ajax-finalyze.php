<?php
/**
 * Static_Press_Ajax_Finalyze
 *
 * @package static_press\includes\controllers
 */

namespace static_press\includes\controllers;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/controllers/class-static-press-ajax-processor.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/exceptions/class-static-press-business-logic-exception.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/repositories/class-static-press-repository-progress.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/response_processors/class-static-press-response-processor-200.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/response_processors/class-static-press-response-processor-404-dump.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-site-dependency.php';
use static_press\includes\controllers\Static_Press_Ajax_Processor;
use static_press\includes\exceptions\Static_Press_Business_Logic_Exception;
use static_press\includes\repositories\Static_Press_Repository_Progress;
use static_press\includes\response_processors\Static_Press_Response_Processor_200;
use static_press\includes\response_processors\Static_Press_Response_Processor_404_Dump;
use static_press\includes\Static_Press_Site_Dependency;

/**
 * Class Static_Press_Ajax_Finalyze
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
			$static_file_creator->create( Static_Press_Site_Dependency::get_site_url() . '404.html' );
		} catch ( Static_Press_Business_Logic_Exception $exception ) {
			// TODO Original specification, although not good...
			// Case when static file does not exist.
		}
		Static_Press_Repository_Progress::delete();

		$result = array( 'result' => true );
		$this->json_output( apply_filters( 'StaticPress::ajax_finalyze', $result ) );
	}
}

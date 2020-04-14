<?php
/**
 * Static_Press_Response_Processor_404
 *
 * @package static_press\includes\response_processors
 */

namespace static_press\includes\response_processors;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/response_processors/class-static-press-response-processor.php';
use static_press\includes\response_processors\Static_Press_Response_Processor;

/**
 * Class Static_Press_Response_Processor_404
 */
class Static_Press_Response_Processor_404 extends Static_Press_Response_Processor {
	/**
	 * Processes.
	 * 
	 * @param array                          $content           Content.
	 * @param Static_Press_Model_Static_File $model_static_file Static file.
	 */
	public function process( $content, $model_static_file ) {
	}
}

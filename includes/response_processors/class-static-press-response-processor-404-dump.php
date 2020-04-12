<?php
/**
 * Static_Press_Response_Processor_404_Dump
 *
 * @package static_press\includes\response_processors
 */

namespace static_press\includes\response_processors;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/response_processors/class-static-press-response-processor-404.php';
use static_press\includes\response_processors\Static_Press_Response_Processor_404;

/**
 * Class Static_Press_Response_Processor_404_Dump
 */
class Static_Press_Response_Processor_404_Dump extends Static_Press_Response_Processor_404 {
	/**
	 * Processes.
	 * 
	 * @param array                          $content           Content.
	 * @param Static_Press_Model_Static_File $model_static_file Static file.
	 */
	public function process( $content, $model_static_file ) {
		parent::process( $model_static_file, $content );
		$this->create_static_file( $model_static_file, $content );
	}
}

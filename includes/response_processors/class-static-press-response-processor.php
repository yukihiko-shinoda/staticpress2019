<?php
/**
 * Static_Press_Response_Processor
 *
 * @package static_press\includes\response_processors
 */

namespace static_press\includes\response_processors;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/infrastructure/class-static-press-file-system-operator.php';
use static_press\includes\infrastructure\Static_Press_File_System_Operator;

/**
 * Class Static_Press_Response_Processor
 */
abstract class Static_Press_Response_Processor {
	/**
	 * Processes.
	 * 
	 * @param array                          $content           Content.
	 * @param Static_Press_Model_Static_File $model_static_file Static file.
	 */
	abstract public function process( $content, $model_static_file );

	/**
	 * Creates static file.
	 * 
	 * @param Static_Press_Model_Static_File $model_static_file Static file.
	 * @param string                         $content       Content.
	 */
	protected function create_static_file( $model_static_file, $content ) {
		$content = apply_filters( 'StaticPress::put_content', $content['body'], $model_static_file->http_code );
		Static_Press_File_System_Operator::make_subdirectories( $model_static_file->file_dest );
		file_put_contents( $model_static_file->file_dest, $content );
		$model_static_file->file_date = date( 'Y-m-d h:i:s', filemtime( $model_static_file->file_dest ) );
	}
}

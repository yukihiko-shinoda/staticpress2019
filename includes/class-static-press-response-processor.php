<?php
/**
 * Static_Press_Response_Processor
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_File_System_Utility' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-file-system-utility.php';
}
use static_press\includes\Static_Press_File_System_Utility;

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
		Static_Press_File_System_Utility::make_subdirectories( $model_static_file->file_dest );
		file_put_contents( $model_static_file->file_dest, $content );
		$model_static_file->file_date = date( 'Y-m-d h:i:s', filemtime( $model_static_file->file_dest ) );
	}
}

<?php
/**
 * Static_Press_Static_File_Creator_Local
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Business_Logic_Exception' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-business-logic-exception.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Static_File_Creator' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-static-file-creator.php';
}
use static_press\includes\Static_Press_Business_Logic_Exception;
use static_press\includes\Static_Press_Static_File_Creator;

/**
 * Class Static_Press_Static_File_Creator_Local
 */
class Static_Press_Static_File_Creator_Local extends Static_Press_Static_File_Creator {
	/**
	 * Gets file.
	 * 
	 * @param Static_Press_Model_Static_File $model_static_file Model of static file.
	 * @throws Static_Press_Business_Logic_Exception When source file doesn't exist.
	 */
	protected function get_file( $model_static_file ) {
		$this->get_static_file( $model_static_file );
	}

	/**
	 * Gets static file.
	 * 
	 * @param Static_Press_Model_Static_File $model_static_file Static file.
	 * @throws Static_Press_Business_Logic_Exception When source file doesn't exist.
	 */
	private function get_static_file( $model_static_file ) {
		$file_source = untrailingslashit( ABSPATH ) . $model_static_file->url;
		if ( '/' !== $model_static_file->dir_sep ) {
			$file_source = str_replace( '/', $model_static_file->dir_sep, $file_source );
		}
		if ( ! is_file( $file_source ) || ! file_exists( $file_source ) ) {
			$this->delete_url( array( $model_static_file->url ) );
			throw new Static_Press_Business_Logic_Exception();
		}
		if ( $file_source != $model_static_file->file_dest && ( ! file_exists( $model_static_file->file_dest ) || filemtime( $file_source ) > filemtime( $model_static_file->file_dest ) ) ) {
			$this->create_static_file( $model_static_file, $file_source );
		}
	}

	/**
	 * Creates static file.
	 * 
	 * @param Static_Press_Model_Static_File $model_static_file Static file.
	 * @param string                         $file_source       File of source.
	 */
	private function create_static_file( $model_static_file, $file_source ) {
		Static_Press_File_System_Utility::make_subdirectories( $model_static_file->file_dest );
		copy( $file_source, $model_static_file->file_dest );
		$model_static_file->file_date = date( 'Y-m-d h:i:s', filemtime( $file_source ) );
	}

	/**
	 * Deletes URL.
	 * 
	 * @param array $urls URLs.
	 */
	private function delete_url( $urls ) {
		foreach ( (array) $urls as $url ) {
			if ( ! isset( $url['url'] ) || ! $url['url'] ) {
				continue;
			}
			$this->repository->delete_url( $url['url'] );
			do_action( 'StaticPress::delete_url', $url );
		}
		return $urls;
	}
}

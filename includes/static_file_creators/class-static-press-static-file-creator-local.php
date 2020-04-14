<?php
/**
 * Static_Press_Static_File_Creator_Local
 *
 * @package static_press\includes\static_file_creators
 */

namespace static_press\includes\static_file_creators;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/exceptions/class-static-press-business-logic-exception.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/infrastructure/class-static-press-document-root-getter.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/infrastructure/class-static-press-file-system-operator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/static_file_creators/class-static-press-static-file-creator.php';
use static_press\includes\exceptions\Static_Press_Business_Logic_Exception;
use static_press\includes\infrastructure\Static_Press_Document_Root_Getter;
use static_press\includes\infrastructure\Static_Press_File_System_Operator;
use static_press\includes\static_file_creators\Static_Press_Static_File_Creator;

/**
 * Class Static_Press_Static_File_Creator_Local
 */
class Static_Press_Static_File_Creator_Local extends Static_Press_Static_File_Creator {
	/**
	 * Document root.
	 * 
	 * @var string
	 */
	private $document_root;

	/**
	 * Constructor.
	 * 
	 * @param string                            $file_type            File type.
	 * @param string                            $dump_directory       Dump direcory.
	 * @param string                            $static_site_url      Static site URL.
	 * @param Static_Press_Repository           $repository           Repository.
	 * @param Static_Press_Date_Time_Factory    $date_time_factory    Date time factory.
	 * @param Static_Press_Document_Root_Getter $document_root_getter Document root getter.
	 */
	public function __construct( $file_type, $dump_directory, $static_site_url, $repository, $date_time_factory, $document_root_getter = null ) {
		$document_root_getter = $document_root_getter ? $document_root_getter : new Static_Press_Document_Root_Getter();
		$this->document_root  = untrailingslashit( $document_root_getter->get() );
		parent::__construct( $file_type, $dump_directory, $static_site_url, $repository, $date_time_factory );
	}

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
		$file_source = $this->document_root . $model_static_file->url;
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
		Static_Press_File_System_Operator::make_subdirectories( $model_static_file->file_dest );
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

<?php
/**
 * Static_Press_Static_File_Creator
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Date_Time_Factory' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-date-time-factory.php';
}

use static_press\includes\Static_Press_Date_Time_Factory;

/**
 * Class Static_Press_Static_File_Creator
 */
abstract class Static_Press_Static_File_Creator {
	/**
	 * File type.
	 * 
	 * @var string
	 */
	private $file_type;
	/**
	 * Directory to dump static files.
	 * 
	 * @var string
	 */
	private $dump_directory;
	/**
	 * Absolute URL of static site.
	 * 
	 * @var string
	 */
	private $static_site_url;
	/**
	 * Repository.
	 * 
	 * @var Static_Press_Repository
	 */
	protected $repository;
	/**
	 * Date time factory instance.
	 * 
	 * @var Static_Press_Date_Time_Factory
	 */
	private $date_time_factory;

	/**
	 * Constructor.
	 * 
	 * @param string                         $file_type         File type.
	 * @param string                         $dump_directory    Dump direcory.
	 * @param string                         $static_site_url   Static site URL.
	 * @param Static_Press_Repository        $repository        Repository.
	 * @param Static_Press_Date_Time_Factory $date_time_factory Date time factory.
	 */
	public function __construct( $file_type, $dump_directory, $static_site_url, $repository, $date_time_factory ) {
		$this->file_type         = $file_type;
		$this->dump_directory    = $dump_directory;
		$this->static_site_url   = $static_site_url;
		$this->repository        = $repository;
		$this->date_time_factory = $date_time_factory ? $date_time_factory : new Static_Press_Date_Time_Factory();
	}

	/**
	 * Creates.
	 * 
	 * @param string $url URL.
	 * @return string File path to created file.
	 * @throws Static_Press_Business_Logic_Exception Unexpected URL type.
	 */
	public function create( $url ) {
		$model_static_file = new Static_Press_Model_Static_File( $url, $this->dump_directory );
		$this->get_file( $model_static_file );
		$model_static_file->do_file_put_action( $this->static_site_url );

		$this->update_url( array( $model_static_file->check_file_existance_and_create_array_url( $this->file_type, $this->date_time_factory )->to_array() ) );

		return $model_static_file->check_file_existance();
	}

	/**
	 * Updates URL.
	 * 
	 * @param  array $urls URLs.
	 */
	protected function update_url( $urls ) {
		$url_updater = new Static_Press_Url_Updater( $this->repository, $this->dump_directory );
		$url_updater->update( $urls );
	}

	/**
	 * Gets file.
	 * 
	 * @param Static_Press_Model_Static_File $model_static_file Model of static file.
	 */
	abstract protected function get_file( $model_static_file );
}

<?php
/**
 * Static_Press_Ajax_Processor
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Date_Time_Factory' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-date-time-factory.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Factory_Static_File_Creator' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-factory-static-file-creator.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Response_Processor_200_Crawl' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-response-processor-200-crawl.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Static_File_Creator_Remote' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-static-file-creator-remote.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Url_Updater' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-url-updater.php';
}
use static_press\includes\Static_Press_Date_Time_Factory;
use static_press\includes\Static_Press_Factory_Static_File_Creator;
use static_press\includes\Static_Press_Response_Processor_200_Crawl;
use static_press\includes\Static_Press_Static_File_Creator_Remote;
use static_press\includes\Static_Press_Url_Updater;

/**
 * Class Static_Press_Ajax_Processor
 */
abstract class Static_Press_Ajax_Processor {
	/**
	 * Absolute URL of static site.
	 * 
	 * @var string
	 */
	protected $static_site_url;
	/**
	 * Directory to dump static files.
	 * 
	 * @var string
	 */
	protected $dump_directory;
	/**
	 * Database access instance.
	 * 
	 * @var Static_Press_Repository
	 */
	protected $repository;
	/**
	 * URL collector instance.
	 * 
	 * @var Static_Press_Url_Collector
	 */
	protected $url_collector;
	/**
	 * Terminator instance.
	 * 
	 * @var Static_Press_Terminator
	 */
	private $terminator;
	/**
	 * Date time factory instance.
	 * 
	 * @var Static_Press_Date_Time_Factory
	 */
	private $date_time_factory;

	/**
	 * Constructor.
	 * 
	 * @param string                         $static_site_url   Absolute URL of static site.
	 * @param string                         $dump_directory    Directory to dump static files.
	 * @param Static_Press_Repository        $repository        Database access instance.
	 * @param Static_Press_Remote_Getter     $remote_getter     Remote getter instance.
	 * @param Static_Press_Terminator        $terminator        Terminator instance.
	 * @param Static_Press_Date_Time_Factory $date_time_factory Date time factory instance.
	 */
	public function __construct( $static_site_url, $dump_directory, $repository, $remote_getter, $terminator = null, $date_time_factory = null ) {
		$this->static_site_url   = $static_site_url;
		$this->dump_directory    = $dump_directory;
		$this->repository        = $repository;
		$this->url_collector     = new Static_Press_Url_Collector( $remote_getter, $date_time_factory );
		$this->terminator        = $terminator ? $terminator : new Static_Press_Terminator();
		$this->date_time_factory = $date_time_factory ? $date_time_factory : new Static_Press_Date_Time_Factory();
	}
	/**
	 * Executes to process ajax request.
	 */
	public function execute() {
		if ( ! is_user_logged_in() ) {
			wp_die( 'Forbidden' );
		}
		if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) {
			define( 'WP_DEBUG_DISPLAY', false );
		}
		$this->process_ajax_request();
	}

	/**
	 * Executes to process ajax request.
	 */
	abstract protected function process_ajax_request();

	/**
	 * Fetches start time.
	 * 
	 * @return string
	 */
	protected function fetch_start_time() {
		$transient_manager = new Static_Press_Transient_Manager();
		$param             = $transient_manager->get_transient();
		if ( isset( $param['fetch_start_time'] ) ) {
			return $param['fetch_start_time'];
		} else {
			$start_time                = $this->date_time_factory->create_date_by_time( 'Y-m-d h:i:s' );
			$param['fetch_start_time'] = $start_time;
			$transient_manager->set_transient( $param );
			return $start_time;
		}
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
	 * Dumps JSON responce.
	 * 
	 * @param array $content Content.
	 */
	protected function json_output( $content ) {
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		echo json_encode( $content );
		$this->terminator->terminate();
	}

	/**
	 * Creates static file creator remote.
	 * 
	 * @param Static_Press_Response_Processor_200 $response_processor_200 Response processor for HTTP status code 200.
	 * @param Static_Press_Response_Processor_404 $response_processor_404 Response processor for HTTP status code 404.
	 * @return Static_Press_Static_File_Creator_Remote Static file creator remote.
	 */
	protected function create_static_file_creator_remote( $response_processor_200, $response_processor_404 ) {
		return new Static_Press_Static_File_Creator_Remote(
			'other_page',
			$this->dump_directory,
			$this->static_site_url,
			$this->repository,
			$this->date_time_factory,
			$response_processor_200,
			$response_processor_404,
			$this->url_collector
		);
	}

	/**
	 * Creates static file creator by factory.
	 * 
	 * @param Static_Press_Model_Url_Fetched $url URL.
	 * @return Static_Press_Static_File_Creator Static file creator.
	 */
	protected function create_static_file_creator_by_factory( $url ) {
		return Static_Press_Factory_Static_File_Creator::create(
			$url->get_type_fetched(),
			$this->dump_directory,
			$this->static_site_url,
			$this->repository,
			$this->date_time_factory,
			$this->url_collector
		);
	}

	/**
	 * Creates response processor 200 crawl.
	 * 
	 * @return Static_Press_Response_Processor_200_Crawl Response processor 200 crawl.
	 */
	protected function create_response_porcessor_200_crawl() {
		return new Static_Press_Response_Processor_200_Crawl( $this->dump_directory, $this->repository, $this->date_time_factory );
	}
}

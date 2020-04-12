<?php
/**
 * Static_Press_Ajax_Processor
 *
 * @package static_press\includes\controllers
 */

namespace static_press\includes\controllers;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/factories/class-static-press-factory-static-file-creator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/infrastructure/class-static-press-document-root-getter.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/models/class-static-press-model-url.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/repositories/class-static-press-repository-progress.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/response_processors/class-static-press-response-processor-200-crawl.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/static_file_creators/class-static-press-static-file-creator-remote.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-date-time-factory.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-terminator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-url-collector.php';
use static_press\includes\factories\Static_Press_Factory_Static_File_Creator;
use static_press\includes\infrastructure\Static_Press_Document_Root_Getter;
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\repositories\Static_Press_Repository_Progress;
use static_press\includes\response_processors\Static_Press_Response_Processor_200_Crawl;
use static_press\includes\static_file_creators\Static_Press_Static_File_Creator_Remote;
use static_press\includes\Static_Press_Date_Time_Factory;
use static_press\includes\Static_Press_Terminator;
use static_press\includes\Static_Press_Url_Collector;
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
	 * Document root getter.
	 * 
	 * @var Static_Press_Document_Root_Getter
	 */
	protected $document_root_getter;

	/**
	 * Constructor.
	 * 
	 * @param string                            $static_site_url      Absolute URL of static site.
	 * @param string                            $dump_directory       Directory to dump static files.
	 * @param Static_Press_Repository           $repository           Database access instance.
	 * @param Static_Press_Remote_Getter        $remote_getter        Remote getter instance.
	 * @param Static_Press_Terminator           $terminator           Terminator instance.
	 * @param Static_Press_Date_Time_Factory    $date_time_factory    Date time factory instance.
	 * @param Static_Press_Document_Root_Getter $document_root_getter Document root getter.
	 */
	public function __construct( $static_site_url, $dump_directory, $repository, $remote_getter, $terminator = null, $date_time_factory = null, $document_root_getter = null ) {
		$this->static_site_url      = $static_site_url;
		$this->dump_directory       = $dump_directory;
		$this->repository           = $repository;
		$this->url_collector        = new Static_Press_Url_Collector( $remote_getter, $date_time_factory, $document_root_getter );
		$this->terminator           = $terminator ? $terminator : new Static_Press_Terminator();
		$this->date_time_factory    = $date_time_factory ? $date_time_factory : new Static_Press_Date_Time_Factory();
		$this->document_root_getter = $document_root_getter ? $document_root_getter : new Static_Press_Document_Root_Getter();
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
		$transient_service = new Static_Press_Repository_Progress( $this->date_time_factory );
		return $transient_service->fetch_start_time();
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
			Static_Press_Model_Url::TYPE_OTHER_PAGE,
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
	 * @throws \LogicException URL has Inbalid file type.
	 */
	protected function create_static_file_creator_by_factory( $url ) {
		return Static_Press_Factory_Static_File_Creator::create(
			$url->get_type_fetched(),
			$this->dump_directory,
			$this->static_site_url,
			$this->repository,
			$this->date_time_factory,
			$this->url_collector,
			$this->document_root_getter
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

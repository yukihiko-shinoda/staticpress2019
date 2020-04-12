<?php
/**
 * Static_Press_Ajax_Fetch
 *
 * @package static_press\includes\controllers
 */

namespace static_press\includes\controllers;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/controllers/class-static-press-ajax-processor.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/exceptions/class-static-press-business-logic-exception.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/models/class-static-press-model-url-fetched.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/repositories/class-static-press-repository-progress.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/response_processors/class-static-press-response-processor-404.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-fetch-result.php';
use static_press\includes\controllers\Static_Press_Ajax_Processor;
use static_press\includes\exceptions\Static_Press_Business_Logic_Exception;
use static_press\includes\models\Static_Press_Model_Url_Fetched;
use static_press\includes\repositories\Static_Press_Repository_Progress;
use static_press\includes\response_processors\Static_Press_Response_Processor_404;
use static_press\includes\Static_Press_Fetch_Result;
/**
 * Class Static_Press_Ajax_Fetch
 */
class Static_Press_Ajax_Fetch extends Static_Press_Ajax_Processor {
	const FETCH_LIMIT        = 5;
	const FETCH_LIMIT_STATIC = 100;

	/**
	 * Fetch result.
	 * 
	 * @var Static_Press_Fetch_Result $fetch_result
	 */
	private $fetch_result;

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
		parent::__construct( $static_site_url, $dump_directory, $repository, $remote_getter, $terminator, $date_time_factory, $document_root_getter );
		$this->fetch_result = new Static_Press_Fetch_Result();
	}

	/**
	 * Fetches URL from database and crate static files.
	 * 
	 * @throws \LogicException Fetched URL has Inbalid file type.
	 */
	protected function process_ajax_request() {
		try {
			$url    = $this->fetch();
			$result = array(
				'result' => true,
				'files'  => $this->fetch_result->result,
				'final'  => ( false === $url ),
			);
		} catch ( Static_Press_Business_Logic_Exception $exception ) {
			$url    = false;
			$result = array(
				'result' => false,
				'final'  => true,
			);
		}
		$this->json_output( apply_filters( 'StaticPress::ajax_fetch', $result, $url ) );
	}

	/**
	 * Fetches.
	 * 
	 * @return Static_Press_Model_Url_Fetched $url URL.
	 * @throws Static_Press_Business_Logic_Exception Case when fail to fetch first URL.
	 * @throws \LogicException Fetched URL has Inbalid file type.
	 */
	private function fetch() {
		$url_first = $this->fetch_url();
		$this->create_static_file( $url_first );

		if ( $url_first->has_multiple_page() ) {
			$this->create_static_file_for_pages( $url_first );
		}

		while ( true ) {
			try {
				$url = $this->fetch_url();
			} catch ( Static_Press_Business_Logic_Exception $exception ) {
				return false;
			}
			$this->create_static_file( $url );
			$limit = $url->is_static_file() ? self::FETCH_LIMIT_STATIC : self::FETCH_LIMIT;
			if ( $this->fetch_result->file_count >= $limit ) {
				return $url;
			}
		}
	}

	/**
	 * Fetches first URL.
	 * 
	 * @param Static_Press_Model_Url_Fetched $url URL.
	 * @throws \LogicException URL has Inbalid file type.
	 */
	private function create_static_file( $url ) {
		$static_file_creator = $this->create_static_file_creator_by_factory( $url );
		try {
			$static_file = $static_file_creator->create( $url->get_url() );
		} catch ( Static_Press_Business_Logic_Exception $exception ) {
			$static_file = false;
		}
		$this->fetch_result->set_fetch_result( $url, $static_file );
	}

	/**
	 * Cretes static file for pages.
	 * 
	 * @param Static_Press_Model_Url_Fetched $url URL.
	 */
	private function create_static_file_for_pages( $url ) {
		$static_file_creator = $this->create_static_file_creator_remote(
			$this->create_response_porcessor_200_crawl(),
			new Static_Press_Response_Processor_404()
		);
		$pages               = $url->get_pages_fetched();
		for ( $page = 2; $page <= $pages; $page++ ) {
			try {
				$page_url = $url->create_page_url( $page );
			} catch ( Static_Press_Business_Logic_Exception $exception ) {
				break;
			}
			try {
				$static_file = $static_file_creator->create( $page_url );
			} catch ( Static_Press_Business_Logic_Exception $exception ) {
				$static_file = false;
			}
			$this->fetch_result->set_page_fetch_result( $url, $page, $page_url, $static_file );
		}
	}

	/**
	 * Fetches URL.
	 * 
	 * @return Static_Press_Model_Url_Fetched Fetched URL when exist in database table, false when not exist in database table.
	 * @throws Static_Press_Business_Logic_Exception Case when fail to fetch URL.
	 */
	private function fetch_url() {
		$result = $this->repository->get_next_url(
			$this->fetch_start_time(),
			Static_Press_Repository_Progress::fetch_last_id()
		);
		if ( is_null( $result ) || is_wp_error( $result ) || ! $result->ID ) {
			Static_Press_Repository_Progress::delete();
			throw new Static_Press_Business_Logic_Exception();
		}
		Static_Press_Repository_Progress::fetch_last_id( $result->ID );
		return new Static_Press_Model_Url_Fetched( $result );
	}
}

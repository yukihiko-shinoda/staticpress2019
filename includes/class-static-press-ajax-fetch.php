<?php
/**
 * Static_Press_Ajax_Fetch
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Ajax_Processor' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-ajax-processor.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Fetch_Result' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-fetch-result.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Model_Url_Fetched' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-model-url-fetched.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Response_Processor_404' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-response-processor-404.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Transient_Service' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-transient-service.php';
}
use static_press\includes\Static_Press_Ajax_Processor;
use static_press\includes\Static_Press_Fetch_Result;
use static_press\includes\Static_Press_Model_Url_Fetched;
use static_press\includes\Static_Press_Response_Processor_404;
use static_press\includes\Static_Press_Transient_Service;
/**
 * Class Static_Press_Ajax_Fetch
 */
class Static_Press_Ajax_Fetch extends Static_Press_Ajax_Processor {
	const FETCH_LIMIT        = 5;
	const FETCH_LIMIT_STATIC = 100;
	/**
	 * Fetches URL from database and crate static files.
	 */
	protected function process_ajax_request() {
		$fetch_result = $this->fetch_first_url();

		while ( $url = $this->fetch_url() ) {
			$static_file_creator = $this->create_static_file_creator_by_factory( $url );
			$fetch_result->set_fetch_result( $url, $static_file_creator->create( $url->get_url() ) );
			$limit = $url->is_static_file() ? self::FETCH_LIMIT_STATIC : self::FETCH_LIMIT;
			if ( $fetch_result->file_count >= $limit ) {
				break;
			}
		}

		$result = array(
			'result' => true,
			'files'  => $fetch_result->result,
			'final'  => ( false === $url ),
		);
		$this->json_output( apply_filters( 'StaticPress::ajax_fetch', $result, $url ) );
	}

	/**
	 * Fetches first URL.
	 */
	private function fetch_first_url() {
		$url = $this->fetch_url();
		if ( ! $url ) {
			$result = array(
				'result' => false,
				'final'  => true,
			);
			$this->json_output( apply_filters( 'StaticPress::ajax_fetch', $result, false ) );
		}

		$static_file_creator = $this->create_static_file_creator_by_factory( $url );
		$fetch_result        = new Static_Press_Fetch_Result();
		$fetch_result->set_fetch_result( $url, $static_file_creator->create( $url->get_url() ) );
		if ( ! $url->has_multiple_page() ) {
			return $fetch_result;
		}
		$static_file_creator = $this->create_static_file_creator_remote(
			$this->create_response_porcessor_200_crawl(),
			new Static_Press_Response_Processor_404()
		);
		$pages               = $url->get_pages_fetched();
		for ( $page = 2; $page <= $pages; $page++ ) {
			$page_url    = untrailingslashit( trim( $url->get_url() ) );
			$static_file = false;
			switch ( $url->get_type_fetched() ) {
				case Static_Press_Model_Url::TYPE_TERM_ARCHIVE:
				case Static_Press_Model_Url::TYPE_AUTHOR_ARCHIVE:
				case Static_Press_Model_Url::TYPE_OTHER_PAGE:
					$page_url    = sprintf( '%s/page/%d', $page_url, $page );
					$static_file = $static_file_creator->create( $page_url );
					break;
				case Static_Press_Model_Url::TYPE_SINGLE:
					$page_url    = sprintf( '%s/%d', $page_url, $page );
					$static_file = $static_file_creator->create( $page_url );
					break;
			}
			if ( ! $static_file ) {
				break;
			}
			$fetch_result->set_page_fetch_result( $url, $page, $page_url, $static_file );
		}
		return $fetch_result;
	}

	/**
	 * Fetches URL.
	 * 
	 * @return Static_Press_Model_Url_Fetched|bool Fetched URL when exist in database table, false when not exist in database table.
	 */
	private function fetch_url() {
		$result = $this->repository->get_next_url(
			$this->fetch_start_time(),
			Static_Press_Transient_Service::fetch_last_id()
		);
		if ( ! is_null( $result ) && ! is_wp_error( $result ) && $result->ID ) {
			Static_Press_Transient_Service::fetch_last_id( $result->ID );
			return new Static_Press_Model_Url_Fetched( $result );
		} else {
			Static_Press_Transient_Service::delete();
			return false;
		}
	}
}

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
if ( ! class_exists( 'static_press\includes\Static_Press_Transient_Manager' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-transient-manager.php';
}
use static_press\includes\Static_Press_Ajax_Processor;
use static_press\includes\Static_Press_Fetch_Result;
use static_press\includes\Static_Press_Model_Url;
use static_press\includes\Static_Press_Transient_Manager;

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
			$limit       = ( Static_Press_Model_Url::TYPE_STATIC_FILE == $url->type ) ? self::FETCH_LIMIT_STATIC : self::FETCH_LIMIT;
			$static_file = $this->create_static_file( $url->url, $url->type, true, true );
			$fetch_result->set_fetch_result( $url, $static_file );
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
			$this->json_output( apply_filters( 'StaticPress::ajax_fetch', $result, $url ) );
		}

		$fetch_result = new Static_Press_Fetch_Result();
		$fetch_result->set_fetch_result( $url, $this->create_static_file( $url->url, $url->type, true, true ) );
		if ( $url->pages <= 1 ) {
			return $fetch_result;
		}
		for ( $page = 2; $page <= $url->pages; $page++ ) {
			$page_url    = untrailingslashit( trim( $url->url ) );
			$static_file = false;
			switch ( $url->type ) {
				case 'term_archive':
				case 'author_archive':
				case 'other_page':
					$page_url    = sprintf( '%s/page/%d', $page_url, $page );
					$static_file = $this->create_static_file( $page_url, 'other_page', false, true );
					break;
				case 'single':
					$page_url    = sprintf( '%s/%d', $page_url, $page );
					$static_file = $this->create_static_file( $page_url, 'other_page', false, true );
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
	 * @return array|bool List of fetched URL when exist in database table, false when not exist in database table.
	 */
	private function fetch_url() {
		$result = $this->repository->get_next_url(
			$this->fetch_start_time(),
			$this->fetch_last_id()
		);
		if ( ! is_null( $result ) && ! is_wp_error( $result ) && $result->ID ) {
			$this->fetch_last_id( $result->ID );
			return $result;
		} else {
			Static_Press_Transient_Manager::delete_transient();
			return false;
		}
	}

	/**
	 * Fetches last ID.
	 * 
	 * @param  int|bool $next_id ID to set next.
	 * @return int Cached ID when $next_id is 0 or false, $next_id when $next_id is not 0 nor false.
	 */
	private function fetch_last_id( $next_id = false ) {
		$transient_manager = new Static_Press_Transient_Manager();
		$param             = $transient_manager->get_transient();
		$last_id           = isset( $param['fetch_last_id'] ) ? intval( $param['fetch_last_id'] ) : 0;
		if ( $next_id ) {
			$last_id                = $next_id;
			$param['fetch_last_id'] = $next_id;
			$transient_manager->set_transient( $param );
		}
		return $last_id;
	}
}

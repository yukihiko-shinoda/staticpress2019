<?php
/**
 * Static_Press_Ajax_Init
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Ajax_Processor' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-ajax_processor.php';
}

use static_press\includes\Static_Press_Ajax_Processor;

/**
 * Class Static_Press_Ajax_Init
 */
class Static_Press_Ajax_Init extends Static_Press_Ajax_Processor {
	/**
	 * List all URL into database table and render JSON responce.
	 */
	protected function process_ajax_request() {
		$this->insert_all_url();
		$all_urls = $this->repository->count_url_per_type( $this->fetch_start_time() );
		$result   =
			! is_wp_error( $all_urls )
			? array(
				'result'     => true,
				'urls_count' => $all_urls,
			)
			: array( 'result' => false );

		$this->json_output( apply_filters( 'StaticPress::ajax_init', $result ) );
	}

	/**
	 * Inserts all URLs.
	 */
	private function insert_all_url() {
		$urls = $this->get_urls();
		$this->update_url( $urls );
	}

	/**
	 * Gets URLs.
	 * 
	 * @return array URLs.
	 */
	private function get_urls() {
		$this->repository->truncate_table();
		return $this->url_collector->collect();
	}
}

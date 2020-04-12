<?php
/**
 * Static_Press_Ajax_Init
 *
 * @package static_press\includes\controllers
 */

namespace static_press\includes\controllers;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/controllers/class-static-press-ajax-processor.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-url-updater.php';
use static_press\includes\controllers\Static_Press_Ajax_Processor;
use static_press\includes\Static_Press_Url_Updater;

/**
 * Class Static_Press_Ajax_Init
 */
class Static_Press_Ajax_Init extends Static_Press_Ajax_Processor {
	/**
	 * List all URL into database table and render JSON responce.
	 */
	protected function process_ajax_request() {
		$this->repository->truncate_table();
		$this->insert_all_url();
		$this->json_output( apply_filters( 'StaticPress::ajax_init', $this->count_url_per_type() ) );
	}

	/**
	 * Inserts all URLs.
	 */
	private function insert_all_url() {
		$url_updater = new Static_Press_Url_Updater( $this->repository, $this->dump_directory, $this->document_root_getter );
		$url_updater->update( $this->url_collector->collect() );
	}

	/**
	 * Counts number of URLs per type.
	 * 
	 * @return array 
	 */
	private function count_url_per_type() {
		$all_urls = $this->repository->count_url_per_type( $this->fetch_start_time() );
		return $this->create_response( $all_urls );
	}

	/**
	 * Creates response.
	 * 
	 * @param array $all_urls All URLs.
	 * @return array Response.
	 */
	private function create_response( $all_urls ) {
		return ! is_wp_error( $all_urls ) ? array(
			'result'     => true,
			'urls_count' => $all_urls,
		) : array( 'result' => false );
	}
}

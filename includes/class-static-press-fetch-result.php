<?php
/**
 * Class Static_Press_Fetch_Result
 *
 * @package static_press\includes
 */

namespace static_press\includes;

/**
 * Fetch result.
 */
class Static_Press_Fetch_Result {
	/**
	 * Result.
	 * 
	 * @var array
	 */
	public $result;
	/**
	 * File count.
	 * 
	 * @var array
	 */
	public $file_count;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->result     = array();
		$this->file_count = 0;
	}

	/**
	 * Sets page fetch result.
	 * 
	 * @param Static_Press_Model_Url_Fetched $url         URL.
	 * @param int                            $page        Page.
	 * @param string                         $page_url    Page URL.
	 * @param string                         $static_file Static file.
	 */
	public function set_page_fetch_result( $url, $page, $page_url, $static_file ) {
		$this->file_count++;
		$this->result[ "{$url->get_id_fetched()}-{$page}" ] = array(
			'ID'     => $url->get_id_fetched(),
			'page'   => $page,
			'type'   => $url->get_type_fetched(),
			'url'    => $page_url,
			'static' => $static_file,
		);
	}

	/**
	 * Sets fetch result.
	 * 
	 * @param Static_Press_Model_Url_Fetched $url         URL.
	 * @param string                         $static_file Static file.
	 */
	public function set_fetch_result( $url, $static_file ) {
		$this->file_count++;
		$this->result[ $url->get_id_fetched() ] = array(
			'ID'     => $url->get_id_fetched(),
			'page'   => 1,
			'type'   => $url->get_type_fetched(),
			'url'    => $url->get_url(),
			'static' => $static_file,
		);
	}
}

<?php
/**
 * Class Static_Press_Url_Updater
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Model_Url' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-model-url.php';
}

use static_press\includes\Static_Press_Model_Url;

/**
 * URL Updater.
 */
class Static_Press_Url_Updater {
	/**
	 * Repository.
	 * 
	 * @var Static_Press_Repository
	 */
	private $repository;
	/**
	 * Directory to dump static files.
	 * 
	 * @var string
	 */
	private $dump_directory;

	/**
	 * Constructor.
	 * 
	 * @param Static_Press_Repository $repository Repository.
	 * @param Static_Press_Repository $dump_directory Directory to dump static files.
	 */
	public function __construct( $repository, $dump_directory ) {
		$this->repository     = $repository;
		$this->dump_directory = $dump_directory;
	}
	/**
	 * Updates URL.
	 * This function is called in 3 situations:
	 * - When initialize
	 * - When find other URL when crawl URL and response body when createing static file of remote page
	 * - When create static file
	 * 
	 * @param  Static_Press_Model_Url[] $urls URLs.
	 */
	public function update( $urls ) {
		foreach ( (array) $urls as $url ) {
			if ( null === $url->get_url() || ! $url->get_url() ) {
				continue;
			}
			$url->judge_to_dump( $this->dump_directory );

			$id = $this->repository->get_id( $url->get_url() );
			if ( $id ) {
				$this->repository->update_url( $id, $url );
			} else {
				$this->repository->insert_url( $url );
			}

			do_action( 'StaticPress::update_url', $url->to_array() );
		}
	}
}

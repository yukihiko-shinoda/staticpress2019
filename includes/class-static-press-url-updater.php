<?php
/**
 * Class Static_Press_Url_Updater
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Business_Logic_Exception' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-business-logic-exception.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Static_FIle_Judger' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-static-file-judger.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Model_Url' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-model-url.php';
}
use static_press\includes\Static_Press_Business_Logic_Exception;
use static_press\includes\Static_Press_Static_FIle_Judger;
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
	 * Document root getter.
	 * 
	 * @var Static_Press_Document_Root_Getter
	 */
	private $document_root_getter;

	/**
	 * Constructor.
	 * 
	 * @param Static_Press_Repository           $repository           Repository.
	 * @param Static_Press_Repository           $dump_directory       Directory to dump static files.
	 * @param Static_Press_Document_Root_Getter $document_root_getter Document root getter.
	 */
	public function __construct( $repository, $dump_directory, $document_root_getter = null ) {
		$this->repository           = $repository;
		$this->dump_directory       = $dump_directory;
		$this->document_root_getter = $document_root_getter ? $document_root_getter : new Static_Press_Document_Root_Getter();
	}
	/**
	 * Updates URL.
	 * This function is called in 3 situations:
	 * - (Insert) When initialize
	 * - (Insert) When find other URL when crawl URL and response body when createing static file of remote page
	 * - (Update) When create static file
	 * 
	 * @param  Static_Press_Model_Url[] $urls URLs.
	 */
	public function update( $urls ) {
		$static_file_judger = new Static_Press_Static_FIle_Judger( $this->dump_directory, $this->document_root_getter );
		foreach ( (array) $urls as $url ) {
			/**
			 * URL.
			 * 
			 * @var Static_Press_Model_Url $url
			 */
			if ( null === $url->get_url() || ! $url->get_url() ) {
				continue;
			}
			try {
				$url->judge_to_dump();
			} catch ( Static_Press_Business_Logic_Exception $exception ) {
				$url->judge_to_dump_for_static_file( $static_file_judger );
			}
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

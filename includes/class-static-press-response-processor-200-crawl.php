<?php
/**
 * Static_Press_Response_Processor_200_Crawl
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Model_Url_Other' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-model-url-other.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Response_Processor_200' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-response-processor-200.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Site_Dependency' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-site-dependency.php';
}
use static_press\includes\Static_Press_Model_Url_Other;
use static_press\includes\Static_Press_Response_Processor_200;
use static_press\includes\Static_Press_Site_Dependency;

/**
 * Class Static_Press_Response_Processor_200_Crawl
 */
class Static_Press_Response_Processor_200_Crawl extends Static_Press_Response_Processor_200 {
	/**
	 * URL updater.
	 * 
	 * @var Static_Press_Url_Updater
	 */
	private $url_updater;
	/**
	 * Repository.
	 * 
	 * @var Static_Press_Repository
	 */
	private $repository;
	/**
	 * Date time factory instance.
	 * 
	 * @var Static_Press_Date_Time_Factory
	 */
	private $date_time_factory;
	/**
	 * Constructor.
	 * 
	 * @param string                         $dump_directory    Dump direcory.
	 * @param Static_Press_Repository        $repository        Repository.
	 * @param Static_Press_Date_Time_Factory $date_time_factory Date time factory.
	 */
	public function __construct( $dump_directory, $repository, $date_time_factory ) {
		$this->url_updater       = new Static_Press_Url_Updater( $repository, $dump_directory );
		$this->repository        = $repository;
		$this->date_time_factory = $date_time_factory;
	}
	/**
	 * Processes.
	 * 
	 * @param array                          $content           Content.
	 * @param Static_Press_Model_Static_File $model_static_file Static file.
	 */
	public function process( $content, $model_static_file ) {
		$this->crawl_url( $model_static_file->url );
		$this->crawl_body( $content['body'] );
		parent::process( $content, $model_static_file );
	}

	/**
	 * Crawls other URL.
	 * 
	 * @param string $url URL.
	 */
	private function crawl_url( $url ) {
		while ( ( $url = dirname( $url ) ) && '/' != $url ) {
			if ( ! $this->has_listed( $url ) ) {
				$this->url_updater->update( array( new Static_Press_Model_Url_Other( $url, $this->date_time_factory ) ) );
			}
		}
	}

	/**
	 * Crawls other URL.
	 * 
	 * @param string $content Content.
	 */
	private function crawl_body( $content ) {
		$pattern = '#href=[\'"](' . preg_quote( Static_Press_Site_Dependency::get_site_url() ) . '[^\'"\?\#]+)[^\'"]*[\'"]#i';
		if ( preg_match_all( $pattern, $content, $matches ) ) {
			$matches = array_unique( $matches[1] );
			foreach ( $matches as $link ) {
				if ( ! $this->has_listed( $link ) ) {
					$this->url_updater->update( array( new Static_Press_Model_Url_Other( $link, $this->date_time_factory ) ) );
				}
			}
		}
		unset( $matches );
	}

	/**
	 * Check whether URL is already list or not.
	 * 
	 * @param  string $url URL.
	 * @return bool
	 */
	private function has_listed( $url ) {
		$url   = apply_filters( 'StaticPress::get_url', $url );
		$count = intval( wp_cache_get( 'StaticPress::' . $url, 'static_press' ) );
		if ( $count > 0 ) {
			return true;
		}

		$count = $this->repository->count_url( $url );
		wp_cache_set( 'StaticPress::' . $url, $count, 'static_press' );
		
		return $count > 0;
	}
}
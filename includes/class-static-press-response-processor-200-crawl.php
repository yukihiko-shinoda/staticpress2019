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
use static_press\includes\Static_Press_Model_Url_Other;
use static_press\includes\Static_Press_Response_Processor_200;

/**
 * Class Static_Press_Response_Processor_200_Crawl
 */
class Static_Press_Response_Processor_200_Crawl extends Static_Press_Response_Processor_200 {
	/**
	 * Directory to dump static files.
	 * 
	 * @var string
	 */
	private $dump_directory;
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
		$this->dump_directory    = $dump_directory;
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
		$this->other_url( $content['body'], $model_static_file->url, $model_static_file->http_code );
		parent::process( $content, $model_static_file );
	}

	/**
	 * Checks other URL.
	 * 
	 * @param string $content Content.
	 * @param string $url     URL.
	 * @return array
	 */
	private function other_url( $content, $url ) {
		$urls = array();

		while ( ( $url = dirname( $url ) ) && '/' != $url ) {
			if ( ! $this->url_exists( $url ) ) {
				$urls[] = new Static_Press_Model_Url_Other( $url, $this->date_time_factory );
			}
		}

		$pattern = '#href=[\'"](' . preg_quote( Static_Press_Url_Collector::get_site_url() ) . '[^\'"\?\#]+)[^\'"]*[\'"]#i';
		if ( preg_match_all( $pattern, $content, $matches ) ) {
			$matches = array_unique( $matches[1] );
			foreach ( $matches as $link ) {
				if ( ! $this->url_exists( $link ) ) {
					$urls[] = new Static_Press_Model_Url_Other( $link, $this->date_time_factory );
				}
			}
		}
		unset( $matches );

		$array_array_url = array();
		foreach ( $urls as $url ) {
			$array_array_url[] = $url->to_array();
		}
		if ( count( $array_array_url ) > 0 ) {
			$this->update_url( $array_array_url );
		}

		return $array_array_url;
	}

	/**
	 * Check whether URL exists or not.
	 * 
	 * @param  string $url URL.
	 * @return bool
	 */
	private function url_exists( $url ) {
		$url   = apply_filters( 'StaticPress::get_url', $url );
		$count = intval( wp_cache_get( 'StaticPress::' . $url, 'static_press' ) );
		if ( $count > 0 ) {
			return true;
		}

		$count = $this->repository->count_url( $url );
		wp_cache_set( 'StaticPress::' . $url, $count, 'static_press' );
		
		return $count > 0;
	}

	/**
	 * Updates URL.
	 * 
	 * @param  array $urls URLs.
	 */
	private function update_url( $urls ) {
		$url_updater = new Static_Press_Url_Updater( $this->repository, $this->dump_directory );
		$url_updater->update( $urls );
	}
}

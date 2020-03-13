<?php
/**
 * Static_Press_Ajax_Processor
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Business_Logic_Exception' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-business-logic-exception.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Date_Time_Factory' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-date-time-factory.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Url_Updater' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-url-updater.php';
}
use static_press\includes\Static_Press_Business_Logic_Exception;
use static_press\includes\Static_Press_Date_Time_Factory;
use static_press\includes\Static_Press_Url_Updater;

/**
 * Class Static_Press_Ajax_Processor
 */
abstract class Static_Press_Ajax_Processor {
	/**
	 * Absolute URL of static site.
	 * 
	 * @var string
	 */
	private $static_site_url;
	/**
	 * Directory to dump static files.
	 * 
	 * @var string
	 */
	private $dump_directory;
	/**
	 * Database access instance.
	 * 
	 * @var Static_Press_Repository
	 */
	protected $repository;
	/**
	 * URL collector instance.
	 * 
	 * @var Static_Press_Url_Collector
	 */
	protected $url_collector;
	/**
	 * Terminator instance.
	 * 
	 * @var Static_Press_Terminator
	 */
	private $terminator;

	/**
	 * Constructor.
	 * 
	 * @param string                         $static_site_url   Absolute URL of static site.
	 * @param string                         $dump_directory    Directory to dump static files.
	 * @param Static_Press_Repository        $repository        Database access instance.
	 * @param Static_Press_Remote_Getter     $remote_getter     Remote getter instance.
	 * @param Static_Press_Terminator        $terminator        Terminator instance.
	 * @param Static_Press_Date_Time_Factory $date_time_factory Date time factory instance.
	 */
	public function __construct( $static_site_url, $dump_directory, $repository, $remote_getter, $terminator = null, $date_time_factory = null ) {
		$this->static_site_url = $static_site_url;
		$this->dump_directory  = $dump_directory;
		$this->repository      = $repository;
		$this->url_collector   = new Static_Press_Url_Collector( $remote_getter, $date_time_factory );
		$this->terminator      = $terminator ? $terminator : new Static_Press_Terminator();
	}
	/**
	 * Executes to process ajax request.
	 */
	public function execute() {
		if ( ! is_user_logged_in() ) {
			wp_die( 'Forbidden' );
		}
		if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) {
			define( 'WP_DEBUG_DISPLAY', false );
		}
		$this->process_ajax_request();
	}

	/**
	 * Executes to process ajax request.
	 */
	abstract protected function process_ajax_request();

	/**
	 * Fetches start time.
	 * 
	 * @return string
	 */
	protected function fetch_start_time() {
		$transient_manager = new Static_Press_Transient_Manager();
		$param             = $transient_manager->get_transient();
		if ( isset( $param['fetch_start_time'] ) ) {
			return $param['fetch_start_time'];
		} else {
			$start_time                = date( 'Y-m-d h:i:s', time() );
			$param['fetch_start_time'] = $start_time;
			$transient_manager->set_transient( $param );
			return $start_time;
		}
	}

	/**
	 * Creates static file.
	 * 
	 * @param  string $url        URL.
	 * @param  string $file_type  File type.
	 * @param  bool   $create_404 Whether create even if HTTP status code is 404 or not.
	 * @param  bool   $crawling   Whether crawl HTML body to check other URL or not.
	 * @return string             Destination of file.
	 */
	protected function create_static_file( $url, $file_type = 'other_page', $create_404 = true, $crawling = false ) {
		$model_static_file = new Static_Press_Model_Static_File( $url, $this->dump_directory );
		switch ( $file_type ) {
			case 'front_page':
			case 'single':
			case 'term_archive':
			case 'author_archive':
			case 'seo_files':
			case 'other_page':
				$this->get_remote_file( $model_static_file, $crawling, $create_404 );
				break;
			case 'static_file':
				try {
					$this->get_static_file( $model_static_file );
				} catch ( Static_Press_Business_Logic_Exception $exception ) {
					return false;
				}
				break;
		}
		$model_static_file->do_file_put_action( $this->static_site_url );

		$this->update_url( array( $model_static_file->check_file_existance_and_create_array_url( $file_type ) ) );

		return $model_static_file->file_dest;
	}

	/**
	 * Gets remote file.
	 * 
	 * @param Static_Press_Model_Static_File $model_static_file Static file.
	 * @param bool                           $crawling   Whether crawl HTML body to check other URL or not.
	 * @param bool                           $create_404 Whether create even if HTTP status code is 404 or not.
	 */
	private function get_remote_file( $model_static_file, $crawling, $create_404 ) {
		$content = $this->url_collector->remote_get( $model_static_file->url );
		if ( ! $content || ! isset( $content['body'] ) ) {
			return;
		}
		if ( 'UTF-8' === get_option( 'blog_charset' ) ) {
			$content['body'] = $this->clean_utf8( $content['body'] );
		}
		$model_static_file->http_code = intval( $content['code'] );
		switch ( $model_static_file->http_code ) {
			case 200:
				if ( $crawling ) {
					$this->other_url( $content['body'], $model_static_file->url, $model_static_file->http_code );
				}
				// No break.
			case 404:
				if ( $create_404 || 200 == $model_static_file->http_code ) {
					$content = apply_filters( 'StaticPress::put_content', $content['body'], $model_static_file->http_code );
					$this->make_subdirectories( $model_static_file->file_dest );
					file_put_contents( $model_static_file->file_dest, $content );
					$model_static_file->file_date = date( 'Y-m-d h:i:s', filemtime( $model_static_file->file_dest ) );
				}
		}
	}

	/**
	 * Makes subdirectries.
	 * 
	 * @param string $file File.
	 */
	private function make_subdirectories( $file ) {
		Static_Press_File_System_Utility::make_subdirectories( $file );
	}

	/**
	 * Gets static file.
	 * 
	 * @param Static_Press_Model_Static_File $model_static_file Static file.
	 * @throws Static_Press_Business_Logic_Exception When source file doesn't exist.
	 */
	private function get_static_file( $model_static_file ) {
		$file_source = untrailingslashit( ABSPATH ) . $model_static_file->url;
		if ( '/' !== $model_static_file->dir_sep ) {
			$file_source = str_replace( '/', $model_static_file->dir_sep, $file_source );
		}
		if ( ! is_file( $file_source ) || ! file_exists( $file_source ) ) {
			$this->delete_url( array( $model_static_file->url ) );
			throw new Static_Press_Business_Logic_Exception();
		}
		if ( $file_source != $model_static_file->file_dest && ( ! file_exists( $model_static_file->file_dest ) || filemtime( $file_source ) > filemtime( $model_static_file->file_dest ) ) ) {
			$model_static_file->file_date = date( 'Y-m-d h:i:s', filemtime( $file_source ) );
			$this->make_subdirectories( $model_static_file->file_dest );
			copy( $file_source, $model_static_file->file_dest );
		}
	}

	/**
	 * Deletes URL.
	 * 
	 * @param array $urls URLs.
	 */
	private function delete_url( $urls ) {
		foreach ( (array) $urls as $url ) {
			if ( ! isset( $url['url'] ) || ! $url['url'] ) {
				continue;
			}
			$this->repository->delete_url( $url['url'] );
			do_action( 'StaticPress::delete_url', $url );
		}
		return $urls;
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
				$urls[] = array(
					'url'           => apply_filters( 'StaticPress::get_url', $url ),
					'last_modified' => date( 'Y-m-d h:i:s' ),
				);
			}
		}

		$pattern = '#href=[\'"](' . preg_quote( Static_Press_Url_Collector::get_site_url() ) . '[^\'"\?\#]+)[^\'"]*[\'"]#i';
		if ( preg_match_all( $pattern, $content, $matches ) ) {
			$matches = array_unique( $matches[1] );
			foreach ( $matches as $link ) {
				if ( ! $this->url_exists( $link ) ) {
					$urls[] = array(
						'url'           => apply_filters( 'StaticPress::get_url', $link ),
						'last_modified' => date( 'Y-m-d h:i:s' ),
					);
				}
			}
		}
		unset( $matches );

		if ( count( $urls ) > 0 ) {
			$this->update_url( $urls );
		}

		return $urls;
	}

	/**
	 * Updates URL.
	 * 
	 * @param  array $urls URLs.
	 */
	protected function update_url( $urls ) {
		$url_updater = new Static_Press_Url_Updater( $this->repository, $this->dump_directory );
		$url_updater->update( $urls );
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
	 * Sometimes the content of a page contains invalid utf8 characters.
	 * This breaks the static publishing process.
	 * In order to prevent this, utf8 content gets cleaned before publishing.
	 * 
	 * @see https://github.com/megumiteam/staticpress/pull/13
	 * @param  string $content Content.
	 * @return string|string[]|null
	 */
	private function clean_utf8( $content ) {
		$regex = <<<'END'
		/
		  (
		    (?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
		    |   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
		    |   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
		    |   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3 
		    ){1,100}                        # ...one or more times
		  )
		| .                                 # anything else
		/x
END;
		return preg_replace( $regex, '$1', $content );
	}

	/**
	 * Dumps JSON responce.
	 * 
	 * @param array $content Content.
	 */
	protected function json_output( $content ) {
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		echo json_encode( $content );
		$this->terminator->terminate();
	}
}

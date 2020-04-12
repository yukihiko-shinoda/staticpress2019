<?php
/**
 * Static_Press_Static_File_Creator_Remote
 *
 * @package static_press\includes\static_file_creators
 */

namespace static_press\includes\static_file_creators;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/static_file_creators/class-static-press-static-file-creator.php';
use static_press\includes\static_file_creators\Static_Press_Static_File_Creator;
/**
 * Class Static_Press_Static_File_Creator_Remote
 */
class Static_Press_Static_File_Creator_Remote extends Static_Press_Static_File_Creator {
	/**
	 * Response processor for HTTP status code 200.
	 * 
	 * @var Static_Press_Response_Processor_200
	 */
	private $response_processor_200;
	/**
	 * Response processor for HTTP status code 404.
	 * 
	 * @var Static_Press_Response_Processor_404
	 */
	private $response_processor_404;
	/**
	 * URL collector instance.
	 * 
	 * @var Static_Press_Url_Collector
	 */
	protected $url_collector;
	/**
	 * Constructor.
	 * 
	 * @param string                              $file_type              File type.
	 * @param string                              $dump_directory         Dump direcory.
	 * @param string                              $static_site_url        Static site URL.
	 * @param Static_Press_Repository             $repository             Repository.
	 * @param Static_Press_Date_Time_Factory      $date_time_factory      Date time factory.
	 * @param Static_Press_Response_Processor_200 $response_processor_200 Response processor for HTTP status code 200.
	 * @param Static_Press_Response_Processor_404 $response_processor_404 Response processor for HTTP status code 404.
	 * @param Static_Press_Url_Collector          $url_collector          URL collector.
	 */
	public function __construct( $file_type, $dump_directory, $static_site_url, $repository, $date_time_factory, $response_processor_200, $response_processor_404, $url_collector ) {
		parent::__construct( $file_type, $dump_directory, $static_site_url, $repository, $date_time_factory );
		$this->response_processor_200 = $response_processor_200;
		$this->response_processor_404 = $response_processor_404;
		$this->url_collector          = $url_collector;
	}

	/**
	 * Gets file.
	 * 
	 * @param Static_Press_Model_Static_File $model_static_file Model of static file.
	 */
	protected function get_file( $model_static_file ) {
		$this->get_remote_file( $model_static_file );
	}

	/**
	 * Gets remote file.
	 * 
	 * @param Static_Press_Model_Static_File $model_static_file Static file.
	 */
	private function get_remote_file( $model_static_file ) {
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
				$this->response_processor_200->process( $content, $model_static_file );
				break;
			case 404:
				$this->response_processor_404->process( $content, $model_static_file );
				break;
		}
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
}

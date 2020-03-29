<?php
/**
 * Class Test_Utility
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

require_once dirname( __FILE__ ) . '/../testlibraries/class-die-exception.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-environment.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-expect-urls-static-files.php';

use LogicException;
use Mockery;
use static_press\includes\Static_Press_Model_Url;
use static_press\includes\Static_Press_Model_Url_Front_Page;
use static_press\includes\Static_Press_Model_Url_Seo;
use static_press\includes\Static_Press_Model_Url_Static_File;
use static_press\tests\testlibraries\Die_Exception;
use static_press\tests\testlibraries\Environment;
use static_press\tests\testlibraries\Expect_Urls_Static_Files;

/**
 * URL Collector.
 */
class Test_Utility {
	const DATE_FOR_TEST    = '2019-12-23 12:34:56';
	const OUTPUT_DIRECTORY = '/tmp/static/';
	/**
	 * Sets up for testing seo_url().
	 * 
	 * @param string $url URL.
	 */
	public static function set_up_seo_url( $url ) {
		$remote_getter_mock = Mockery::mock( 'alias:Remote_Getter_Mock' );
		$remote_getter_mock->shouldReceive( 'remote_get' )
		->with( $url . 'robots.txt' )
		->andReturn( self::create_response( '/robots.txt', 'robots.txt' ) );
		$remote_getter_mock->shouldReceive( 'remote_get' )
		->with( $url . 'sitemap.xml' )
		->andReturn( self::create_response( '/sitemap.xml', 'sitemap.xml' ) );
		$remote_getter_mock->shouldReceive( 'remote_get' )
		->with( $url . 'sitemap-misc.xml' )
		->andReturn( self::create_response( '/sitemap-misc.xml', 'sitemap-misc.xml' ) );
		$remote_getter_mock->shouldReceive( 'remote_get' )
		->with( $url . 'sitemap-tax-category.xml' )
		->andReturn( self::create_response( '/sitemap-tax-category.xml', 'sitemap-tax-category.xml' ) );
		$remote_getter_mock->shouldReceive( 'remote_get' )
		->with( $url . 'sitemap-pt-post-2020-02.xml' )
		->andReturn( self::create_response( '/sitemap-pt-post-2020-02.xml', 'sitemap-pt-post-2020-02.xml' ) );
		return $remote_getter_mock;
	}

	/**
	 * Creates response.
	 * 
	 * @param string $url         URL.
	 * @param string $file_name   File name.
	 * @param int    $status_code HTTP status code.
	 * @return array Responce.
	 */
	public static function create_response( $url, $file_name, $status_code = 200 ) {
		$body        = self::get_test_resource_content( $file_name );
		$header_data = array(
			'content-encoding' => 'gzip',
			'age'              => '354468',
			'cache-control'    => 'max-age=604800',
			'content-type'     => 'text/html; charset=UTF-8',
			'date'             => 'Tue, 18 Feb 2020 04:21:05 GMT',
			'etag'             => '3147526947+ident+gzip',
			'expires'          => 'Tue, 25 Feb 2020 04:21:05 GMT',
			'last-modified'    => 'Thu, 17 Oct 2019 07:18:26 GMT',
			'server'           => 'ECS (sjc/4E74)',
			'vary'             => 'Accept-Encoding',
			'x-cache'          => 'HIT',
			'content-length'   => '648',
		);
		$responce    = array(
			'body'     => $body,
			'response' => array(
				'code'    => $status_code,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
		global $wp_version;
		if ( version_compare( $wp_version, '4.6.0', '<' ) ) {
			$responce['headers'] = $header_data;
			return $responce;
		}
		$requests_response                   = new \Requests_Response();
		$requests_response->headers          = new \Requests_Response_Headers( $header_data );
		$requests_response->body             = $body;
		$requests_response->status_code      = $status_code;
		$requests_response->protocol_version = 1.1;
		$requests_response->success          = true;
		$requests_response->url              = 'http://example.org' . $url;
		$responce['http_response']           = new \WP_HTTP_Requests_Response( $requests_response, null );
		$responce['headers']                 = new \Requests_Utility_CaseInsensitiveDictionary( $header_data );
		return $responce;
	}

	/**
	 * Gets test resource content.
	 * 
	 * @param string $file_name Related path based on testresources directory not start with '/'.
	 * @return string Content.
	 */
	public static function get_test_resource_content( $file_name ) {
		return file_get_contents( dirname( __FILE__ ) . '/../testresources/' . $file_name );
	}

	/**
	 * Copies test resource content.
	 * 
	 * @param string $file_name   Related path based on testresources directory not start with '/'.
	 * @param string $target_path Target path.
	 * @return string Content.
	 */
	public static function copy_test_resource( $file_name, $target_path ) {
		return copy( dirname( __FILE__ ) . '/../testresources/' . $file_name, $target_path );
	}

	/**
	 * Gets expect URLs of front_page_url().
	 */
	public static function get_expect_urls_front_page() {
		return array(
			new Static_Press_Model_Url_Front_Page( self::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' ) ),
		);
	}

	/**
	 * Gets expect URLs.
	 * 
	 * @return Static_Press_Model_Url_Static_File[] Array of model URL of static file.
	 * @throws \LogicException When fail to stat any file.
	 */
	public static function get_expect_urls_static_files() {
		/**
		 * To convert E_WARNING of filemtime(): stat failed to LogicException.
		 * 
		 * @see https://stackoverflow.com/questions/1241728/can-i-try-catch-a-warning/1241751#1241751
		 */
		set_error_handler(
			function( $errno, $errstr, $errfile, $errline, $errcontext ) {
				// error was suppressed with the @-operator.
				if ( 0 === error_reporting() ) {
					return false;
				}
				throw new \LogicException( $errstr, $errno );
			}
		);
		$expect                = array();
		$array_logic_exception = array();
		foreach ( Expect_Urls_Static_Files::EXPECT_URLS as $expect_url ) {
			try {
				$expect[] = new Static_Press_Model_Url_Static_File( Static_Press_Model_Url::TYPE_STATIC_FILE, ABSPATH, ABSPATH . ltrim( $expect_url, '/' ) );
			} catch ( \LogicException $exception ) {
				$array_logic_exception[] = $exception;
			}
		}
		restore_error_handler();
		if ( ! empty( $array_logic_exception ) ) {
			$message = "filemtime(): stat failed\n";
			foreach ( $array_logic_exception as $logic_exception ) {
				$message .= "{$logic_exception->getMessage()}\n";
			}
			throw new \LogicException( $message );
		}
		return $expect;
	}

	/**
	 * Asserts URLs.
	 * 
	 * @param PHPUnit_Framework_TestCase $test_case Test case.
	 * @param Static_Press_Model_Url[]   $expect    Expect URLs.
	 * @param Static_Press_Model_Url[]   $actual    Actual URLs.
	 */
	public static function assert_array_model_url( $test_case, $expect, $actual ) {
		$length_expect = count( $expect );
		$length_actual = count( $actual );
		$test_case->assertEquals(
			$length_expect,
			$length_actual,
			"Failed asserting that {$length_actual} matches expected {$length_expect}."
		);
		for ( $index = 0; $index < $length_expect; $index ++ ) {
			$expect_url = $expect[ $index ];
			$actual_url = $actual[ $index ];
			$test_case->assertEquals( $expect_url, $actual_url );
		}
	}

	/**
	 * Asserts URLs.
	 * 
	 * @param PHPUnit_Framework_TestCase $test_case Test case.
	 * @param array                      $expect    Expect URLs.
	 * @param array                      $actual    Actual URLs.
	 */
	public static function assert_urls( $test_case, $expect, $actual ) {
		$length_expect = count( $expect );
		$length_actual = count( $actual );
		$test_case->assertEquals(
			$length_expect,
			$length_actual,
			"Failed asserting that {$length_actual} matches expected {$length_expect}. URL list:\n" . self::urls_to_string( $actual )
		);
		for ( $index = 0; $index < $length_expect; $index ++ ) {
			$expect_url = $expect[ $index ];
			$actual_url = $actual[ $index ];
			$test_case->assertEquals(
				array_key_exists( 'last_modified', $expect_url ),
				array_key_exists( 'last_modified', $actual_url ),
				'Existance of last_modified is not same. Index = ' . $index
			);
			if ( array_key_exists( 'last_modified', $actual_url ) ) {
				if ( is_null( $actual_url['last_modified'] ) ) {
					$test_case->assertNull( $actual_url['last_modified'] );
				} else {
					$test_case->assertRegExp(
						'/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/i',
						$actual_url['last_modified'],
						'$actual_url[\last_modified\'] is not mutch regex \'/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/i\'. Index = ' . $index
					);
				}
			}
			unset( $expect_url['last_modified'] );
			unset( $actual_url['last_modified'] );
			$test_case->assertEquals( $expect_url, $actual_url );
		}
	}

	/**
	 * Converts urls to string.
	 * 
	 * @param array $urls URLs.
	 * @return string Converted URLs.
	 */
	private static function urls_to_string( $urls ) {
		$string = '';
		foreach ( $urls as $url ) {
			$string .= "{$url['url']}\n";
		}
		return $string;
	}

	/**
	 * Gets expect URLs of seo_url().
	 * 
	 * @return Static_Press_Model_Url_Seo[] Array of model URL of SEO.
	 */
	public static function get_expect_urls_seo() {
		$date_time_factory = self::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s', self::DATE_FOR_TEST );
		return array(
			new Static_Press_Model_Url_Seo( '/robots.txt', $date_time_factory ),
			new Static_Press_Model_Url_Seo( '/sitemap.xml', $date_time_factory ),
			new Static_Press_Model_Url_Seo( '/sitemap-misc.xml', $date_time_factory ),
			new Static_Press_Model_Url_Seo( '/sitemap-tax-category.xml', $date_time_factory ),
			new Static_Press_Model_Url_Seo( '/sitemap-pt-post-2020-02.xml', $date_time_factory ),
		);
	}

	/**
	 * Creates mock for Terminator to prevent to call die().
	 * 
	 * @return MockInterface Mock interface.
	 */
	public static function create_terminator_mock() {
		$terminator_mock = Mockery::mock( 'alias:Terminator_Mock' );
		$terminator_mock->shouldReceive( 'terminate' )->andThrow( new Die_Exception( 'Dead!' ) );
		return $terminator_mock;
	}

	/**
	 * Creates mock for Remote Getter to prevent to call wp_remote_get since web server is not running in PHPUnit environment.
	 * 
	 * @return MockInterface Mock interface.
	 */
	public static function create_remote_getter_mock() {
		$remote_getter_mock = Mockery::mock( 'alias:Remote_Getter_Mock' );
		$remote_getter_mock->shouldReceive( 'remote_get' )->andReturn( self::create_response( '/', 'index-example.html' ) );
		return $remote_getter_mock;
	}

	/**
	 * Creates mock for Date time factory to fix date time.
	 * 
	 * @param string $function_name Function name.
	 * @param string $parameter     Parameter.
	 * @param mixed  $return_value  Return value.
	 */
	public static function create_date_time_factory_mock( $function_name, $parameter, $return_value = self::DATE_FOR_TEST ) {
		$date_time_factory_mock = Mockery::mock( 'alias:Date_Time_Factory_Mock' );
		$date_time_factory_mock->shouldReceive( $function_name )
		->with( $parameter )
		->andReturn( $return_value );
		return $date_time_factory_mock;
	}

	/**
	 * Creates mock for Date time factory to fix date time.
	 * 
	 * @param string $return_value Return value.
	 */
	public static function create_docuemnt_root_getter_mock( $return_value = null ) {
		$return_value              = $return_value ? $return_value : Environment::get_document_root();
		$document_root_getter_mock = Mockery::mock( 'alias:Document_Root_Getter_Mock' );
		$document_root_getter_mock->shouldReceive( 'get' )->andReturn( $return_value );
		return $document_root_getter_mock;
	}

	/**
	 * Creates static file of readme.
	 * 
	 * @return Static_Press_Model_Url_Static_File Static file of readme.
	 */
	public static function create_static_file_readme() {
		return self::create_static_file( Static_Press_Model_Url::TYPE_STATIC_FILE, ABSPATH . 'readme.txt' );
	}

	/**
	 * Creates static file of not exist.
	 * 
	 * @return Static_Press_Model_Url_Static_File Static file of not exist.
	 */
	public static function create_static_file_not_exist() {
		$path = ABSPATH . 'test.png';
		$url  = self::create_static_file( Static_Press_Model_Url::TYPE_STATIC_FILE, $path );
		unlink( $path );
		return $url;
	}

	/**
	 * Creates static file of not updated after last dump.
	 * 
	 * @return Static_Press_Model_Url_Static_File Static file of not updated after last dump.
	 */
	public static function create_static_file_not_updated() {
		self::create_file_with_directory( self::OUTPUT_DIRECTORY . Environment::DIRECTORY_NAME_WORD_PRESS . '/test.txt' );
		return self::create_static_file( Static_Press_Model_Url::TYPE_STATIC_FILE, ABSPATH . 'test.txt' );
	}

	/**
	 * Creates static file of active plugin.
	 * 
	 * @return Static_Press_Model_Url_Static_File Static file of active plugin.
	 * @throws \LogicException Case when failed to activate plugin.
	 */
	public static function create_static_file_active_plugin() {
		self::activate_plugin();
		return new Static_Press_Model_Url_Static_File( Static_Press_Model_Url::TYPE_STATIC_FILE, trailingslashit( Environment::get_document_root() ), ABSPATH . 'wp-content/plugins/akismet/_inc/akismet.css' );
	}

	/**
	 * Creates static file of active plugin.
	 * 
	 * @return Static_Press_Model_Url_Static_File Static file of active plugin.
	 * @throws \LogicException Case when failed to deactivate plugin.
	 */
	public static function create_static_file_non_active_plugin() {
		self::deactivate_plugin();
		return new Static_Press_Model_Url_Static_File( Static_Press_Model_Url::TYPE_STATIC_FILE, trailingslashit( Environment::get_document_root() ), ABSPATH . 'wp-content/plugins/akismet/_inc/akismet.css' );
	}

	/**
	 * Activates plugin.
	 * 
	 * @throws \LogicException Case when failed to activate plugin.
	 */
	public static function activate_plugin() {
		$result = activate_plugin( 'akismet/akismet.php' );
		if ( null !== $result ) {
			var_dump( $result );
			throw new \LogicException( 'Failed to activate plugin!' );
		}
	}

	/**
	 * Activates plugin.
	 * 
	 * @throws \LogicException Case when failed to deactivate plugin.
	 */
	public static function deactivate_plugin() {
		$result = deactivate_plugins( array( 'akismet/akismet.php' ) );
		if ( null !== $result ) {
			var_dump( $result );
			throw new \LogicException( 'Failed to deactivate plugin!' );
		}
	}

	/**
	 * Creates static file of active plugin.
	 * 
	 * @return Static_Press_Model_Url_Static_File Static file of active plugin.
	 */
	public static function create_static_file_not_plugin_nor_theme() {
		return self::create_static_file( Static_Press_Model_Url::TYPE_STATIC_FILE, ABSPATH . 'wp-content/uploads/2020/03/test.txt' );
	}

	/**
	 * Creates static file of active plugin.
	 * 
	 * @return Static_Press_Model_Url_Static_File Static file of active plugin.
	 */
	public static function create_content_file_not_plugin_nor_theme() {
		return self::create_static_file( Static_Press_Model_Url::TYPE_CONTENT_FILE, WP_CONTENT_DIR . '/app/uploads/2020/03/test.txt' );
	}

	/**
	 * Creates static file of active plugin.
	 * 
	 * @param string $file_type File type.
	 * @param string $path      Path.
	 * @return Static_Press_Model_Url_Static_File Static file of active plugin.
	 */
	public static function create_static_file( $file_type, $path ) {
		self::create_file_with_directory( $path );
		return new Static_Press_Model_Url_Static_File( $file_type, trailingslashit( Environment::get_document_root() ), $path );
	}

	/**
	 * Creates static file of active plugin.
	 * 
	 * @param string $path           Path.
	 */
	public static function create_file_with_directory( $path ) {
		$directory = dirname( $path );
		if ( ! file_exists( $directory ) ) {
			mkdir( $directory, 0777, true );
		}
		file_put_contents( $path, '' );
	}

	/**
	 * PHP delete function that deals with directories recursively.
	 *
	 * @see https://paulund.co.uk/php-delete-directory-and-files-in-directory
	 *
	 * @param string $target Example: '/path/for/the/directory/' .
	 */
	public static function delete_files( $target = self::OUTPUT_DIRECTORY ) {
		if ( is_dir( $target ) ) {
			$files = glob( $target . '*', GLOB_MARK ); // GLOB_MARK adds a slash to directories returned.
			foreach ( $files as $file ) {
				self::delete_files( $file );
			}
			rmdir( $target );
		} elseif ( is_file( $target ) ) {
			unlink( $target );
		}
	}

	/**
	 * Gets array file in output directory.
	 */
	public static function get_array_file_in_output_directory() {
		return array_filter( self::rglob( self::OUTPUT_DIRECTORY . '*' ), 'is_file' );
	}

	/**
	 * Glob recursive.
	 * 
	 * @see https://stackoverflow.com/questions/17160696/php-glob-scan-in-subfolders-for-a-file/17161106#17161106
	 * @param string  $pattern Pattern.
	 * @param integer $flags   Flags.
	 * @return string[] Files.
	 */
	private static function rglob( $pattern, $flags = 0 ) {
		$files = glob( $pattern, $flags ); 
		foreach ( glob( dirname( $pattern ) . '/*', GLOB_ONLYDIR | GLOB_NOSORT ) as $dir ) {
			$files = array_merge( $files, self::rglob( $dir . '/' . basename( $pattern ), $flags ) );
		}
		return $files;
	}
}

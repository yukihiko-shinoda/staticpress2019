<?php
/**
 * Class Mock_Creator
 *
 * @package static_press\tests\testlibraries\creators
 */

namespace static_press\tests\testlibraries\creators;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/exceptions/class-die-exception.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/infrastructure/class-environment.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/infrastructure/class-file-system-operator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-error-handler.php';

use Mockery;
use static_press\tests\testlibraries\exceptions\Die_Exception;
use static_press\tests\testlibraries\infrastructure\Environment;
use static_press\tests\testlibraries\infrastructure\File_System_Operator;
use static_press\tests\testlibraries\Error_Handler;

/**
 * Mock creator.
 */
class Mock_Creator {

	const DATE_FOR_TEST = '2019-12-23 12:34:56';
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
	 * Creates mock for Remote Getter to prevent to call wp_remote_get since web server is not running in PHPUnit environment.
	 *
	 * @param integer $status_code Status code.
	 * @return MockInterface Mock interface.
	 */
	public static function create_remote_getter_mock( $status_code = 200 ) {
		$remote_getter_mock = Mockery::mock( 'alias:Remote_Getter_Mock' );
		$remote_getter_mock->shouldReceive( 'remote_get' )->andReturn( self::create_response( '/', 'index-example.html', $status_code ) );
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
		$body        = File_System_Operator::get_test_resource_content( $file_name );
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
		$error_handler = new Error_Handler();
		// Due to trunk of WordPress Core's bug causing following error:
		// Deprecated: The PSR-0 `Requests_...` class names in the Request library are deprecated.
		// Switch to the PSR-4 `WpOrg\Requests\...` class names at your earliest convenience.
		// in /usr/src/wordpress/wp-includes/Requests/src/Autoload.php on line 171.
		set_error_handler( array( $error_handler, 'ignore' ) );
		$requests_response                   = new \Requests_Response();
		restore_error_handler();
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
	 * Creates mock for Terminator to prevent to call die().
	 *
	 * @param array|null $json             Capture of JSON.
	 * @param int|null   $http_status_code Capture of HTTP status code.
	 * @return MockInterface Mock interface.
	 */
	public static function create_terminator_mock( &$json = null, &$http_status_code = null ) {
		$terminator_mock = Mockery::mock( 'alias:Terminator_Mock' );
		$terminator_mock->shouldReceive( 'terminate' )
			->with( Mockery::capture( $json ), Mockery::capture( $http_status_code ) )
			->andThrow( new Die_Exception( 'Dead!' ) );
		return $terminator_mock;
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
}

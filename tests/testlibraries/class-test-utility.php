<?php
/**
 * Class Test_Utility
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

require_once dirname( __FILE__ ) . '/../testlibraries/class-expect-urls-static-files.php';
use Mockery;
use static_press\tests\testlibraries\Expect_Urls_Static_Files;

/**
 * URL Collector.
 */
class Test_Utility {
	/**
	 * Sets up for testing seo_url().
	 */
	public static function set_up_seo_url() {
		$remote_getter_mock = Mockery::mock( 'alias:Remote_Getter_Mock' );
		$remote_getter_mock->shouldReceive( 'remote_get' )
		->with( 'http://example.org/robots.txt' )
		->andReturn( self::create_response( '/robots.txt', 'robots.txt' ) );
		$remote_getter_mock->shouldReceive( 'remote_get' )
		->with( 'http://example.org/sitemap.xml' )
		->andReturn( self::create_response( '/sitemap.xml', 'sitemap.xml' ) );
		$remote_getter_mock->shouldReceive( 'remote_get' )
		->with( 'http://example.org/sitemap-misc.xml' )
		->andReturn( self::create_response( '/sitemap-misc.xml', 'sitemap-misc.xml' ) );
		$remote_getter_mock->shouldReceive( 'remote_get' )
		->with( 'http://example.org/sitemap-tax-category.xml' )
		->andReturn( self::create_response( '/sitemap-tax-category.xml', 'sitemap-tax-category.xml' ) );
		$remote_getter_mock->shouldReceive( 'remote_get' )
		->with( 'http://example.org/sitemap-pt-post-2020-02.xml' )
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
	 * Gets expect URLs of front_page_url().
	 * 
	 * @param string $last_modified Last modified time.
	 */
	public static function get_expect_urls_front_page( $last_modified ) {
		return array(
			array(
				'type'          => 'front_page',
				'url'           => '/',
				'last_modified' => $last_modified,
			),
		);
	}

	/**
	 * Gets expect URLs.
	 * 
	 * @param string $last_modified Last modified time.
	 */
	public static function get_expect_urls_static_files( $last_modified ) {
		$expect = array();
		foreach ( Expect_Urls_Static_Files::EXPECT_URLS as $expect_url ) {
			$expect[] = array(
				'type'          => 'static_file',
				'url'           => $expect_url,
				'last_modified' => $last_modified,
			);
		}
		return $expect;
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
	 * @param string $last_modified Last modified.
	 */
	public static function get_expect_urls_seo( $last_modified ) {
		return array(
			array(
				'type'          => 'seo_files',
				'url'           => '/robots.txt',
				'last_modified' => $last_modified,
			),
			array(
				'type'          => 'seo_files',
				'url'           => '/sitemap.xml',
				'last_modified' => $last_modified,
			),
			array(
				'type'          => 'seo_files',
				'url'           => '/sitemap-misc.xml',
				'last_modified' => $last_modified,
			),
			array(
				'type'          => 'seo_files',
				'url'           => '/sitemap-tax-category.xml',
				'last_modified' => $last_modified,
			),
			array(
				'type'          => 'seo_files',
				'url'           => '/sitemap-pt-post-2020-02.xml',
				'last_modified' => $last_modified,
			),
		);
	}

	/**
	 * Creates mock for Terminator to prevent to call die().
	 * 
	 * @return MockInterface Mock interface.
	 */
	public static function create_terminator_mock() {
		$terminator_mock = Mockery::mock( 'alias:Terminator_Mock' );
		$terminator_mock->shouldReceive( 'terminate' )->andThrow( new \Exception( 'Dead!' ) );
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
}

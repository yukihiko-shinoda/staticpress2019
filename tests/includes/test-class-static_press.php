<?php
/**
 * Class StaticPressTest
 *
 * @package staticpress\tests\includes
 */

namespace staticpress\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-expect-url.php';
use staticpress\tests\includes\Static_Press_Test;

const DATE_FOR_TEST = '2019-12-23 12:34:56';
const TIME_FOR_TEST = '12:34:56';
/**
 * Override date() in current namespace for testing
 *
 * @return string
 */
function date() {
	return DATE_FOR_TEST;
}

/**
 * Override time() in current namespace for testing
 *
 * @return int
 */
function time() {
	return strtotime( TIME_FOR_TEST );
}

/**
 * Override wp_remote_get() in current namespace for testing
 *
 * @param string $url  URL to retrieve.
 * @param array  $args Optional. Request arguments. Default empty array.
 * @return WP_Error|array The response or WP_Error on failure.
 */
function wp_remote_get( $url, $args = array() ) {
	return Static_Press_Test::$wordpress_mock->wp_remote_get( $url, $args );
}

namespace staticpress\tests\includes;

use const staticpress\includes\DATE_FOR_TEST;
use staticpress\includes\static_press;
use staticpress\tests\testlibraries\Expect_Url;
use ReflectionException;
use Mockery;

/**
 * StaticPress test case.
 *
 * @noinspection PhpUndefinedClassInspection
 */
class Static_Press_Test extends \WP_UnitTestCase {
	const OUTPUT_DIRECTORY = '/tmp/static';
	/**
	 * For WordPress
	 * 
	 * @var \Mockery\MockInterface
	 */
	public static $wordpress_mock;

	/**
	 * Sets administrator as current user.
	 *
	 * @see https://wordpress.stackexchange.com/a/207363
	 */
	public function tearDown() {
		self::delete_files( self::OUTPUT_DIRECTORY . '/' );
		self::$wordpress_mock = null;
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * PHP delete function that deals with directories recursively.
	 *
	 * @see https://paulund.co.uk/php-delete-directory-and-files-in-directory
	 *
	 * @param string $target Example: '/path/for/the/directory/' .
	 */
	public static function delete_files( $target ) {
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
	 * Function url_table() should return prefix for WordPress tables + 'urls'.
	 */
	public function test_url_table() {
		$this->assertEquals( 'wptests_urls', static_press::url_table() );
	}

	/**
	 * Test steps for replace_url().
	 *
	 * @dataProvider provider_replace_url
	 *
	 * @param string $url argument.
	 * @param string $expect Expect return value.
	 */
	public function test_replace_url( $url, $expect ) {
		$static_press = new static_press( 'staticpress' );
		$this->assertEquals( $expect, $static_press->replace_url( $url ) );
	}

	/**
	 * Function replace_url() should return relative URL when same host.
	 * Function replace_url() should return absolute URL when different host.
	 * Function replace_url() should return URL end with '/' when no extension is set.
	 * Function replace_url() should return URL end without '/' when extension is registered.
	 * Function replace_url() should return URL end with '/' when extension is not registered.
	 */
	public function provider_replace_url() {
		return array(
			array( '', '/' ),
			array( 'http://example.org/', '/' ),
			array( 'http://example.org/', '/' ),
			array( 'http://example.org/test', '/test/' ),
			array( 'http://example.org/test.php', '/test.php' ),
			array( 'http://example.org/test.xlsx', '/test.xlsx/' ),  // Maybe, not intended.
		);
	}

	/**
	 * Test steps for create_static_file().
	 *
	 * @dataProvider provider_create_static_file
	 *
	 * @param string $url         Argument.
	 * @param string $file_type   Argument.
	 * @param string $expect      Expect return value.
	 * @param string $expect_file Expect file.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_create_static_file( $url, $file_type, $expect, $expect_file ) {
		self::$wordpress_mock = Mockery::mock( 'alias:WordPress_Mock' );
		self::$wordpress_mock->shouldReceive( 'wp_remote_get' )->andReturn( $this->create_response( $url ) );
		$static_press = new static_press( 'staticpress', '/', self::OUTPUT_DIRECTORY );
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'create_static_file' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $static_press, array( $url, $file_type ) );
		$this->assertEquals( $expect, $result );
		if ( false !== $expect ) {
			$path_to_expect_file = self::OUTPUT_DIRECTORY . $expect_file;
			$files               = glob( self::OUTPUT_DIRECTORY . '/*', GLOB_MARK );
			$message             = 'File ' . $path_to_expect_file . "doesn't exist.\nExisting file list:\n" . implode( "\n", $files );
			$this->assertFileExists( $path_to_expect_file, $message );
		}
	}

	/**
	 * Function create_static_file() should create home page.
	 * Function create_static_file() should create seo files.
	 * 
	 * @return array[]
	 */
	public function provider_create_static_file() {
		return array(
			array( '/', 'front_page', '/tmp/static/index.html', '/index.html' ),
			array( '/sitemap.xml', 'seo_files', '/tmp/static/sitemap.xml', '/sitemap.xml' ),
		);
	}

	/**
	 * Test steps for other_url().
	 *
	 * @dataProvider provider_other_url
	 *
	 * @param string       $content     Argument.
	 * @param string       $url         Argument.
	 * @param array        $expect      Expect return value.
	 * @param Expect_Url[] $expect_urls Expect URLs in table.
	 *
	 * @throws ReflectionException     When fail to create ReflectionClass instance.
	 */
	public function test_other_url( $content, $url, $expect, $expect_urls ) {
		$urls         = array(
			array(
				'url' => '/',
			),
			array(
				'url' => '/test/',
			),
		);
		$static_press = new static_press( 'staticpress' );
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'update_url' );
		$method->setAccessible( true );
		$method->invokeArgs( $static_press, array( $urls ) );
		$method = $reflection->getMethod( 'other_url' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $static_press, array( $content, $url ) );
		$this->assertEquals( $expect, $result );
		$method = $reflection->getMethod( 'get_all_url' );
		$method->setAccessible( true );

		$results = $method->invokeArgs( $static_press, array() );
		$this->assert_url( $expect_urls, $results );
	}

	/**
	 * Function other_url() should return empty array when all of self or parent URL exists.
	 * Function other_url() shouldn't insert URL to table when all of self or parent URL exists.
	 * Function other_url() shouldn't add any URL when content doesn't include link to other page.
	 * Function other_url() should return array of map of all existing URL data
	 * when any of self or parent URL doesn't exist.
	 * Function other_url() should insert URL to table when any of self or parent URL exists.
	 * Function other_url() should add URLs of other page included in content
	 * when content includes link to other page.
	 *
	 * @return array[]
	 */
	public function provider_other_url() {
		return array(
			array(
				'',
				'/',
				array(),
				array(
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
				),
			),
			array(
				'',
				'/test/',
				array(),
				array(
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
				),
			),
			array(
				'',
				'/test/index.html',
				array(),
				array(
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
				),
			),
			array(
				'',
				'/test/test/index.html',
				array(
					array(
						'url'           => '/test/test/',
						'last_modified' => DATE_FOR_TEST,
					),
				),
				array(
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/test/', '1' ),
				),
			),
			array(
				'href="http://example.org/test"',
				'/',
				array(),
				array(
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
				),
			),
			array(
				'href="http://example.org/test/test"',
				'/',
				array(
					array(
						'url'           => '/test/test/',
						'last_modified' => DATE_FOR_TEST,
					),
				),
				array(
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/test/', '1' ),
				),
			),
			array(
				'href="http://example.org/test/test/"',
				'/',
				array(
					array(
						'url'           => '/test/test/',
						'last_modified' => DATE_FOR_TEST,
					),
				),
				array(
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/test/', '1' ),
				),
			),
			array(
				'href="http://example.org/test/test/index.html"' . "\n" . 'href="http://example.org/test/test2/index.html"',
				'/',
				array(
					array(
						'url'           => '/test/test/index.html',
						'last_modified' => DATE_FOR_TEST,
					),
					array(
						'url'           => '/test/test2/index.html',
						'last_modified' => DATE_FOR_TEST,
					),
				),
				array(
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/test/index.html', '1' ),
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/test2/index.html', '1' ),
				),
			),
			array(
				'href="http://example.org/test/test/index.html"' . "\n" . 'href="http://example.org/test/test2/index.html"',
				'/test/test/index.html',
				array(
					array(
						'url'           => '/test/test/',
						'last_modified' => DATE_FOR_TEST,
					),
					array(
						'url'           => '/test/test/index.html',
						'last_modified' => DATE_FOR_TEST,
					),
					array(
						'url'           => '/test/test2/index.html',
						'last_modified' => DATE_FOR_TEST,
					),
				),
				array(
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/test/', '1' ),
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/test/index.html', '1' ),
					new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/test2/index.html', '1' ),
				),
			),
		);
	}

	/**
	 * Function rewrite_generator_tag should return generator meta tag which added plugin name and version.
	 */
	public function test_rewrite_generator_tag() {
		$content        = '<meta name="generator" content="WordPress 5.3" />';
		$file_data      = get_file_data(
			dirname( dirname( dirname( __FILE__ ) ) ) . '/plugin.php',
			array(
				'pluginname' => 'Plugin Name',
				'version'    => 'Version',
			)
		);
		$plugin_name    = $file_data['pluginname'];
		$plugin_version = $file_data['version'];
		$expect         = '<meta name="generator" content="WordPress 5.3 with ' . $plugin_name . ' ver.' . $plugin_version . '" />';

		$static_press = new static_press( 'staticpress' );
		$result       = $static_press->rewrite_generator_tag( $content );
		$this->assertEquals( $expect, $result );
	}

	/**
	 * Test steps for url_exists().
	 *
	 * @dataProvider provider_url_exists
	 *
	 * @param string $link   Argument.
	 * @param bool   $expect Expect return value.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_url_exists( $link, $expect ) {
		$urls = array(
			array(
				'url' => '/',
			),
		);

		$static_press = new static_press( 'staticpress' );
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'update_url' );
		$method->setAccessible( true );
		$method->invokeArgs( $static_press, array( $urls ) );
		$method = $reflection->getMethod( 'url_exists' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $static_press, array( $link ) );
		$this->assertEquals( $expect, $result );
	}

	/**
	 * Function test_rul_exists() should return whether URL exists or not.
	 *
	 * @return array[]
	 */
	public function provider_url_exists() {
		return array(
			array( '', true ),
			array( '/', true ),
			array( '/test', false ),
			array( '/test.php', false ),
		);
	}

	/**
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_update_url() {
		$urls        = array(
			array(
				'url' => '/',
			),
			array(
				'url' => '/test/',
			),
		);
		$expect_urls = array(
			new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/', '1' ),
			new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
		);

		$static_press = new static_press( 'staticpress' );
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'update_url' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $static_press, array( $urls ) );
		$this->assertEquals( $result, $urls );
		$method = $reflection->getMethod( 'get_all_url' );
		$method->setAccessible( true );

		$results = $method->invokeArgs( $static_press, array() );
		$this->assert_url( $expect_urls, $results );
	}

	/**
	 * Function get_all_url() should return array of inserted URL object.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_get_all_url() {
		$urls = array(
			array(
				'url' => '/',
			),
			array(
				'url' => '/test/',
			),
		);

		$static_press = new static_press( 'staticpress' );
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'update_url' );
		$method->setAccessible( true );
		$method->invokeArgs( $static_press, array( $urls ) );
		$method = $reflection->getMethod( 'get_all_url' );
		$method->setAccessible( true );

		$results = $method->invokeArgs( $static_press, array() );
		$this->assert_url(
			array(
				new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/', '1' ),
				new Expect_Url( 'other_page', '/test/', '1' ),
			),
			$results
		);
	}

	/**
	 * Asserts Url data.
	 *
	 * @param Expect_Url[]      $expect Expect url data.
	 * @param array|object|null $actual Actual url data.
	 */
	private function assert_url( $expect, $actual ) {
		$length = count( $expect );
		$this->assertEquals( $length, count( $actual ) );
		for ( $index = 0; $index < $length; $index ++ ) {
			$expect_url = $expect[ $index ];
			$actual_url = $actual[ $index ];
			$this->assertInternalType( 'string', $actual_url->ID );
			$this->assertNotEquals( 0, intval( $actual_url->ID ) );
			$this->assertEquals( $expect_url->type, $actual_url->type );
			$this->assertEquals( $expect_url->url, $actual_url->url );
			$this->assertEquals( $expect_url->pages, $actual_url->pages );
		}
	}

	/**
	 * Function test_fetch_start_time() should return current date time string
	 * when fetch_start_time in transient_key is not set.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_fetch_start_time() {
		$static_press = new static_press( 'staticpress' );
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'fetch_start_time' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $static_press, array() );
		$this->assertEquals( $result, DATE_FOR_TEST );
	}

	/**
	 * Function test_fetch_start_time() should return fetch_start_time in transient_key
	 * when fetch_start_time in transient_key is set.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_fetch_start_time_transient_key() {
		$start_time                = '2019-12-23 12:34:56';
		$param['fetch_start_time'] = $start_time;
		set_transient( 'static static', $param, 3600 );
		$static_press = new static_press( 'staticpress' );
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'fetch_start_time' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $static_press, array() );
		$this->assertEquals( $start_time, $result );
	}

	/**
	 * Function get_transient_key() should return appropriate string when current user id is not set.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_get_transient_key() {
		$static_press = new static_press( 'staticpress' );
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'get_transient_key' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $static_press, array() );
		$this->assertEquals( 'static static', $result );
	}

	/**
	 * Function get_transient_key() should return appropriate string when current user id is set.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_get_transient_key_current_user() {
		wp_set_current_user( 1 );
		$static_press = new static_press( 'staticpress' );
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'get_transient_key' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $static_press, array() );
		$this->assertEquals( 'static static - 1', $result );
	}

	/**
	 * Creates response.
	 * 
	 * @param string $url URL.
	 * @return array Responce.
	 */
	private function create_response( $url ) {
		$body        = file_get_contents( dirname( __FILE__ ) . '/../testresources/index-example.html' );
		$status_code = 200;
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
}

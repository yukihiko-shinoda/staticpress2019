<?php
/**
 * Class Static_Press_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-expect-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-repository-for-test.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-test-utility.php';
use Mockery;
use static_press\includes\Static_Press_Ajax_Init;
use static_press\includes\Static_Press_Model_Url_Failed;
use static_press\includes\Static_Press_Model_Url_Fetched;
use static_press\includes\Static_Press_Repository;
use static_press\tests\testlibraries\Expect_Url;
use static_press\tests\testlibraries\Model_Url;
use static_press\tests\testlibraries\Repository_For_Test;
use static_press\tests\testlibraries\Test_Utility;

/**
 * StaticPress test case.
 *
 * @noinspection PhpUndefinedClassInspection
 */
class Static_Press_Ajax_Processor_Test extends \WP_UnitTestCase {
	const OUTPUT_DIRECTORY = '/tmp/static/';
	const DATE_FOR_TEST    = '2019-12-23 12:34:56';
	/**
	 * Function json_output() should die.
	 * 
	 * @runInSeparateProcess
	 */
	public function test_json_output() {
		$argument     = array(
			'result'     => true,
			'urls_count' => array( 'test' ),
		);
		$expect       = '{"result":true,"urls_count":["test"]}';
		$static_press = new Static_Press_Ajax_Init(
			null,
			null,
			new Static_Press_Repository(),
			null,
			Test_Utility::create_terminator_mock()
		);
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'json_output' );
		$method->setAccessible( true );
		ob_start();
		try {
			$method->invokeArgs( $static_press, array( $argument ) );
			// Reason: No need to execute any task.
		} catch ( \Exception $exception ) { // phpcs:ignore
			$output = ob_get_clean();
			$this->assertEquals( $expect, $output );
			return;
		}
		$this->fail();
	}

	/**
	 * Test steps for create_static_file().
	 *
	 * @dataProvider provider_create_static_file
	 *
	 * @param string $http_status_code Argument.
	 * @param string $url              Argument.
	 * @param string $file_type        Argument.
	 * @param string $expect           Expect return value.
	 * @param string $expect_file      Expect file.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_create_static_file( $http_status_code, $url, $file_type, $expect, $expect_file ) {
		file_put_contents( ABSPATH . 'wp-content/uploads/2020/03/test.txt', '' );
		$remote_getter_mock = Mockery::mock( 'alias:Remote_Getter_Mock' );
		$remote_getter_mock->shouldReceive( 'remote_get' )->andReturn( Test_Utility::create_response( '/', 'index-example.html', $http_status_code ) );
		$url_object          = new Model_Url( 1, $file_type, $url, null, null, null, 1, null, null, null, null, null, null, null );
		$url_fetched         = new Static_Press_Model_Url_Fetched( $url_object );
		$static_file_creator = $this->create_accessable_method( 'create_static_file_creator_by_factory', array( $url_fetched ), $remote_getter_mock );
		$result              = $static_file_creator->create( $url );
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
			array( 200, '/', 'front_page', '/tmp/static/index.html', '/index.html' ),
			array( 500, '/?author=1/', 'author_archive', false, null ),
			array( 200, '/wp-content/uploads/2020/03/test.png', Model_Url::TYPE_STATIC_FILE, false, null ),
			array( 200, '/wp-content/uploads/2020/03/test.txt', Model_Url::TYPE_STATIC_FILE, '/tmp/static/wp-content/uploads/2020/03/test.txt', '/wp-content/uploads/2020/03/test.txt' ),
			array( 200, '/sitemap.xml', 'seo_files', '/tmp/static/sitemap.xml', '/sitemap.xml' ),
		);
	}

	/**
	 * Function update_url() should update enable when URL exists in database table.
	 * Function update_url() should insert URL when URL doesn't exist in database table.
	 * Function update_url() should save as disable when URL is PHP file.
	 * Function update_url() should save as disable when URL is get request with parameter.
	 * Function update_url() should save as disable when URL is WordPress admin home page.
	 * Function update_url() should save as disable when URL is readme.
	 * Function update_url() should save as disable when URL is not exist.
	 * Function update_url() should save as enable when URL is activated plugin's static file.
	 * Function update_url() should save as disable when URL is not current theme's static file.
	 * Function update_url() should save as enable when URL is current theme's static file.
	 * 
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_update_url() {
		global $wp_version;
		if ( version_compare( $wp_version, '5.3.0', '<' ) ) {
			$theme_to_activate = 'twentyfifteen';
		} else {
			$theme_to_activate = 'twentytwenty';
		}
		$urls = array(
			array(
				'url' => '/',
			),
			array(
				'url' => '/test/',
			),
			array(
				'url' => '/test.php',
			),
			array(
				'url' => '/test?parameter=value',
			),
			array(
				'url' => '/wp-admin/',
			),
			array(
				'url'  => '/readme.txt',
				'type' => Model_Url::TYPE_STATIC_FILE,
			),
			array(
				'url'  => '/test.png',
				'type' => Model_Url::TYPE_STATIC_FILE,
			),
			array(
				'url'  => '/wp-content/plugins/akismet/_inc/akismet.css',
				'type' => Model_Url::TYPE_STATIC_FILE,
			),
			array(
				'url'  => '/wp-content/themes/twentynineteen/style.css',
				'type' => Model_Url::TYPE_STATIC_FILE,
			),
			array(
				'url'  => "/wp-content/themes/{$theme_to_activate}/style.css",
				'type' => Model_Url::TYPE_STATIC_FILE,
			),
		);
		Repository_For_Test::insert_url(
			new Model_Url(
				1,
				'other_page',
				'/test/',
				0,
				'',
				0,
				1,
				0,
				'',
				'0000-00-00 00:00:00',
				0,
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00'
			)
		);
		$expect_urls_in_database = array(
			new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
			new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
			new Expect_Url( Model_Url::TYPE_STATIC_FILE, '/wp-content/plugins/akismet/_inc/akismet.css', '1' ),
			new Expect_Url( Model_Url::TYPE_STATIC_FILE, "/wp-content/themes/{$theme_to_activate}/style.css", '1' ),
		);
		activate_plugin( 'akismet/akismet.php' );
		switch_theme( $theme_to_activate );
		$static_press = new Static_Press_Ajax_Init(
			null,
			null,
			new Static_Press_Repository(),
			null
		);
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'update_url' );
		$method->setAccessible( true );
		$method->invokeArgs( $static_press, array( $urls ) );
		$method = $reflection->getMethod( 'fetch_start_time' );
		$method->setAccessible( true );
		$start_time = $method->invokeArgs( $static_press, array() );
		$repository = new Static_Press_Repository();
		$results    = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function update_url() should save as disable when dump directory is same with absolute path.
	 */
	public function test_update_url_case_dump_directory_is_absolute_path() {
		$urls                    = array(
			array(
				'url'  => '/',
				'type' => Model_Url::TYPE_STATIC_FILE,
			),
		);
		$expect_urls_in_database = array();
		$static_press            = new Static_Press_Ajax_Init(
			null,
			ABSPATH,
			new Static_Press_Repository(),
			null
		);
		$reflection              = new \ReflectionClass( get_class( $static_press ) );
		$method                  = $reflection->getMethod( 'update_url' );
		$method->setAccessible( true );
		$method->invokeArgs( $static_press, array( $urls ) );
		$method = $reflection->getMethod( 'fetch_start_time' );
		$method->setAccessible( true );
		$start_time = $method->invokeArgs( $static_press, array() );
		$repository = new Static_Press_Repository();
		$results    = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function update_url() should save as disable when file is not updated after last dump.
	 */
	public function test_update_url_case_non_update_file() {
		if ( ! file_exists( self::OUTPUT_DIRECTORY ) ) {
			mkdir( self::OUTPUT_DIRECTORY, 0755 );
		}
		file_put_contents( self::OUTPUT_DIRECTORY . 'test.txt', '' );
		file_put_contents( ABSPATH . 'test.txt', '' );
		$urls                    = array(
			array(
				'url'  => '/test.txt',
				'type' => Model_Url::TYPE_STATIC_FILE,
			),
		);
		$expect_urls_in_database = array();
		$static_press            = new Static_Press_Ajax_Init(
			null,
			self::OUTPUT_DIRECTORY,
			new Static_Press_Repository(),
			null
		);
		$reflection              = new \ReflectionClass( get_class( $static_press ) );
		$method                  = $reflection->getMethod( 'update_url' );
		$method->setAccessible( true );

		$method->invokeArgs( $static_press, array( $urls ) );
		$method = $reflection->getMethod( 'fetch_start_time' );
		$method->setAccessible( true );
		$start_time = $method->invokeArgs( $static_press, array() );
		$repository = new Static_Press_Repository();
		$results    = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function update_url() should save as disable when URL is not activated plugin's static file.
	 */
	public function test_update_url_case_non_active_plugin_static_file() {
		deactivate_plugins( array( 'akismet/akismet.php' ) );
		$urls                    = array(
			array(
				'url'  => '/wp-content/plugins/akismet/_inc/akismet.css',
				'type' => Model_Url::TYPE_STATIC_FILE,
			),
		);
		$expect_urls_in_database = array();
		$static_press            = new Static_Press_Ajax_Init(
			null,
			null,
			new Static_Press_Repository(),
			null
		);
		$reflection              = new \ReflectionClass( get_class( $static_press ) );
		$method                  = $reflection->getMethod( 'update_url' );
		$method->setAccessible( true );

		$method->invokeArgs( $static_press, array( $urls ) );
		$method = $reflection->getMethod( 'fetch_start_time' );
		$method->setAccessible( true );
		$start_time = $method->invokeArgs( $static_press, array() );
		$repository = new Static_Press_Repository();
		$results    = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function update_url() should save as disable when URL is not activated plugin's static file.
	 */
	public function test_update_url_case_static_file_not_plugin_nor_theme() {
		file_put_contents( ABSPATH . 'wp-content/uploads/2020/03/test.txt', '' );
		$urls                    = array(
			array(
				'url'  => '/wp-content/uploads/2020/03/test.txt',
				'type' => Model_Url::TYPE_STATIC_FILE,
			),
		);
		$expect_urls_in_database = array(
			new Expect_Url( Model_Url::TYPE_STATIC_FILE, '/wp-content/uploads/2020/03/test.txt', '1' ),
		);
		$static_press            = new Static_Press_Ajax_Init(
			null,
			null,
			new Static_Press_Repository(),
			null
		);
		$reflection              = new \ReflectionClass( get_class( $static_press ) );
		$method                  = $reflection->getMethod( 'update_url' );
		$method->setAccessible( true );

		$method->invokeArgs( $static_press, array( $urls ) );
		$method = $reflection->getMethod( 'fetch_start_time' );
		$method->setAccessible( true );
		$start_time = $method->invokeArgs( $static_press, array() );
		$repository = new Static_Press_Repository();
		$results    = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function test_fetch_start_time() should return current date time string
	 * when fetch_start_time in transient_key is not set.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_fetch_start_time() {
		$result = $this->create_accessable_method( 'fetch_start_time', array() );
		$this->assertEquals( self::DATE_FOR_TEST, $result );
	}

	/**
	 * Function fetch_start_time() should return fetch_start_time in transient_key
	 * when fetch_start_time in transient_key is set.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_fetch_start_time_transient_key() {
		$start_time                = '2019-12-23 12:34:56';
		$param['fetch_start_time'] = $start_time;
		set_transient( 'static static', $param, 3600 );
		$result = $this->create_accessable_method( 'fetch_start_time', array() );
		$this->assertEquals( $start_time, $result );
	}

	/**
	 * Creates accessable method.
	 * 
	 * @param  string        $method_name     Method name.
	 * @param  array         $array_parameter Array of parameter.
	 * @param  MockInterface $remote_get_mock Mock interface for Remote get.
	 * @return mixed  Returned value.
	 */
	private function create_accessable_method( $method_name, $array_parameter, $remote_get_mock = null ) {
		$static_press = new Static_Press_Ajax_Init(
			null,
			self::OUTPUT_DIRECTORY,
			new Static_Press_Repository(),
			$remote_get_mock ? $remote_get_mock : Test_Utility::create_remote_getter_mock(),
			Test_Utility::create_terminator_mock(),
			Test_Utility::create_date_time_factory_mock( 'create_date_by_time', 'Y-m-d h:i:s', '2019-12-23 12:34:56' )
		);
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $static_press, $array_parameter );
	}
}

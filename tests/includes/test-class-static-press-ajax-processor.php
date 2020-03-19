<?php
/**
 * Class Static_Press_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-expect-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-die-exception.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url-handler.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-repository-for-test.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-test-utility.php';
use Mockery;
use static_press\includes\Static_Press_Ajax_Init;
use static_press\includes\Static_Press_Business_Logic_Exception;
use static_press\includes\Static_Press_Repository;
use static_press\tests\testlibraries\Die_Exception;
use static_press\tests\testlibraries\Model_Url;
use static_press\tests\testlibraries\Model_Url_Handler;
use static_press\tests\testlibraries\Test_Utility;

/**
 * StaticPress test case.
 *
 * @noinspection PhpUndefinedClassInspection
 */
class Static_Press_Ajax_Processor_Test extends \WP_UnitTestCase {
	const OUTPUT_DIRECTORY = '/tmp/static/';
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
		} catch ( Die_Exception $exception ) {
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
		$url_fetched         = Model_Url_Handler::create_model_url_fetched( 1, $file_type, $url, 1 );
		$static_file_creator = $this->create_accessable_method( 'create_static_file_creator_by_factory', array( $url_fetched ), $remote_getter_mock );
		$result              = $static_file_creator->create( $url );
		$this->assertEquals( $expect, $result );
		$path_to_expect_file = self::OUTPUT_DIRECTORY . $expect_file;
		$files               = glob( self::OUTPUT_DIRECTORY . '/*', GLOB_MARK );
		$message             = 'File ' . $path_to_expect_file . "doesn't exist.\nExisting file list:\n" . implode( "\n", $files );
		$this->assertFileExists( $path_to_expect_file, $message );
	}

	/**
	 * Function create_static_file() should create home page.
	 * Function create_static_file() should create seo files.
	 * 
	 * @return array[]
	 */
	public function provider_create_static_file() {
		return array(
			array( 200, '/', Model_Url::TYPE_FRONT_PAGE, '/tmp/static/index.html', '/index.html' ),
			array( 200, '/wp-content/uploads/2020/03/test.txt', Model_Url::TYPE_STATIC_FILE, '/tmp/static/wp-content/uploads/2020/03/test.txt', '/wp-content/uploads/2020/03/test.txt' ),
			array( 200, '/sitemap.xml', Model_Url::TYPE_SEO_FILES, '/tmp/static/sitemap.xml', '/sitemap.xml' ),
		);
	}

	/**
	 * Test steps for create_static_file().
	 *
	 * @dataProvider provider_create_static_file_exception
	 *
	 * @param string $http_status_code Argument.
	 * @param string $url              Argument.
	 * @param string $file_type        Argument.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_create_static_file_exception( $http_status_code, $url, $file_type ) {
		file_put_contents( ABSPATH . 'wp-content/uploads/2020/03/test.txt', '' );
		$remote_getter_mock = Mockery::mock( 'alias:Remote_Getter_Mock' );
		$remote_getter_mock->shouldReceive( 'remote_get' )->andReturn( Test_Utility::create_response( '/', 'index-example.html', $http_status_code ) );
		$url_fetched         = Model_Url_Handler::create_model_url_fetched( 1, $file_type, $url, 1 );
		$static_file_creator = $this->create_accessable_method( 'create_static_file_creator_by_factory', array( $url_fetched ), $remote_getter_mock );
		// Reason: This project no longer support PHP 5.5 nor lower.
		$this->expectException( Static_Press_Business_Logic_Exception::class ); // phpcs:ignore
		$static_file_creator->create( $url );
	}

	/**
	 * Function create_static_file() should create home page.
	 * Function create_static_file() should create seo files.
	 * 
	 * @return array[]
	 */
	public function provider_create_static_file_exception() {
		return array(
			array( 500, '/?author=1/', Model_Url::TYPE_AUTHOR_ARCHIVE ),
			array( 200, '/wp-content/uploads/2020/03/test.png', Model_Url::TYPE_STATIC_FILE ),
		);
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
			Test_Utility::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s', '2019-12-23 12:34:56' )
		);
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $static_press, $array_parameter );
	}
}

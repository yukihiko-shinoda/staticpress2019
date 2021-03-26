<?php
/**
 * Class Static_Press_Test
 *
 * @package static_press\tests\includes\controllers
 */

namespace static_press\tests\includes\controllers;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/exceptions/class-die-exception.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/infrastructure/class-environment.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/infrastructure/class-file-system-operator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-mock-creator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-model-url-creator.php';
use static_press\includes\controllers\Static_Press_Ajax_Init;
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\exceptions\Static_Press_Business_Logic_Exception;
use static_press\includes\repositories\Static_Press_Repository;
use static_press\tests\testlibraries\exceptions\Die_Exception;
use static_press\tests\testlibraries\infrastructure\Environment;
use static_press\tests\testlibraries\infrastructure\File_System_Operator;
use static_press\tests\testlibraries\creators\Mock_Creator;
use static_press\tests\testlibraries\creators\Model_Url_Creator;
/**
 * StaticPress test case.
 *
 * @noinspection PhpUndefinedClassInspection
 */
class Static_Press_Ajax_Processor_Test extends \WP_UnitTestCase {
	/**
	 * Deletes files in content directory if exist.
	 */
	public function tearDown() {
		$directory = ABSPATH . 'wp-content/uploads/2020/';
		if ( file_exists( $directory ) ) {
			File_System_Operator::delete_files( $directory );
		}
		parent::tearDown();
	}

	/**
	 * Function json_output() should die.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_json_output() {
		$argument                = array(
			'result'     => true,
			'urls_count' => array( 'test' ),
		);
		$expect_json             = '{"result":true,"urls_count":["test"]}';
		$expect_http_status_code = 200;
		$actual_json             = null;
		$actual_http_status_code = null;
		$static_press            = new Static_Press_Ajax_Init(
			null,
			null,
			new Static_Press_Repository(),
			null,
			Mock_Creator::create_terminator_mock( $actual_json, $actual_http_status_code )
		);
		$reflection              = new \ReflectionClass( get_class( $static_press ) );
		$method                  = $reflection->getMethod( 'json_output' );
		$method->setAccessible( true );
		try {
			$method->invokeArgs( $static_press, array( $argument ) );
		} catch ( Die_Exception $exception ) {
			$this->assertEquals( $expect_json, $actual_json );
			$this->assertEquals( $expect_http_status_code, $actual_http_status_code );
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
		File_System_Operator::create_file_with_directory( ABSPATH . 'wp-content/uploads/2020/03/test.txt' );
		$url_fetched         = Model_Url_Creator::create_model_url_fetched( 1, $file_type, $url, 1 );
		$static_file_creator = $this->create_accessable_method(
			'create_static_file_creator_by_factory',
			array( $url_fetched ),
			Mock_Creator::create_remote_getter_mock( $http_status_code )
		);
		$result              = $static_file_creator->create( $url );
		$this->assertEquals( $expect, $result );
		$path_to_expect_file = File_System_Operator::OUTPUT_DIRECTORY . $expect_file;
		$files               = File_System_Operator::get_array_file_in_output_directory();
		$message             = 'File ' . $path_to_expect_file . "doesn't exist.\nExisting file list:\n" . implode( "\n", $files );
		$this->assertFileExists( $path_to_expect_file, $message );
	}

	/**
	 * Function create_static_file() should create home page.
	 * Function create_static_file() should create static file.
	 * Function create_static_file() should create seo files.
	 *
	 * @return array[]
	 */
	public function provider_create_static_file() {
		return array(
			array( 200, '/', Static_Press_Model_Url::TYPE_FRONT_PAGE, File_System_Operator::OUTPUT_DIRECTORY . 'index.html', '/index.html' ),
			array(
				200,
				'/' . Environment::DIRECTORY_NAME_WORD_PRESS . '/wp-content/uploads/2020/03/test.txt',
				Static_Press_Model_Url::TYPE_STATIC_FILE,
				File_System_Operator::OUTPUT_DIRECTORY . Environment::DIRECTORY_NAME_WORD_PRESS . '/wp-content/uploads/2020/03/test.txt',
				'/' . Environment::DIRECTORY_NAME_WORD_PRESS . '/wp-content/uploads/2020/03/test.txt',
			),
			array( 200, '/sitemap.xml', Static_Press_Model_Url::TYPE_SEO_FILES, File_System_Operator::OUTPUT_DIRECTORY . 'sitemap.xml', '/sitemap.xml' ),
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
		File_System_Operator::create_file_with_directory( ABSPATH . 'wp-content/uploads/2020/03/test.txt' );
		$url_fetched         = Model_Url_Creator::create_model_url_fetched( 1, $file_type, $url, 1 );
		$static_file_creator = $this->create_accessable_method(
			'create_static_file_creator_by_factory',
			array( $url_fetched ),
			Mock_Creator::create_remote_getter_mock( $http_status_code )
		);
		// Reason: This project no longer support PHP 5.5 nor lower.
		$this->expectException( Static_Press_Business_Logic_Exception::class ); // phpcs:ignore
		$static_file_creator->create( $url );
	}

	/**
	 * Function create_static_file() should throw exception when HTTP status code is not 200.
	 * Function create_static_file() should throw exception when static file doesn't exist.
	 *
	 * @return array[]
	 */
	public function provider_create_static_file_exception() {
		return array(
			array( 500, '/?author=1/', Static_Press_Model_Url::TYPE_AUTHOR_ARCHIVE ),
			array( 200, '/wp-content/uploads/2020/03/test.png', Static_Press_Model_Url::TYPE_STATIC_FILE ),
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
			File_System_Operator::OUTPUT_DIRECTORY,
			new Static_Press_Repository(),
			$remote_get_mock ? $remote_get_mock : Mock_Creator::create_remote_getter_mock(),
			Mock_Creator::create_terminator_mock(),
			Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' ),
			Mock_Creator::create_docuemnt_root_getter_mock()
		);
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $static_press, $array_parameter );
	}
}

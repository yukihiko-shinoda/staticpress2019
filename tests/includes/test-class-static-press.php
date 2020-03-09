<?php
/**
 * Class Static_Press_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\includes;

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

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-array-url-handler.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-expect-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-repository-for-test.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-test-utility.php';
// Reason: This project no longer support PHP 5.5 nor lower.
use const static_press\includes\DATE_FOR_TEST; // phpcs:ignore
use ReflectionException;
use Mockery;
use static_press\includes\Static_Press;
use static_press\includes\Static_Press_Repository;
use static_press\tests\testlibraries\Array_Url_Handler;
use static_press\tests\testlibraries\Expect_Url;
use static_press\tests\testlibraries\Model_Url;
use static_press\tests\testlibraries\Repository_For_Test;
use static_press\tests\testlibraries\Test_Utility;

/**
 * StaticPress test case.
 *
 * @noinspection PhpUndefinedClassInspection
 */
class Static_Press_Test extends \WP_UnitTestCase {
	const OUTPUT_DIRECTORY = '/tmp/static/';
	/**
	 * Sets administrator as current user.
	 *
	 * @see https://wordpress.stackexchange.com/a/207363
	 */
	public function tearDown() {
		self::delete_files( self::OUTPUT_DIRECTORY );
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
	 * Test steps for constructor.
	 * 
	 * @dataProvider provider_init_param_static_url
	 * 
	 * @param string $static_url Argument.
	 * @param string $expect     Expect return value.
	 */
	public function test_init_param_static_url( $static_url, $expect ) {
		$static_press       = new Static_Press( 'staticpress', $static_url );
		$reflector          = new \ReflectionClass( $static_press );
		$reflector_property = $reflector->getProperty( 'static_url' );
		$reflector_property->setAccessible( true );

		$this->assertEquals( $expect, $reflector_property->getValue( $static_press ) );
	}

	/**
	 * Function init_param() should set
	 * persed home URL or DOMAIN_CURRENT_SITE or contents of the Host: header from the current request
	 * when parameter is not HTTP nor HTTPS.
	 * Function init_param() should set parameter when parameter is HTTP.
	 * Function init_param() should set parameter when parameter is HTTPS.
	 */
	public function provider_init_param_static_url() {
		return array(
			array( '/', 'http://example.org/' ),
			array( '//domain.com/', 'http://example.org/' ),
			array( 'http://domain.com/', 'http://domain.com/' ),
			array( 'https://domain.com/', 'https://domain.com/' ),
		);
	}

	/**
	 * Test steps for constructor.
	 * 
	 * @dataProvider provider_init_param_dump_directory
	 * 
	 * @param string $static_url     Argument.
	 * @param string $dump_directory Argument.
	 * @param string $expect         Expect return value.
	 */
	public function test_init_param_dump_directory( $static_url, $dump_directory, $expect ) {
		$static_press       = new Static_Press( 'staticpress', $static_url, $dump_directory );
		$reflector          = new \ReflectionClass( $static_press );
		$reflector_property = $reflector->getProperty( 'dump_directory' );
		$reflector_property->setAccessible( true );

		$this->assertEquals( $expect, $reflector_property->getValue( $static_press ) );
	}

	/**
	 * Function init_param() should set WordPress directory when parameter is empty.
	 * Function init_param() should set parameter when parameter is not empty.
	 * Function init_param() should set path which end with slash.
	 * Function init_param() should set path added relative URL.
	 */
	public function provider_init_param_dump_directory() {
		return array(
			array( '/', '', ABSPATH ),
			array( 'http://domain.com/', '', ABSPATH ),
			array( 'https://domain.com/test', '', ABSPATH . 'test/' ),
			array( '/', '/tmp/', '/tmp/' ),
			array( '/', '/tmp', '/tmp/' ),
			array( 'http://domain.com/', '/tmp', '/tmp/' ),
			array( 'https://domain.com/test', '/tmp/', '/tmp/test/' ),
		);
	}

	/**
	 * Function activate() should ensure that database table which list URL exists.
	 */
	public function test_constructor_create_table() {
		global $wpdb;
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		if ( $wpdb->get_var( "show tables like '{$this->url_table()}'" ) == $this->url_table() ) {
			$wpdb->query( "DROP TABLE `{$this->url_table()}`" );
		}
		$this->assertNotEquals( $this->url_table(), $wpdb->get_var( "show tables like '{$this->url_table()}'" ) );
		$static_press = new Static_Press( 'staticpress' );
		$this->assertEquals( $this->url_table(), $wpdb->get_var( "show tables like '{$this->url_table()}'" ) );
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
	}

	/**
	 * Function activate() should ensure that database table which list URL exists.
	 */
	public function test_activate() {
		global $wpdb;
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$static_press = new Static_Press( 'staticpress' );
		if ( $wpdb->get_var( "show tables like '{$this->url_table()}'" ) == $this->url_table() ) {
			$wpdb->query( "DROP TABLE `{$this->url_table()}`" );
		}
		$this->assertNotEquals( $this->url_table(), $wpdb->get_var( "show tables like '{$this->url_table()}'" ) );
		$static_press->activate();
		$this->assertEquals( $this->url_table(), $wpdb->get_var( "show tables like '{$this->url_table()}'" ) );
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
	}

	/**
	 * Function activate() should ensure that database table which list URL has column 'enable'.
	 */
	public function test_activate_2() {
		global $wpdb;
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		if ( $wpdb->get_var( "show tables like '{$this->url_table()}'" ) == $this->url_table() ) {
			$wpdb->query( "DROP TABLE `{$this->url_table()}`" );
		}
		$this->create_legacy_table();
		$columns = $wpdb->get_results( "show columns from {$this->url_table()} like 'enable'" );
		$this->assertEquals( 0, count( $columns ) );
		$static_press = new Static_Press( 'staticpress' );
		$static_press->activate();
		$columns = $wpdb->get_results( "show columns from {$this->url_table()} like 'enable'" );
		$this->assertEquals( 1, count( $columns ) );
		$column = $columns[0];
		$this->assertEquals( 'enable', $column->Field );         // phpcs:ignore
		$this->assertEquals( 'int(1) unsigned', $column->Type ); // phpcs:ignore
		$this->assertEquals( 'NO', $column->Null );              // phpcs:ignore
		$this->assertEquals( '', $column->Key );                 // phpcs:ignore
		$this->assertEquals( '1', $column->Default );            // phpcs:ignore
		$this->assertEquals( '', $column->Extra );               // phpcs:ignore
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
	}

	/**
	 * Creates legacy table.
	 */
	private function create_legacy_table() {
		global $wpdb;
		$wpdb->query(
			"CREATE TABLE `{$this->url_table()}` (
				`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`type` varchar(255) NOT NULL DEFAULT 'other_page',
				`url` varchar(255) NOT NULL,
				`object_id` bigint(20) unsigned NULL,
				`object_type` varchar(20) NULL ,
				`parent` bigint(20) unsigned NOT NULL DEFAULT 0,
				`pages` bigint(20) unsigned NOT NULL DEFAULT 1,
				`file_name` varchar(255) NOT NULL,
				`file_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`last_statuscode` int(20) NULL,
				`last_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`last_upload` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`create_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY (`ID`),
				KEY `type` (`type`),
				KEY `url` (`url`),
				KEY `file_name` (`file_name`),
				KEY `file_date` (`file_date`),
				KEY `last_upload` (`last_upload`)
			)"
		);
	}

	/**
	 * Creates latest table.
	 */
	private function create_latest_table() {
		global $wpdb;
		$wpdb->query(
			"CREATE TABLE `{$this->url_table()}` (
				`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`type` varchar(255) NOT NULL DEFAULT 'other_page',
				`url` varchar(255) NOT NULL,
				`object_id` bigint(20) unsigned NULL,
				`object_type` varchar(20) NULL ,
				`parent` bigint(20) unsigned NOT NULL DEFAULT 0,
				`pages` bigint(20) unsigned NOT NULL DEFAULT 1,
				`enable` int(1) unsigned NOT NULL DEFAULT '1',
				`file_name` varchar(255) NOT NULL DEFAULT '',
				`file_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`last_statuscode` int(20) NULL,
				`last_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`last_upload` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`create_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY (`ID`),
				KEY `type` (`type`),
				KEY `url` (`url`),
				KEY `file_name` (`file_name`),
				KEY `file_date` (`file_date`),
				KEY `last_upload` (`last_upload`)
			)"
		);
	}

	/**
	 * Function activate() should ensure that database table which list URL exists.
	 */
	public function test_deactivate() {
		global $wpdb;
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$static_press = new Static_Press( 'staticpress' );
		if ( $wpdb->get_var( "show tables like '{$this->url_table()}'" ) != $this->url_table() ) {
			$this->create_latest_table();
		}
		$this->assertEquals( $this->url_table(), $wpdb->get_var( "show tables like '{$this->url_table()}'" ) );
		$static_press->deactivate();
		$this->assertNotEquals( $this->url_table(), $wpdb->get_var( "show tables like '{$this->url_table()}'" ) );
		$this->create_latest_table();
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
	}

	/**
	 * Returns database table name for URL list.
	 */
	private static function url_table() {
		global $wpdb;
		return $wpdb->prefix . 'urls';
	}

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
		$static_press = new Static_Press( 'staticpress', '/', '', array(), $this->create_terminator_mock() );
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
	 * Function ajax_init() should die.
	 * 
	 * @runInSeparateProcess
	 */
	public function test_ajax_init() {
		Test_Utility::set_up_seo_url();
		$user_name     = 'User Name';
		$user_password = 'passW@rd';
		wp_create_user( $user_name, $user_password );
		$result = wp_signon(
			array(
				'user_login'    => $user_name,
				'user_password' => $user_password,
			)
		);
		wp_set_current_user( $result->ID );

		$expect       = '{"result":true,"urls_count":[{"type":"front_page","count":"1"},{"type":"seo_files","count":"5"}]}';
		$static_press = new Static_Press( 'staticpress', '/', '', array(), $this->create_terminator_mock(), Test_Utility::set_up_seo_url() );
		ob_start();
		try {
			$static_press->ajax_init();
			// Reason: No need to execute any task.
		} catch ( \Exception $exception ) { // phpcs:ignore
			$output = ob_get_clean();
			$this->assertEquals( $expect, $output );
			return;
		}
		$this->fail();
	}

	/**
	 * Function get_site_url() should return site URL.
	 * TODO test for multi site.
	 */
	public function test_get_site_url() {
		$result = $this->create_accessable_method( 'get_site_url', array() );
		$this->assertEquals( 'http://example.org/', $result );
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
		$static_press = new Static_Press( 'staticpress' );
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
			array( 'http://google.com/', 'http://google.com/' ),
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
		$remote_getter_mock = $this->create_remote_getter_mock();
		$static_press       = new Static_Press( 'staticpress', '/', self::OUTPUT_DIRECTORY, array(), null, $remote_getter_mock );
		$reflection         = new \ReflectionClass( get_class( $static_press ) );
		$method             = $reflection->getMethod( 'create_static_file' );
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
	 * Sets up for testing seo_url().
	 */
	private function create_remote_getter_mock() {
		$remote_getter_mock = Mockery::mock( 'alias:Remote_Getter_Mock' );
		$remote_getter_mock->shouldReceive( 'remote_get' )->andReturn( Test_Utility::create_response( '/', 'index-example.html' ) );
		return $remote_getter_mock;
	}

	/**
	 * Sets up for testing seo_url().
	 */
	private function create_terminator_mock() {
		$terminator_mock = Mockery::mock( 'alias:Terminator_Mock' );
		$terminator_mock->shouldReceive( 'terminate' )->andThrow( new \Exception( 'Dead!' ) );
		return $terminator_mock;
	}
	/**
	 * Test steps for other_url().
	 *
	 * @dataProvider provider_other_url
	 *
	 * @param string       $content                 Argument.
	 * @param string       $url                     Argument.
	 * @param array        $expect                  Expect return value.
	 * @param Expect_Url[] $expect_urls_in_database Expect URLs in table.
	 *
	 * @throws ReflectionException     When fail to create ReflectionClass instance.
	 */
	public function test_other_url( $content, $url, $expect, $expect_urls_in_database ) {
		$urls         = array(
			array(
				'url' => '/',
			),
			array(
				'url' => '/test/',
			),
		);
		$static_press = new Static_Press( 'staticpress' );
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'update_url' );
		$method->setAccessible( true );
		$method->invokeArgs( $static_press, array( $urls ) );
		$method = $reflection->getMethod( 'other_url' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $static_press, array( $content, $url ) );
		$this->assertEquals( $expect, $result );
		$method = $reflection->getMethod( 'fetch_start_time' );
		$method->setAccessible( true );
		$start_time = $method->invokeArgs( $static_press, array() );
		$repository = new Static_Press_Repository();
		$results    = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
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

		$static_press = new Static_Press( 'staticpress' );
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

		$static_press = new Static_Press( 'staticpress' );
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
				'type' => 'static_file',
			),
			array(
				'url'  => '/test.png',
				'type' => 'static_file',
			),
			array(
				'url'  => '/wp-content/plugins/akismet/_inc/akismet.css',
				'type' => 'static_file',
			),
			array(
				'url'  => '/wp-content/themes/twentynineteen/style.css',
				'type' => 'static_file',
			),
			array(
				'url'  => "/wp-content/themes/{$theme_to_activate}/style.css",
				'type' => 'static_file',
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
			new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
			new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/', '1' ),
			new Expect_Url( Expect_Url::TYPE_STATIC_FILE, '/wp-content/plugins/akismet/_inc/akismet.css', '1' ),
			new Expect_Url( Expect_Url::TYPE_STATIC_FILE, "/wp-content/themes/{$theme_to_activate}/style.css", '1' ),
		);
		activate_plugin( 'akismet/akismet.php' );
		switch_theme( $theme_to_activate );
		$static_press = new Static_Press( 'staticpress', '/', self::OUTPUT_DIRECTORY );
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
	 * Function get_urls() should trancate database table for list URL.
	 * Function get_urls() should return urls of front page, static files, and SEO.
	 */
	public function test_get_urls_trancate() {
		Test_Utility::set_up_seo_url();
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
		$expect_database = array(
			new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
		);
		Expect_Url::assert_url( $this, $expect_database, Repository_For_Test::get_all_url() );
		$expect_urls = array_merge(
			Test_Utility::get_expect_urls_front_page( DATE_FOR_TEST ),
			Test_Utility::get_expect_urls_static_files( DATE_FOR_TEST ),
			Test_Utility::get_expect_urls_seo( DATE_FOR_TEST )
		);
		$actual      = $this->create_accessable_method( 'get_urls', array() );
		Array_Url_Handler::assert_contains_urls( $this, $expect_urls, $actual );
		Expect_Url::assert_url( $this, array(), Repository_For_Test::get_all_url() );
	}

	/**
	 * Function delete_url() should delete URLs specified by key "url" of arrays.
	 */
	public function test_delete_url() {
		Repository_For_Test::insert_url(
			new Model_Url(
				1,
				'other_page',
				'/test1/',
				0,
				'',
				0,
				1,
				1,
				'',
				'0000-00-00 00:00:00',
				0,
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00'
			)
		);
		Repository_For_Test::insert_url(
			new Model_Url(
				2,
				'other_page',
				'/test2/',
				0,
				'',
				0,
				1,
				1,
				'',
				'0000-00-00 00:00:00',
				0,
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00'
			)
		);
		Repository_For_Test::insert_url(
			new Model_Url(
				3,
				'other_page',
				'/test3/',
				0,
				'',
				0,
				1,
				1,
				'',
				'0000-00-00 00:00:00',
				0,
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00'
			)
		);
		Repository_For_Test::insert_url(
			new Model_Url(
				4,
				'other_page',
				'/test4/',
				0,
				'',
				0,
				1,
				1,
				'',
				'0000-00-00 00:00:00',
				0,
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00'
			)
		);
		$parameter               = array(
			array(),
			array( 'url' => '/test1/' ),
			array( 'url' => '/test3/' ),
		);
		$expect_urls_in_database = array(
			new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test2/', '1' ),
			new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test4/', '1' ),
		);

		$actual = $this->create_accessable_method( 'delete_url', array( $parameter ) );
		$this->assertEquals( $parameter, $actual );
		$static_press = new Static_Press( 'staticpress', '/', self::OUTPUT_DIRECTORY );
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'fetch_start_time' );
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
				'type' => 'static_file',
			),
		);
		$expect_urls_in_database = array();
		$static_press            = new Static_Press( 'staticpress' );
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
		mkdir( self::OUTPUT_DIRECTORY, 0755 );
		file_put_contents( self::OUTPUT_DIRECTORY . 'test.txt', '' );
		file_put_contents( ABSPATH . 'test.txt', '' );
		$urls                    = array(
			array(
				'url'  => '/test.txt',
				'type' => 'static_file',
			),
		);
		$expect_urls_in_database = array();
		$static_press            = new Static_Press( 'staticpress', '/', self::OUTPUT_DIRECTORY );
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
				'type' => 'static_file',
			),
		);
		$expect_urls_in_database = array();
		$static_press            = new Static_Press( 'staticpress', '/', self::OUTPUT_DIRECTORY );
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
				'type' => 'static_file',
			),
		);
		$expect_urls_in_database = array(
			new Expect_Url( Expect_Url::TYPE_STATIC_FILE, '/wp-content/uploads/2020/03/test.txt', '1' ),
		);
		$static_press            = new Static_Press( 'staticpress', '/', self::OUTPUT_DIRECTORY );
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
		$this->assertEquals( $result, DATE_FOR_TEST );
	}

	/**
	 * Function fetch_last_id() should return 0 when parameter is not set.
	 */
	public function test_fetch_last_id_without_parameter_with_transient() {
		set_transient( 'static static', array( 'fetch_last_id' => 2 ), 3600 );
		$result = $this->create_accessable_method( 'fetch_last_id', array() );
		$this->assertEquals( $result, 2 );
	}

	/**
	 * Function fetch_last_id() should return 0 when parameter is not set.
	 */
	public function test_fetch_last_id_without_parameter_without_transient() {
		$result = $this->create_accessable_method( 'fetch_last_id', array() );
		$this->assertEquals( $result, 0 );
	}

	/**
	 * Test steps for constructor.
	 * 
	 * @dataProvider provider_fetch_last_id_with_paramter_with_transient
	 * 
	 * @param string $next_id ID to set next.
	 * @param string $expect  Expect return value.
	 */
	public function test_fetch_last_id_with_paramter_with_transient( $next_id, $expect ) {
		set_transient( 'static static', array( 'fetch_last_id' => 2 ), 3600 );
		$result = $this->create_accessable_method( 'fetch_last_id', array( $next_id ) );
		$this->assertEquals( $result, $expect );
	}

	/**
	 * Function fetch_finalyze() should delete transient.
	 */
	public function test_fetch_finalyze() {
		set_transient( 'static static', array( 'fetch_last_id' => 2 ), 3600 );
		$this->create_accessable_method( 'fetch_finalyze', array() );
		$this->assertFalse( get_transient( 'static static', array( 'fetch_last_id' => 2 ), 3600 ) );
	}

	/**
	 * Function fetch_last_id() should return Cached ID when $next_id is 0
	 * Function fetch_last_id() should return Cached ID when $next_id is false,
	 * Function fetch_last_id() should return  $next_id when $next_id is not 0 nor false.
	 */
	public function provider_fetch_last_id_with_paramter_with_transient() {
		return array(
			array( 0, 2 ),
			array( false, 2 ),
			array( 1, 1 ),
		);
	}

	/**
	 * Test steps for constructor.
	 * 
	 * @dataProvider provider_fetch_last_id_with_paramter_without_transient
	 * 
	 * @param string $next_id ID to set next.
	 * @param string $expect  Expect return value.
	 */
	public function test_fetch_last_id_with_paramter_without_transient( $next_id, $expect ) {
		$result = $this->create_accessable_method( 'fetch_last_id', array( $next_id ) );
		$this->assertEquals( $result, $expect );
	}

	/**
	 * Function fetch_last_id() should return Cached ID when $next_id is 0
	 * Function fetch_last_id() should return Cached ID when $next_id is false,
	 * Function fetch_last_id() should return  $next_id when $next_id is not 0 nor false.
	 */
	public function provider_fetch_last_id_with_paramter_without_transient() {
		return array(
			array( 0, 0 ),
			array( false, 0 ),
			array( 1, 1 ),
		);
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
	 * Function fetch_url() should return false when URLs do not exist in database table.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_fetch_url_url_not_exists() {
		$this->assertFalse( $this->create_accessable_method( 'fetch_url', array() ) );
	}

	/**
	 * Function fetch_url() should return URL of first record in database table when URLs exist in database table.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_fetch_url_url_exists() {
		Repository_For_Test::insert_url(
			new Model_Url(
				1,
				'other_page',
				'/test1/',
				0,
				'',
				0,
				1,
				1,
				'',
				'0000-00-00 00:00:00',
				0,
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00'
			)
		);
		Repository_For_Test::insert_url(
			new Model_Url(
				2,
				'other_page',
				'/test2/',
				0,
				'',
				0,
				1,
				1,
				'',
				'0000-00-00 00:00:00',
				0,
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00'
			)
		);
		$expect = array(
			new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test1/', '1' ),
		);
		Expect_Url::assert_url( $this, $expect, array( $this->create_accessable_method( 'fetch_url', array() ) ) );
	}

	/**
	 * Creates accessable method.
	 * 
	 * @param string $method_name     Method name.
	 * @param array  $array_parameter Array of parameter.
	 */
	private function create_accessable_method( $method_name, $array_parameter ) {
		$static_press = new Static_Press( 'staticpress', '/', '', array(), $this->create_terminator_mock(), $this->create_remote_getter_mock() );
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $static_press, $array_parameter );
	}
}

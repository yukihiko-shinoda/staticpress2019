<?php
/**
 * Class Static_Press_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-expect-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-repository-for-test.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url.php';
use static_press\tests\includes\Static_Press_Test;
use static_press\tests\includes\Repository_For_Test;
use static_press\tests\includes\Model_Url;

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

namespace static_press\tests\includes;

// Reason: This project no longer support PHP 5.5 nor lower.
use const static_press\includes\DATE_FOR_TEST; // phpcs:ignore
use ReflectionException;
use Mockery;
use static_press\includes\Static_Press;
use static_press\includes\Static_Press_Repository;
use static_press\tests\testlibraries\Expect_Url;
use static_press\tests\testlibraries\Model_Url;
use static_press\tests\testlibraries\Repository_For_Test;

/**
 * StaticPress test case.
 *
 * @noinspection PhpUndefinedClassInspection
 */
class Static_Press_Test extends \WP_UnitTestCase {
	const OUTPUT_DIRECTORY = '/tmp/static/';
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
			array( '/', '', '/usr/src/wordpress/' ),
			array( 'http://domain.com/', '', '/usr/src/wordpress/' ),
			array( 'https://domain.com/test', '', '/usr/src/wordpress/test/' ),
			array( '/', '/tmp/', '/tmp/' ),
			array( '/', '/tmp', '/tmp/' ),
			array( 'http://domain.com/', '/tmp', '/tmp/' ),
			array( 'https://domain.com/test', '/tmp/', '/tmp/test/' ),
		);
	}

	/**
	 * Function activate() should ensure that database table which listup URL exists.
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
	 * Function activate() should ensure that database table which listup URL exists.
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
	 * Function activate() should ensure that database table which listup URL has column 'enable'.
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
	 * Function activate() should ensure that database table which listup URL exists.
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
		$argument        = array(
			'result'     => true,
			'urls_count' => array( 'test' ),
		);
		$expect          = '{"result":true,"urls_count":["test"]}';
		$terminator_mock = Mockery::mock( 'alias:Terminator_Mock' );
		$terminator_mock->shouldReceive( 'terminate' )->andThrow( new \Exception( 'Dead!' ) );
		$static_press = new Static_Press( 'staticpress', '/', '', array(), $terminator_mock );
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
		self::$wordpress_mock = Mockery::mock( 'alias:WordPress_Mock' );
		self::$wordpress_mock->shouldReceive( 'wp_remote_get' )->andReturn( $this->create_response( '/', 'index-example.html' ) );
		$static_press = new Static_Press( 'staticpress', '/', self::OUTPUT_DIRECTORY );
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
				'url'  => '/wp-content/themes/twentytwenty/style.css',
				'type' => 'static_file',
			),
		);
		$url  = new Model_Url(
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
		);
		Repository_For_Test::insert_url( $url );
		$expect_urls_in_database = array(
			new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
			new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/', '1' ),
			new Expect_Url( Expect_Url::TYPE_STATIC_FILE, '/wp-content/plugins/akismet/_inc/akismet.css', '1' ),
			new Expect_Url( Expect_Url::TYPE_STATIC_FILE, '/wp-content/themes/twentytwenty/style.css', '1' ),
		);
		activate_plugin( 'akismet/akismet.php' );
		switch_theme( 'twentytwenty' );
		$static_press = new Static_Press( 'staticpress', '/', self::OUTPUT_DIRECTORY );
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'update_url' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $static_press, array( $urls ) );
		$this->assertEquals( $result, $urls );
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
				'type' => 'static_file',
			),
		);
		$expect_urls_in_database = array();
		$static_press            = new Static_Press( 'staticpress' );
		$reflection              = new \ReflectionClass( get_class( $static_press ) );
		$method                  = $reflection->getMethod( 'update_url' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $static_press, array( $urls ) );
		$this->assertEquals( $result, $urls );
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
		file_put_contents( '/usr/src/wordpress/test.txt', '' );
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

		$result = $method->invokeArgs( $static_press, array( $urls ) );
		$this->assertEquals( $result, $urls );
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

		$result = $method->invokeArgs( $static_press, array( $urls ) );
		$this->assertEquals( $result, $urls );
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
		file_put_contents( '/usr/src/wordpress/wp-content/uploads/2020/03/test.txt', '' );
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

		$result = $method->invokeArgs( $static_press, array( $urls ) );
		$this->assertEquals( $result, $urls );
		$method = $reflection->getMethod( 'fetch_start_time' );
		$method->setAccessible( true );
		$start_time = $method->invokeArgs( $static_press, array() );
		$repository = new Static_Press_Repository();
		$results    = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function get_urls() should trancate database table for listup URL.
	 * Function get_urls() should return urls of front page, static files, and SEO.
	 */
	public function test_get_urls_trancate() {
		$this->set_up_seo_url();
		$url = new Model_Url(
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
		);
		Repository_For_Test::insert_url( $url );
		$expect_database = array(
			new Expect_Url( Expect_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
		);
		Expect_Url::assert_url( $this, $expect_database, Repository_For_Test::get_all_url() );
		$expect_urls = array_merge(
			$this->get_expect_urls_front_page(),
			$this->get_expect_urls_static_files(),
			$this->get_expect_urls_seo()
		);
		$actual      = $this->create_accessable_method( 'get_urls', array() );
		$this->assert_urls( $expect_urls, $actual );
		Expect_Url::assert_url( $this, array(), Repository_For_Test::get_all_url() );
	}

	/**
	 * Function seo_url() should trancate database table for listup URL.
	 */
	public function test_seo_url() {
		$this->set_up_seo_url();
		$expect_urls = $this->get_expect_urls_seo();
		$actual      = $this->create_accessable_method( 'seo_url', array() );
		$this->assert_urls( $expect_urls, $actual );
	}

	/**
	 * Sets up for testing seo_url().
	 */
	private function set_up_seo_url() {
		self::$wordpress_mock = Mockery::mock( 'alias:WordPress_Mock' );
		self::$wordpress_mock->shouldReceive( 'wp_remote_get' )
		->with( 'http://example.org/robots.txt', array() )
		->andReturn( $this->create_response( '/robots.txt', 'robots.txt' ) );
		self::$wordpress_mock->shouldReceive( 'wp_remote_get' )
		->with( 'http://example.org/sitemap.xml', array() )
		->andReturn( $this->create_response( '/sitemap.xml', 'sitemap.xml' ) );
		self::$wordpress_mock->shouldReceive( 'wp_remote_get' )
		->with( 'http://example.org/sitemap-misc.xml', array() )
		->andReturn( $this->create_response( '/sitemap-misc.xml', 'sitemap-misc.xml' ) );
		self::$wordpress_mock->shouldReceive( 'wp_remote_get' )
		->with( 'http://example.org/sitemap-tax-category.xml', array() )
		->andReturn( $this->create_response( '/sitemap-tax-category.xml', 'sitemap-tax-category.xml' ) );
		self::$wordpress_mock->shouldReceive( 'wp_remote_get' )
		->with( 'http://example.org/sitemap-pt-post-2020-02.xml', array() )
		->andReturn( $this->create_response( '/sitemap-pt-post-2020-02.xml', 'sitemap-pt-post-2020-02.xml' ) );
	}

	/**
	 * Gets expect URLs of seo_url().
	 */
	private function get_expect_urls_seo() {
		return array(
			array(
				'type'          => 'seo_files',
				'url'           => '/robots.txt',
				'last_modified' => DATE_FOR_TEST,
			),
			array(
				'type'          => 'seo_files',
				'url'           => '/sitemap.xml',
				'last_modified' => DATE_FOR_TEST,
			),
			array(
				'type'          => 'seo_files',
				'url'           => '/sitemap-misc.xml',
				'last_modified' => DATE_FOR_TEST,
			),
			array(
				'type'          => 'seo_files',
				'url'           => '/sitemap-tax-category.xml',
				'last_modified' => DATE_FOR_TEST,
			),
			array(
				'type'          => 'seo_files',
				'url'           => '/sitemap-pt-post-2020-02.xml',
				'last_modified' => DATE_FOR_TEST,
			),
		);
	}

	/**
	 * Function front_page_url() should return appropriate URLs.
	 */
	public function test_front_page_url() {
		$expect        = $this->get_expect_urls_front_page();
		$actual        = $this->create_accessable_method( 'front_page_url', array() );
		$length_expect = count( $expect );
		$this->assertEquals( $length_expect, count( $actual ) );
		for ( $index = 0; $index < $length_expect; $index ++ ) {
			$expect_url = $expect[ $index ];
			$actual_url = $actual[ $index ];
			$this->assertEquals( $expect_url, $actual_url );
		}
	}

	/**
	 * Gets expect URLs of front_page_url().
	 */
	private function get_expect_urls_front_page() {
		return array(
			array(
				'type'          => 'front_page',
				'url'           => '/',
				'last_modified' => DATE_FOR_TEST,
			),
		);
	}

	/**
	 * Function single_url() should return URLs of posts.
	 * Function single_url() should return number of pages by split post content by nextpage tag.
	 */
	public function test_single_url() {
		$expect = array(
			array(
				'type'          => 'single',
				'url'           => '/?attachment_id=4/',
				'object_id'     => 4,
				'object_type'   => 'attachment',
				'pages'         => 1,
				'last_modified' => DATE_FOR_TEST,
			),
			array(
				'type'          => 'single',
				'url'           => '/?attachment_id=5/',
				'object_id'     => 5,
				'object_type'   => 'attachment',
				'pages'         => 3,
				'last_modified' => DATE_FOR_TEST,
			),
		);
		wp_insert_post(
			array(
				'post_title'   => 'Post Title 1',
				'post_content' => 'Post content 1.',
				'post_status'  => 'publish',
				'post_type'    => 'attachment',
			)
		);
		wp_insert_post(
			array(
				'post_title'   => 'Post Title 2',
				'post_content' => 'test<!--nextpage-->test<!--nextpage-->test',
				'post_status'  => 'publish',
				'post_type'    => 'attachment',
			)
		);
		$actual = $this->create_accessable_method( 'single_url', array() );
		$this->assert_urls( $expect, $actual );
	}

	/**
	 * Function terms_url() should return URLs of terms.
	 */
	public function test_terms_url() {
		$term_parent = wp_insert_category(
			array(
				'cat_name' => 'category parent',
			)
		);
		$term_child  = wp_insert_category(
			array(
				'cat_name'             => 'category child',
				'category_description' => '',
				'category_nicename'    => '',
				'category_parent'      => $term_parent,
			)
		);
		wp_insert_post(
			array(
				'post_title'    => 'Test Title',
				'post_content'  => 'Test content.',
				'post_status'   => 'publish',
				'post_type'     => 'post',
				'post_category' => array(
					$term_child,
				),
			)
		);
		$expect = array(
			array(
				'type'          => 'term_archive',
				'url'           => '/?cat=3/',
				'object_id'     => 3,
				'object_type'   => 'category',
				'pages'         => 1,
				'parent'        => 2,
				'last_modified' => DATE_FOR_TEST,
			),
			array(
				'type'          => 'term_archive',
				'url'           => '/?cat=2/',
				'object_id'     => 2,
				'object_type'   => 'category',
				'pages'         => 1,
				'parent'        => 0,
				'last_modified' => DATE_FOR_TEST,
			),
			array(
				'type'          => 'term_archive',
				'url'           => '/?cat=3/',
				'object_id'     => 3,
				'object_type'   => 'category',
				'pages'         => 1,
				'parent'        => 2,
				'last_modified' => DATE_FOR_TEST,
			),
		);
		$actual = $this->create_accessable_method( 'terms_url', array() );
		$this->assert_urls( $expect, $actual );
	}

	/**
	 * Function author_url() should return URLs of authors.
	 */
	public function test_author_url() {
		$expect = array(
			array(
				'type'          => 'author_archive',
				'url'           => '/?author=1/',
				'object_id'     => 1,
				'pages'         => 1,
				'last_modified' => DATE_FOR_TEST,
			),
		);
		wp_insert_post(
			array(
				'post_title'   => 'Post Title 1',
				'post_content' => 'Post content 1.',
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'post_author'  => 1,
			)
		);
		$actual = $this->create_accessable_method( 'author_url', array() );
		$this->assert_urls( $expect, $actual );
	}

	/**
	 * Function static_files_url() should return URLs of authors.
	 */
	public function test_static_files_url() {
		$expect = $this->get_expect_urls_static_files();
		wp_insert_post(
			array(
				'post_title'   => 'Post Title 1',
				'post_content' => 'Post content 1.',
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'post_author'  => 1,
			)
		);
		$actual = $this->create_accessable_method( 'static_files_url', array() );
		$this->assert_urls( $expect, $actual );
	}

	/**
	 * Gets expect URLs.
	 */
	private function get_expect_urls_static_files() {
		$expect_urls = array(
			'/readme.html',
			'/license.txt',
			'/test.txt',
			'/wp-admin/css/colors/blue/colors-rtl.css',
			'/wp-admin/css/colors/blue/colors-rtl.min.css',
			'/wp-admin/css/colors/blue/colors.css',
			'/wp-admin/css/colors/blue/colors.min.css',
			'/wp-admin/css/colors/coffee/colors-rtl.css',
			'/wp-admin/css/colors/coffee/colors-rtl.min.css',
			'/wp-admin/css/colors/coffee/colors.css',
			'/wp-admin/css/colors/coffee/colors.min.css',
			'/wp-admin/css/colors/ectoplasm/colors-rtl.css',
			'/wp-admin/css/colors/ectoplasm/colors-rtl.min.css',
			'/wp-admin/css/colors/ectoplasm/colors.css',
			'/wp-admin/css/colors/ectoplasm/colors.min.css',
			'/wp-admin/css/colors/light/colors-rtl.css',
			'/wp-admin/css/colors/light/colors-rtl.min.css',
			'/wp-admin/css/colors/light/colors.css',
			'/wp-admin/css/colors/light/colors.min.css',
			'/wp-admin/css/colors/midnight/colors-rtl.css',
			'/wp-admin/css/colors/midnight/colors-rtl.min.css',
			'/wp-admin/css/colors/midnight/colors.css',
			'/wp-admin/css/colors/midnight/colors.min.css',
			'/wp-admin/css/colors/ocean/colors-rtl.css',
			'/wp-admin/css/colors/ocean/colors-rtl.min.css',
			'/wp-admin/css/colors/ocean/colors.css',
			'/wp-admin/css/colors/ocean/colors.min.css',
			'/wp-admin/css/colors/sunrise/colors-rtl.css',
			'/wp-admin/css/colors/sunrise/colors-rtl.min.css',
			'/wp-admin/css/colors/sunrise/colors.css',
			'/wp-admin/css/colors/sunrise/colors.min.css',
			'/wp-admin/css/about-rtl.css',
			'/wp-admin/css/about-rtl.min.css',
			'/wp-admin/css/about.css',
			'/wp-admin/css/about.min.css',
			'/wp-admin/css/admin-menu-rtl.css',
			'/wp-admin/css/admin-menu-rtl.min.css',
			'/wp-admin/css/admin-menu.css',
			'/wp-admin/css/admin-menu.min.css',
			'/wp-admin/css/code-editor-rtl.css',
			'/wp-admin/css/code-editor-rtl.min.css',
			'/wp-admin/css/code-editor.css',
			'/wp-admin/css/code-editor.min.css',
			'/wp-admin/css/color-picker-rtl.css',
			'/wp-admin/css/color-picker-rtl.min.css',
			'/wp-admin/css/color-picker.css',
			'/wp-admin/css/color-picker.min.css',
			'/wp-admin/css/common-rtl.css',
			'/wp-admin/css/common-rtl.min.css',
			'/wp-admin/css/common.css',
			'/wp-admin/css/common.min.css',
			'/wp-admin/css/customize-controls-rtl.css',
			'/wp-admin/css/customize-controls-rtl.min.css',
			'/wp-admin/css/customize-controls.css',
			'/wp-admin/css/customize-controls.min.css',
			'/wp-admin/css/customize-nav-menus-rtl.css',
			'/wp-admin/css/customize-nav-menus-rtl.min.css',
			'/wp-admin/css/customize-nav-menus.css',
			'/wp-admin/css/customize-nav-menus.min.css',
			'/wp-admin/css/customize-widgets-rtl.css',
			'/wp-admin/css/customize-widgets-rtl.min.css',
			'/wp-admin/css/customize-widgets.css',
			'/wp-admin/css/customize-widgets.min.css',
			'/wp-admin/css/dashboard-rtl.css',
			'/wp-admin/css/dashboard-rtl.min.css',
			'/wp-admin/css/dashboard.css',
			'/wp-admin/css/dashboard.min.css',
			'/wp-admin/css/deprecated-media-rtl.css',
			'/wp-admin/css/deprecated-media-rtl.min.css',
			'/wp-admin/css/deprecated-media.css',
			'/wp-admin/css/deprecated-media.min.css',
			'/wp-admin/css/edit-rtl.css',
			'/wp-admin/css/edit-rtl.min.css',
			'/wp-admin/css/edit.css',
			'/wp-admin/css/edit.min.css',
			'/wp-admin/css/farbtastic-rtl.css',
			'/wp-admin/css/farbtastic-rtl.min.css',
			'/wp-admin/css/farbtastic.css',
			'/wp-admin/css/farbtastic.min.css',
			'/wp-admin/css/forms-rtl.css',
			'/wp-admin/css/forms-rtl.min.css',
			'/wp-admin/css/forms.css',
			'/wp-admin/css/forms.min.css',
			'/wp-admin/css/ie-rtl.css',
			'/wp-admin/css/ie-rtl.min.css',
			'/wp-admin/css/ie.css',
			'/wp-admin/css/ie.min.css',
			'/wp-admin/css/install-rtl.css',
			'/wp-admin/css/install-rtl.min.css',
			'/wp-admin/css/install.css',
			'/wp-admin/css/install.min.css',
			'/wp-admin/css/l10n-rtl.css',
			'/wp-admin/css/l10n-rtl.min.css',
			'/wp-admin/css/l10n.css',
			'/wp-admin/css/l10n.min.css',
			'/wp-admin/css/list-tables-rtl.css',
			'/wp-admin/css/list-tables-rtl.min.css',
			'/wp-admin/css/list-tables.css',
			'/wp-admin/css/list-tables.min.css',
			'/wp-admin/css/login-rtl.css',
			'/wp-admin/css/login-rtl.min.css',
			'/wp-admin/css/login.css',
			'/wp-admin/css/login.min.css',
			'/wp-admin/css/media-rtl.css',
			'/wp-admin/css/media-rtl.min.css',
			'/wp-admin/css/media.css',
			'/wp-admin/css/media.min.css',
			'/wp-admin/css/nav-menus-rtl.css',
			'/wp-admin/css/nav-menus-rtl.min.css',
			'/wp-admin/css/nav-menus.css',
			'/wp-admin/css/nav-menus.min.css',
			'/wp-admin/css/revisions-rtl.css',
			'/wp-admin/css/revisions-rtl.min.css',
			'/wp-admin/css/revisions.css',
			'/wp-admin/css/revisions.min.css',
			'/wp-admin/css/site-health-rtl.css',
			'/wp-admin/css/site-health-rtl.min.css',
			'/wp-admin/css/site-health.css',
			'/wp-admin/css/site-health.min.css',
			'/wp-admin/css/site-icon-rtl.css',
			'/wp-admin/css/site-icon-rtl.min.css',
			'/wp-admin/css/site-icon.css',
			'/wp-admin/css/site-icon.min.css',
			'/wp-admin/css/themes-rtl.css',
			'/wp-admin/css/themes-rtl.min.css',
			'/wp-admin/css/themes.css',
			'/wp-admin/css/themes.min.css',
			'/wp-admin/css/widgets-rtl.css',
			'/wp-admin/css/widgets-rtl.min.css',
			'/wp-admin/css/widgets.css',
			'/wp-admin/css/widgets.min.css',
			'/wp-admin/css/wp-admin-rtl.css',
			'/wp-admin/css/wp-admin-rtl.min.css',
			'/wp-admin/css/wp-admin.css',
			'/wp-admin/css/wp-admin.min.css',
			'/wp-admin/images/bubble_bg-2x.gif',
			'/wp-admin/images/bubble_bg.gif',
			'/wp-admin/images/date-button-2x.gif',
			'/wp-admin/images/date-button.gif',
			'/wp-admin/images/loading.gif',
			'/wp-admin/images/media-button-image.gif',
			'/wp-admin/images/media-button-music.gif',
			'/wp-admin/images/media-button-other.gif',
			'/wp-admin/images/media-button-video.gif',
			'/wp-admin/images/resize-2x.gif',
			'/wp-admin/images/resize-rtl-2x.gif',
			'/wp-admin/images/resize-rtl.gif',
			'/wp-admin/images/resize.gif',
			'/wp-admin/images/sort-2x.gif',
			'/wp-admin/images/sort.gif',
			'/wp-admin/images/spinner-2x.gif',
			'/wp-admin/images/spinner.gif',
			'/wp-admin/images/wpspin_light-2x.gif',
			'/wp-admin/images/wpspin_light.gif',
			'/wp-admin/images/xit-2x.gif',
			'/wp-admin/images/xit.gif',
			'/wp-admin/images/align-center-2x.png',
			'/wp-admin/images/align-center.png',
			'/wp-admin/images/align-left-2x.png',
			'/wp-admin/images/align-left.png',
			'/wp-admin/images/align-none-2x.png',
			'/wp-admin/images/align-none.png',
			'/wp-admin/images/align-right-2x.png',
			'/wp-admin/images/align-right.png',
			'/wp-admin/images/arrows-2x.png',
			'/wp-admin/images/arrows.png',
			'/wp-admin/images/browser-rtl.png',
			'/wp-admin/images/browser.png',
			'/wp-admin/images/comment-grey-bubble-2x.png',
			'/wp-admin/images/comment-grey-bubble.png',
			'/wp-admin/images/generic.png',
			'/wp-admin/images/icons32-2x.png',
			'/wp-admin/images/icons32-vs-2x.png',
			'/wp-admin/images/icons32-vs.png',
			'/wp-admin/images/icons32.png',
			'/wp-admin/images/imgedit-icons-2x.png',
			'/wp-admin/images/imgedit-icons.png',
			'/wp-admin/images/list-2x.png',
			'/wp-admin/images/list.png',
			'/wp-admin/images/marker.png',
			'/wp-admin/images/mask.png',
			'/wp-admin/images/media-button-2x.png',
			'/wp-admin/images/media-button.png',
			'/wp-admin/images/menu-2x.png',
			'/wp-admin/images/menu-vs-2x.png',
			'/wp-admin/images/menu-vs.png',
			'/wp-admin/images/menu.png',
			'/wp-admin/images/no.png',
			'/wp-admin/images/post-formats-vs.png',
			'/wp-admin/images/post-formats.png',
			'/wp-admin/images/post-formats32-vs.png',
			'/wp-admin/images/post-formats32.png',
			'/wp-admin/images/se.png',
			'/wp-admin/images/stars-2x.png',
			'/wp-admin/images/stars.png',
			'/wp-admin/images/w-logo-blue.png',
			'/wp-admin/images/w-logo-white.png',
			'/wp-admin/images/wheel.png',
			'/wp-admin/images/wordpress-logo.png',
			'/wp-admin/images/yes.png',
			'/wp-admin/images/wordpress-logo-white.svg',
			'/wp-admin/images/wordpress-logo.svg',
			'/wp-admin/js/widgets/custom-html-widgets.js',
			'/wp-admin/js/widgets/custom-html-widgets.min.js',
			'/wp-admin/js/widgets/media-audio-widget.js',
			'/wp-admin/js/widgets/media-audio-widget.min.js',
			'/wp-admin/js/widgets/media-gallery-widget.js',
			'/wp-admin/js/widgets/media-gallery-widget.min.js',
			'/wp-admin/js/widgets/media-image-widget.js',
			'/wp-admin/js/widgets/media-image-widget.min.js',
			'/wp-admin/js/widgets/media-video-widget.js',
			'/wp-admin/js/widgets/media-video-widget.min.js',
			'/wp-admin/js/widgets/media-widgets.js',
			'/wp-admin/js/widgets/media-widgets.min.js',
			'/wp-admin/js/widgets/text-widgets.js',
			'/wp-admin/js/widgets/text-widgets.min.js',
			'/wp-admin/js/accordion.js',
			'/wp-admin/js/accordion.min.js',
			'/wp-admin/js/code-editor.js',
			'/wp-admin/js/code-editor.min.js',
			'/wp-admin/js/color-picker.js',
			'/wp-admin/js/color-picker.min.js',
			'/wp-admin/js/comment.js',
			'/wp-admin/js/comment.min.js',
			'/wp-admin/js/common.js',
			'/wp-admin/js/common.min.js',
			'/wp-admin/js/custom-background.js',
			'/wp-admin/js/custom-background.min.js',
			'/wp-admin/js/custom-header.js',
			'/wp-admin/js/customize-controls.js',
			'/wp-admin/js/customize-controls.min.js',
			'/wp-admin/js/customize-nav-menus.js',
			'/wp-admin/js/customize-nav-menus.min.js',
			'/wp-admin/js/customize-widgets.js',
			'/wp-admin/js/customize-widgets.min.js',
			'/wp-admin/js/dashboard.js',
			'/wp-admin/js/dashboard.min.js',
			'/wp-admin/js/edit-comments.js',
			'/wp-admin/js/edit-comments.min.js',
			'/wp-admin/js/editor-expand.js',
			'/wp-admin/js/editor-expand.min.js',
			'/wp-admin/js/editor.js',
			'/wp-admin/js/editor.min.js',
			'/wp-admin/js/farbtastic.js',
			'/wp-admin/js/gallery.js',
			'/wp-admin/js/gallery.min.js',
			'/wp-admin/js/image-edit.js',
			'/wp-admin/js/image-edit.min.js',
			'/wp-admin/js/inline-edit-post.js',
			'/wp-admin/js/inline-edit-post.min.js',
			'/wp-admin/js/inline-edit-tax.js',
			'/wp-admin/js/inline-edit-tax.min.js',
			'/wp-admin/js/iris.min.js',
			'/wp-admin/js/language-chooser.js',
			'/wp-admin/js/language-chooser.min.js',
			'/wp-admin/js/link.js',
			'/wp-admin/js/link.min.js',
			'/wp-admin/js/media-gallery.js',
			'/wp-admin/js/media-gallery.min.js',
			'/wp-admin/js/media-upload.js',
			'/wp-admin/js/media-upload.min.js',
			'/wp-admin/js/media.js',
			'/wp-admin/js/media.min.js',
			'/wp-admin/js/nav-menu.js',
			'/wp-admin/js/nav-menu.min.js',
			'/wp-admin/js/password-strength-meter.js',
			'/wp-admin/js/password-strength-meter.min.js',
			'/wp-admin/js/plugin-install.js',
			'/wp-admin/js/plugin-install.min.js',
			'/wp-admin/js/post.js',
			'/wp-admin/js/post.min.js',
			'/wp-admin/js/postbox.js',
			'/wp-admin/js/postbox.min.js',
			'/wp-admin/js/privacy-tools.js',
			'/wp-admin/js/privacy-tools.min.js',
			'/wp-admin/js/revisions.js',
			'/wp-admin/js/revisions.min.js',
			'/wp-admin/js/set-post-thumbnail.js',
			'/wp-admin/js/set-post-thumbnail.min.js',
			'/wp-admin/js/site-health.js',
			'/wp-admin/js/site-health.min.js',
			'/wp-admin/js/svg-painter.js',
			'/wp-admin/js/svg-painter.min.js',
			'/wp-admin/js/tags-box.js',
			'/wp-admin/js/tags-box.min.js',
			'/wp-admin/js/tags-suggest.js',
			'/wp-admin/js/tags-suggest.min.js',
			'/wp-admin/js/tags.js',
			'/wp-admin/js/tags.min.js',
			'/wp-admin/js/theme-plugin-editor.js',
			'/wp-admin/js/theme-plugin-editor.min.js',
			'/wp-admin/js/theme.js',
			'/wp-admin/js/theme.min.js',
			'/wp-admin/js/updates.js',
			'/wp-admin/js/updates.min.js',
			'/wp-admin/js/user-profile.js',
			'/wp-admin/js/user-profile.min.js',
			'/wp-admin/js/user-suggest.js',
			'/wp-admin/js/user-suggest.min.js',
			'/wp-admin/js/widgets.js',
			'/wp-admin/js/widgets.min.js',
			'/wp-admin/js/word-count.js',
			'/wp-admin/js/word-count.min.js',
			'/wp-admin/js/wp-fullscreen-stub.js',
			'/wp-admin/js/wp-fullscreen-stub.min.js',
			'/wp-admin/js/xfn.js',
			'/wp-admin/js/xfn.min.js',
			'/wp-includes/ID3/license.commercial.txt',
			'/wp-includes/ID3/license.txt',
			'/wp-includes/ID3/readme.txt',
			'/wp-includes/css/dist/block-editor/style-rtl.css',
			'/wp-includes/css/dist/block-editor/style-rtl.min.css',
			'/wp-includes/css/dist/block-editor/style.css',
			'/wp-includes/css/dist/block-editor/style.min.css',
			'/wp-includes/css/dist/block-library/editor-rtl.css',
			'/wp-includes/css/dist/block-library/editor-rtl.min.css',
			'/wp-includes/css/dist/block-library/editor.css',
			'/wp-includes/css/dist/block-library/editor.min.css',
			'/wp-includes/css/dist/block-library/style-rtl.css',
			'/wp-includes/css/dist/block-library/style-rtl.min.css',
			'/wp-includes/css/dist/block-library/style.css',
			'/wp-includes/css/dist/block-library/style.min.css',
			'/wp-includes/css/dist/block-library/theme-rtl.css',
			'/wp-includes/css/dist/block-library/theme-rtl.min.css',
			'/wp-includes/css/dist/block-library/theme.css',
			'/wp-includes/css/dist/block-library/theme.min.css',
			'/wp-includes/css/dist/components/style-rtl.css',
			'/wp-includes/css/dist/components/style-rtl.min.css',
			'/wp-includes/css/dist/components/style.css',
			'/wp-includes/css/dist/components/style.min.css',
			'/wp-includes/css/dist/edit-post/style-rtl.css',
			'/wp-includes/css/dist/edit-post/style-rtl.min.css',
			'/wp-includes/css/dist/edit-post/style.css',
			'/wp-includes/css/dist/edit-post/style.min.css',
			'/wp-includes/css/dist/editor/editor-styles-rtl.css',
			'/wp-includes/css/dist/editor/editor-styles-rtl.min.css',
			'/wp-includes/css/dist/editor/editor-styles.css',
			'/wp-includes/css/dist/editor/editor-styles.min.css',
			'/wp-includes/css/dist/editor/style-rtl.css',
			'/wp-includes/css/dist/editor/style-rtl.min.css',
			'/wp-includes/css/dist/editor/style.css',
			'/wp-includes/css/dist/editor/style.min.css',
			'/wp-includes/css/dist/format-library/style-rtl.css',
			'/wp-includes/css/dist/format-library/style-rtl.min.css',
			'/wp-includes/css/dist/format-library/style.css',
			'/wp-includes/css/dist/format-library/style.min.css',
			'/wp-includes/css/dist/list-reusable-blocks/style-rtl.css',
			'/wp-includes/css/dist/list-reusable-blocks/style-rtl.min.css',
			'/wp-includes/css/dist/list-reusable-blocks/style.css',
			'/wp-includes/css/dist/list-reusable-blocks/style.min.css',
			'/wp-includes/css/dist/nux/style-rtl.css',
			'/wp-includes/css/dist/nux/style-rtl.min.css',
			'/wp-includes/css/dist/nux/style.css',
			'/wp-includes/css/dist/nux/style.min.css',
			'/wp-includes/css/admin-bar-rtl.css',
			'/wp-includes/css/admin-bar-rtl.min.css',
			'/wp-includes/css/admin-bar.css',
			'/wp-includes/css/admin-bar.min.css',
			'/wp-includes/css/buttons-rtl.css',
			'/wp-includes/css/buttons-rtl.min.css',
			'/wp-includes/css/buttons.css',
			'/wp-includes/css/buttons.min.css',
			'/wp-includes/css/customize-preview-rtl.css',
			'/wp-includes/css/customize-preview-rtl.min.css',
			'/wp-includes/css/customize-preview.css',
			'/wp-includes/css/customize-preview.min.css',
			'/wp-includes/css/dashicons.css',
			'/wp-includes/css/dashicons.min.css',
			'/wp-includes/css/editor-rtl.css',
			'/wp-includes/css/editor-rtl.min.css',
			'/wp-includes/css/editor.css',
			'/wp-includes/css/editor.min.css',
			'/wp-includes/css/jquery-ui-dialog-rtl.css',
			'/wp-includes/css/jquery-ui-dialog-rtl.min.css',
			'/wp-includes/css/jquery-ui-dialog.css',
			'/wp-includes/css/jquery-ui-dialog.min.css',
			'/wp-includes/css/media-views-rtl.css',
			'/wp-includes/css/media-views-rtl.min.css',
			'/wp-includes/css/media-views.css',
			'/wp-includes/css/media-views.min.css',
			'/wp-includes/css/wp-auth-check-rtl.css',
			'/wp-includes/css/wp-auth-check-rtl.min.css',
			'/wp-includes/css/wp-auth-check.css',
			'/wp-includes/css/wp-auth-check.min.css',
			'/wp-includes/css/wp-embed-template-ie.css',
			'/wp-includes/css/wp-embed-template-ie.min.css',
			'/wp-includes/css/wp-embed-template.css',
			'/wp-includes/css/wp-embed-template.min.css',
			'/wp-includes/css/wp-pointer-rtl.css',
			'/wp-includes/css/wp-pointer-rtl.min.css',
			'/wp-includes/css/wp-pointer.css',
			'/wp-includes/css/wp-pointer.min.css',
			'/wp-includes/fonts/dashicons.ttf',
			'/wp-includes/fonts/dashicons.woff',
			'/wp-includes/fonts/dashicons.woff2',
			'/wp-includes/fonts/dashicons.eot',
			'/wp-includes/fonts/dashicons.svg',
			'/wp-includes/images/crystal/license.txt',
			'/wp-includes/images/crystal/archive.png',
			'/wp-includes/images/crystal/audio.png',
			'/wp-includes/images/crystal/code.png',
			'/wp-includes/images/crystal/default.png',
			'/wp-includes/images/crystal/document.png',
			'/wp-includes/images/crystal/interactive.png',
			'/wp-includes/images/crystal/spreadsheet.png',
			'/wp-includes/images/crystal/text.png',
			'/wp-includes/images/crystal/video.png',
			'/wp-includes/images/media/archive.png',
			'/wp-includes/images/media/audio.png',
			'/wp-includes/images/media/code.png',
			'/wp-includes/images/media/default.png',
			'/wp-includes/images/media/document.png',
			'/wp-includes/images/media/interactive.png',
			'/wp-includes/images/media/spreadsheet.png',
			'/wp-includes/images/media/text.png',
			'/wp-includes/images/media/video.png',
			'/wp-includes/images/smilies/icon_arrow.gif',
			'/wp-includes/images/smilies/icon_biggrin.gif',
			'/wp-includes/images/smilies/icon_confused.gif',
			'/wp-includes/images/smilies/icon_cool.gif',
			'/wp-includes/images/smilies/icon_cry.gif',
			'/wp-includes/images/smilies/icon_eek.gif',
			'/wp-includes/images/smilies/icon_evil.gif',
			'/wp-includes/images/smilies/icon_exclaim.gif',
			'/wp-includes/images/smilies/icon_idea.gif',
			'/wp-includes/images/smilies/icon_lol.gif',
			'/wp-includes/images/smilies/icon_mad.gif',
			'/wp-includes/images/smilies/icon_mrgreen.gif',
			'/wp-includes/images/smilies/icon_neutral.gif',
			'/wp-includes/images/smilies/icon_question.gif',
			'/wp-includes/images/smilies/icon_razz.gif',
			'/wp-includes/images/smilies/icon_redface.gif',
			'/wp-includes/images/smilies/icon_rolleyes.gif',
			'/wp-includes/images/smilies/icon_sad.gif',
			'/wp-includes/images/smilies/icon_smile.gif',
			'/wp-includes/images/smilies/icon_surprised.gif',
			'/wp-includes/images/smilies/icon_twisted.gif',
			'/wp-includes/images/smilies/icon_wink.gif',
			'/wp-includes/images/smilies/frownie.png',
			'/wp-includes/images/smilies/mrgreen.png',
			'/wp-includes/images/smilies/rolleyes.png',
			'/wp-includes/images/smilies/simple-smile.png',
			'/wp-includes/images/wlw/wp-comments.png',
			'/wp-includes/images/wlw/wp-icon.png',
			'/wp-includes/images/wlw/wp-watermark.png',
			'/wp-includes/images/blank.gif',
			'/wp-includes/images/down_arrow-2x.gif',
			'/wp-includes/images/down_arrow.gif',
			'/wp-includes/images/spinner-2x.gif',
			'/wp-includes/images/spinner.gif',
			'/wp-includes/images/wpspin-2x.gif',
			'/wp-includes/images/wpspin.gif',
			'/wp-includes/images/xit-2x.gif',
			'/wp-includes/images/xit.gif',
			'/wp-includes/images/admin-bar-sprite-2x.png',
			'/wp-includes/images/admin-bar-sprite.png',
			'/wp-includes/images/arrow-pointer-blue-2x.png',
			'/wp-includes/images/arrow-pointer-blue.png',
			'/wp-includes/images/icon-pointer-flag-2x.png',
			'/wp-includes/images/icon-pointer-flag.png',
			'/wp-includes/images/rss-2x.png',
			'/wp-includes/images/rss.png',
			'/wp-includes/images/toggle-arrow-2x.png',
			'/wp-includes/images/toggle-arrow.png',
			'/wp-includes/images/uploader-icons-2x.png',
			'/wp-includes/images/uploader-icons.png',
			'/wp-includes/images/w-logo-blue.png',
			'/wp-includes/images/wpicons-2x.png',
			'/wp-includes/images/wpicons.png',
			'/wp-includes/js/codemirror/codemirror.min.css',
			'/wp-includes/js/codemirror/codemirror.min.js',
			'/wp-includes/js/codemirror/csslint.js',
			'/wp-includes/js/codemirror/esprima.js',
			'/wp-includes/js/codemirror/fakejshint.js',
			'/wp-includes/js/codemirror/htmlhint-kses.js',
			'/wp-includes/js/codemirror/htmlhint.js',
			'/wp-includes/js/codemirror/jsonlint.js',
			'/wp-includes/js/crop/cropper.css',
			'/wp-includes/js/crop/cropper.js',
			'/wp-includes/js/crop/marqueeHoriz.gif',
			'/wp-includes/js/crop/marqueeVert.gif',
			'/wp-includes/js/dist/vendor/lodash.js',
			'/wp-includes/js/dist/vendor/lodash.min.js',
			'/wp-includes/js/dist/vendor/moment.js',
			'/wp-includes/js/dist/vendor/moment.min.js',
			'/wp-includes/js/dist/vendor/react-dom.js',
			'/wp-includes/js/dist/vendor/react-dom.min.js',
			'/wp-includes/js/dist/vendor/react.js',
			'/wp-includes/js/dist/vendor/react.min.js',
			'/wp-includes/js/dist/vendor/wp-polyfill-element-closest.js',
			'/wp-includes/js/dist/vendor/wp-polyfill-element-closest.min.js',
			'/wp-includes/js/dist/vendor/wp-polyfill-fetch.js',
			'/wp-includes/js/dist/vendor/wp-polyfill-fetch.min.js',
			'/wp-includes/js/dist/vendor/wp-polyfill-formdata.js',
			'/wp-includes/js/dist/vendor/wp-polyfill-formdata.min.js',
			'/wp-includes/js/dist/vendor/wp-polyfill-node-contains.js',
			'/wp-includes/js/dist/vendor/wp-polyfill-node-contains.min.js',
			'/wp-includes/js/dist/vendor/wp-polyfill.js',
			'/wp-includes/js/dist/vendor/wp-polyfill.min.js',
			'/wp-includes/js/dist/a11y.js',
			'/wp-includes/js/dist/a11y.min.js',
			'/wp-includes/js/dist/annotations.js',
			'/wp-includes/js/dist/annotations.min.js',
			'/wp-includes/js/dist/api-fetch.js',
			'/wp-includes/js/dist/api-fetch.min.js',
			'/wp-includes/js/dist/autop.js',
			'/wp-includes/js/dist/autop.min.js',
			'/wp-includes/js/dist/blob.js',
			'/wp-includes/js/dist/blob.min.js',
			'/wp-includes/js/dist/block-editor.js',
			'/wp-includes/js/dist/block-editor.min.js',
			'/wp-includes/js/dist/block-library.js',
			'/wp-includes/js/dist/block-library.min.js',
			'/wp-includes/js/dist/block-serialization-default-parser.js',
			'/wp-includes/js/dist/block-serialization-default-parser.min.js',
			'/wp-includes/js/dist/blocks.js',
			'/wp-includes/js/dist/blocks.min.js',
			'/wp-includes/js/dist/components.js',
			'/wp-includes/js/dist/components.min.js',
			'/wp-includes/js/dist/compose.js',
			'/wp-includes/js/dist/compose.min.js',
			'/wp-includes/js/dist/core-data.js',
			'/wp-includes/js/dist/core-data.min.js',
			'/wp-includes/js/dist/data-controls.js',
			'/wp-includes/js/dist/data-controls.min.js',
			'/wp-includes/js/dist/data.js',
			'/wp-includes/js/dist/data.min.js',
			'/wp-includes/js/dist/date.js',
			'/wp-includes/js/dist/date.min.js',
			'/wp-includes/js/dist/deprecated.js',
			'/wp-includes/js/dist/deprecated.min.js',
			'/wp-includes/js/dist/dom-ready.js',
			'/wp-includes/js/dist/dom-ready.min.js',
			'/wp-includes/js/dist/dom.js',
			'/wp-includes/js/dist/dom.min.js',
			'/wp-includes/js/dist/edit-post.js',
			'/wp-includes/js/dist/edit-post.min.js',
			'/wp-includes/js/dist/editor.js',
			'/wp-includes/js/dist/editor.min.js',
			'/wp-includes/js/dist/element.js',
			'/wp-includes/js/dist/element.min.js',
			'/wp-includes/js/dist/escape-html.js',
			'/wp-includes/js/dist/escape-html.min.js',
			'/wp-includes/js/dist/format-library.js',
			'/wp-includes/js/dist/format-library.min.js',
			'/wp-includes/js/dist/hooks.js',
			'/wp-includes/js/dist/hooks.min.js',
			'/wp-includes/js/dist/html-entities.js',
			'/wp-includes/js/dist/html-entities.min.js',
			'/wp-includes/js/dist/i18n.js',
			'/wp-includes/js/dist/i18n.min.js',
			'/wp-includes/js/dist/is-shallow-equal.js',
			'/wp-includes/js/dist/is-shallow-equal.min.js',
			'/wp-includes/js/dist/keycodes.js',
			'/wp-includes/js/dist/keycodes.min.js',
			'/wp-includes/js/dist/list-reusable-blocks.js',
			'/wp-includes/js/dist/list-reusable-blocks.min.js',
			'/wp-includes/js/dist/media-utils.js',
			'/wp-includes/js/dist/media-utils.min.js',
			'/wp-includes/js/dist/notices.js',
			'/wp-includes/js/dist/notices.min.js',
			'/wp-includes/js/dist/nux.js',
			'/wp-includes/js/dist/nux.min.js',
			'/wp-includes/js/dist/plugins.js',
			'/wp-includes/js/dist/plugins.min.js',
			'/wp-includes/js/dist/priority-queue.js',
			'/wp-includes/js/dist/priority-queue.min.js',
			'/wp-includes/js/dist/redux-routine.js',
			'/wp-includes/js/dist/redux-routine.min.js',
			'/wp-includes/js/dist/rich-text.js',
			'/wp-includes/js/dist/rich-text.min.js',
			'/wp-includes/js/dist/server-side-render.js',
			'/wp-includes/js/dist/server-side-render.min.js',
			'/wp-includes/js/dist/shortcode.js',
			'/wp-includes/js/dist/shortcode.min.js',
			'/wp-includes/js/dist/token-list.js',
			'/wp-includes/js/dist/token-list.min.js',
			'/wp-includes/js/dist/url.js',
			'/wp-includes/js/dist/url.min.js',
			'/wp-includes/js/dist/viewport.js',
			'/wp-includes/js/dist/viewport.min.js',
			'/wp-includes/js/dist/wordcount.js',
			'/wp-includes/js/dist/wordcount.min.js',
			'/wp-includes/js/imgareaselect/imgareaselect.css',
			'/wp-includes/js/imgareaselect/jquery.imgareaselect.js',
			'/wp-includes/js/imgareaselect/jquery.imgareaselect.min.js',
			'/wp-includes/js/imgareaselect/border-anim-h.gif',
			'/wp-includes/js/imgareaselect/border-anim-v.gif',
			'/wp-includes/js/jcrop/jquery.Jcrop.min.css',
			'/wp-includes/js/jcrop/jquery.Jcrop.min.js',
			'/wp-includes/js/jcrop/Jcrop.gif',
			'/wp-includes/js/jquery/ui/accordion.min.js',
			'/wp-includes/js/jquery/ui/autocomplete.min.js',
			'/wp-includes/js/jquery/ui/button.min.js',
			'/wp-includes/js/jquery/ui/core.min.js',
			'/wp-includes/js/jquery/ui/datepicker.min.js',
			'/wp-includes/js/jquery/ui/dialog.min.js',
			'/wp-includes/js/jquery/ui/draggable.min.js',
			'/wp-includes/js/jquery/ui/droppable.min.js',
			'/wp-includes/js/jquery/ui/effect-blind.min.js',
			'/wp-includes/js/jquery/ui/effect-bounce.min.js',
			'/wp-includes/js/jquery/ui/effect-clip.min.js',
			'/wp-includes/js/jquery/ui/effect-drop.min.js',
			'/wp-includes/js/jquery/ui/effect-explode.min.js',
			'/wp-includes/js/jquery/ui/effect-fade.min.js',
			'/wp-includes/js/jquery/ui/effect-fold.min.js',
			'/wp-includes/js/jquery/ui/effect-highlight.min.js',
			'/wp-includes/js/jquery/ui/effect-puff.min.js',
			'/wp-includes/js/jquery/ui/effect-pulsate.min.js',
			'/wp-includes/js/jquery/ui/effect-scale.min.js',
			'/wp-includes/js/jquery/ui/effect-shake.min.js',
			'/wp-includes/js/jquery/ui/effect-size.min.js',
			'/wp-includes/js/jquery/ui/effect-slide.min.js',
			'/wp-includes/js/jquery/ui/effect-transfer.min.js',
			'/wp-includes/js/jquery/ui/effect.min.js',
			'/wp-includes/js/jquery/ui/menu.min.js',
			'/wp-includes/js/jquery/ui/mouse.min.js',
			'/wp-includes/js/jquery/ui/position.min.js',
			'/wp-includes/js/jquery/ui/progressbar.min.js',
			'/wp-includes/js/jquery/ui/resizable.min.js',
			'/wp-includes/js/jquery/ui/selectable.min.js',
			'/wp-includes/js/jquery/ui/selectmenu.min.js',
			'/wp-includes/js/jquery/ui/slider.min.js',
			'/wp-includes/js/jquery/ui/sortable.min.js',
			'/wp-includes/js/jquery/ui/spinner.min.js',
			'/wp-includes/js/jquery/ui/tabs.min.js',
			'/wp-includes/js/jquery/ui/tooltip.min.js',
			'/wp-includes/js/jquery/ui/widget.min.js',
			'/wp-includes/js/jquery/jquery-migrate.js',
			'/wp-includes/js/jquery/jquery-migrate.min.js',
			'/wp-includes/js/jquery/jquery.color.min.js',
			'/wp-includes/js/jquery/jquery.form.js',
			'/wp-includes/js/jquery/jquery.form.min.js',
			'/wp-includes/js/jquery/jquery.hotkeys.js',
			'/wp-includes/js/jquery/jquery.hotkeys.min.js',
			'/wp-includes/js/jquery/jquery.js',
			'/wp-includes/js/jquery/jquery.masonry.min.js',
			'/wp-includes/js/jquery/jquery.query.js',
			'/wp-includes/js/jquery/jquery.schedule.js',
			'/wp-includes/js/jquery/jquery.serialize-object.js',
			'/wp-includes/js/jquery/jquery.table-hotkeys.js',
			'/wp-includes/js/jquery/jquery.table-hotkeys.min.js',
			'/wp-includes/js/jquery/jquery.ui.touch-punch.js',
			'/wp-includes/js/jquery/suggest.js',
			'/wp-includes/js/jquery/suggest.min.js',
			'/wp-includes/js/mediaelement/renderers/vimeo.js',
			'/wp-includes/js/mediaelement/renderers/vimeo.min.js',
			'/wp-includes/js/mediaelement/mediaelementplayer-legacy.css',
			'/wp-includes/js/mediaelement/mediaelementplayer-legacy.min.css',
			'/wp-includes/js/mediaelement/mediaelementplayer.css',
			'/wp-includes/js/mediaelement/mediaelementplayer.min.css',
			'/wp-includes/js/mediaelement/wp-mediaelement.css',
			'/wp-includes/js/mediaelement/wp-mediaelement.min.css',
			'/wp-includes/js/mediaelement/mediaelement-and-player.js',
			'/wp-includes/js/mediaelement/mediaelement-and-player.min.js',
			'/wp-includes/js/mediaelement/mediaelement-migrate.js',
			'/wp-includes/js/mediaelement/mediaelement-migrate.min.js',
			'/wp-includes/js/mediaelement/mediaelement.js',
			'/wp-includes/js/mediaelement/mediaelement.min.js',
			'/wp-includes/js/mediaelement/wp-mediaelement.js',
			'/wp-includes/js/mediaelement/wp-mediaelement.min.js',
			'/wp-includes/js/mediaelement/wp-playlist.js',
			'/wp-includes/js/mediaelement/wp-playlist.min.js',
			'/wp-includes/js/mediaelement/mejs-controls.png',
			'/wp-includes/js/mediaelement/mejs-controls.svg',
			'/wp-includes/js/plupload/license.txt',
			'/wp-includes/js/plupload/handlers.js',
			'/wp-includes/js/plupload/handlers.min.js',
			'/wp-includes/js/plupload/moxie.js',
			'/wp-includes/js/plupload/moxie.min.js',
			'/wp-includes/js/plupload/plupload.js',
			'/wp-includes/js/plupload/plupload.min.js',
			'/wp-includes/js/plupload/wp-plupload.js',
			'/wp-includes/js/plupload/wp-plupload.min.js',
			'/wp-includes/js/swfupload/license.txt',
			'/wp-includes/js/swfupload/handlers.js',
			'/wp-includes/js/swfupload/handlers.min.js',
			'/wp-includes/js/swfupload/swfupload.js',
			'/wp-includes/js/thickbox/thickbox.css',
			'/wp-includes/js/thickbox/thickbox.js',
			'/wp-includes/js/thickbox/loadingAnimation.gif',
			'/wp-includes/js/thickbox/macFFBgHack.png',
			'/wp-includes/js/tinymce/langs/wp-langs-en.js',
			'/wp-includes/js/tinymce/plugins/charmap/plugin.js',
			'/wp-includes/js/tinymce/plugins/charmap/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/colorpicker/plugin.js',
			'/wp-includes/js/tinymce/plugins/colorpicker/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/compat3x/css/dialog.css',
			'/wp-includes/js/tinymce/plugins/compat3x/plugin.js',
			'/wp-includes/js/tinymce/plugins/compat3x/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/directionality/plugin.js',
			'/wp-includes/js/tinymce/plugins/directionality/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/fullscreen/plugin.js',
			'/wp-includes/js/tinymce/plugins/fullscreen/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/hr/plugin.js',
			'/wp-includes/js/tinymce/plugins/hr/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/image/plugin.js',
			'/wp-includes/js/tinymce/plugins/image/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/link/plugin.js',
			'/wp-includes/js/tinymce/plugins/link/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/lists/plugin.js',
			'/wp-includes/js/tinymce/plugins/lists/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/media/plugin.js',
			'/wp-includes/js/tinymce/plugins/media/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/paste/plugin.js',
			'/wp-includes/js/tinymce/plugins/paste/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/tabfocus/plugin.js',
			'/wp-includes/js/tinymce/plugins/tabfocus/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/textcolor/plugin.js',
			'/wp-includes/js/tinymce/plugins/textcolor/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/wordpress/plugin.js',
			'/wp-includes/js/tinymce/plugins/wordpress/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/wpautoresize/plugin.js',
			'/wp-includes/js/tinymce/plugins/wpautoresize/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/wpdialogs/plugin.js',
			'/wp-includes/js/tinymce/plugins/wpdialogs/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/wpeditimage/plugin.js',
			'/wp-includes/js/tinymce/plugins/wpeditimage/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/wpemoji/plugin.js',
			'/wp-includes/js/tinymce/plugins/wpemoji/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/wpgallery/plugin.js',
			'/wp-includes/js/tinymce/plugins/wpgallery/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/wplink/plugin.js',
			'/wp-includes/js/tinymce/plugins/wplink/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/wptextpattern/plugin.js',
			'/wp-includes/js/tinymce/plugins/wptextpattern/plugin.min.js',
			'/wp-includes/js/tinymce/plugins/wpview/plugin.js',
			'/wp-includes/js/tinymce/plugins/wpview/plugin.min.js',
			'/wp-includes/js/tinymce/skins/lightgray/fonts/tinymce-small.ttf',
			'/wp-includes/js/tinymce/skins/lightgray/fonts/tinymce.ttf',
			'/wp-includes/js/tinymce/skins/lightgray/fonts/tinymce-small.woff',
			'/wp-includes/js/tinymce/skins/lightgray/fonts/tinymce.woff',
			'/wp-includes/js/tinymce/skins/lightgray/fonts/tinymce-small.eot',
			'/wp-includes/js/tinymce/skins/lightgray/fonts/tinymce.eot',
			'/wp-includes/js/tinymce/skins/lightgray/fonts/tinymce-small.svg',
			'/wp-includes/js/tinymce/skins/lightgray/fonts/tinymce.svg',
			'/wp-includes/js/tinymce/skins/lightgray/img/anchor.gif',
			'/wp-includes/js/tinymce/skins/lightgray/img/loader.gif',
			'/wp-includes/js/tinymce/skins/lightgray/img/object.gif',
			'/wp-includes/js/tinymce/skins/lightgray/img/trans.gif',
			'/wp-includes/js/tinymce/skins/lightgray/content.inline.min.css',
			'/wp-includes/js/tinymce/skins/lightgray/content.min.css',
			'/wp-includes/js/tinymce/skins/lightgray/skin.min.css',
			'/wp-includes/js/tinymce/skins/wordpress/images/audio.png',
			'/wp-includes/js/tinymce/skins/wordpress/images/dashicon-edit.png',
			'/wp-includes/js/tinymce/skins/wordpress/images/dashicon-no.png',
			'/wp-includes/js/tinymce/skins/wordpress/images/embedded.png',
			'/wp-includes/js/tinymce/skins/wordpress/images/gallery-2x.png',
			'/wp-includes/js/tinymce/skins/wordpress/images/gallery.png',
			'/wp-includes/js/tinymce/skins/wordpress/images/more-2x.png',
			'/wp-includes/js/tinymce/skins/wordpress/images/more.png',
			'/wp-includes/js/tinymce/skins/wordpress/images/pagebreak-2x.png',
			'/wp-includes/js/tinymce/skins/wordpress/images/pagebreak.png',
			'/wp-includes/js/tinymce/skins/wordpress/images/playlist-audio.png',
			'/wp-includes/js/tinymce/skins/wordpress/images/playlist-video.png',
			'/wp-includes/js/tinymce/skins/wordpress/images/video.png',
			'/wp-includes/js/tinymce/skins/wordpress/wp-content.css',
			'/wp-includes/js/tinymce/themes/inlite/theme.js',
			'/wp-includes/js/tinymce/themes/inlite/theme.min.js',
			'/wp-includes/js/tinymce/themes/modern/theme.js',
			'/wp-includes/js/tinymce/themes/modern/theme.min.js',
			'/wp-includes/js/tinymce/utils/editable_selects.js',
			'/wp-includes/js/tinymce/utils/form_utils.js',
			'/wp-includes/js/tinymce/utils/mctabs.js',
			'/wp-includes/js/tinymce/utils/validate.js',
			'/wp-includes/js/tinymce/license.txt',
			'/wp-includes/js/tinymce/tiny_mce_popup.js',
			'/wp-includes/js/tinymce/tinymce.min.js',
			'/wp-includes/js/tinymce/wp-tinymce.js',
			'/wp-includes/js/admin-bar.js',
			'/wp-includes/js/admin-bar.min.js',
			'/wp-includes/js/api-request.js',
			'/wp-includes/js/api-request.min.js',
			'/wp-includes/js/autosave.js',
			'/wp-includes/js/autosave.min.js',
			'/wp-includes/js/backbone.js',
			'/wp-includes/js/backbone.min.js',
			'/wp-includes/js/clipboard.js',
			'/wp-includes/js/clipboard.min.js',
			'/wp-includes/js/colorpicker.js',
			'/wp-includes/js/colorpicker.min.js',
			'/wp-includes/js/comment-reply.js',
			'/wp-includes/js/comment-reply.min.js',
			'/wp-includes/js/customize-base.js',
			'/wp-includes/js/customize-base.min.js',
			'/wp-includes/js/customize-loader.js',
			'/wp-includes/js/customize-loader.min.js',
			'/wp-includes/js/customize-models.js',
			'/wp-includes/js/customize-models.min.js',
			'/wp-includes/js/customize-preview-nav-menus.js',
			'/wp-includes/js/customize-preview-nav-menus.min.js',
			'/wp-includes/js/customize-preview-widgets.js',
			'/wp-includes/js/customize-preview-widgets.min.js',
			'/wp-includes/js/customize-preview.js',
			'/wp-includes/js/customize-preview.min.js',
			'/wp-includes/js/customize-selective-refresh.js',
			'/wp-includes/js/customize-selective-refresh.min.js',
			'/wp-includes/js/customize-views.js',
			'/wp-includes/js/customize-views.min.js',
			'/wp-includes/js/heartbeat.js',
			'/wp-includes/js/heartbeat.min.js',
			'/wp-includes/js/hoverIntent.js',
			'/wp-includes/js/hoverIntent.min.js',
			'/wp-includes/js/hoverintent-js.min.js',
			'/wp-includes/js/imagesloaded.min.js',
			'/wp-includes/js/json2.js',
			'/wp-includes/js/json2.min.js',
			'/wp-includes/js/masonry.min.js',
			'/wp-includes/js/mce-view.js',
			'/wp-includes/js/mce-view.min.js',
			'/wp-includes/js/media-audiovideo.js',
			'/wp-includes/js/media-audiovideo.min.js',
			'/wp-includes/js/media-editor.js',
			'/wp-includes/js/media-editor.min.js',
			'/wp-includes/js/media-grid.js',
			'/wp-includes/js/media-grid.min.js',
			'/wp-includes/js/media-models.js',
			'/wp-includes/js/media-models.min.js',
			'/wp-includes/js/media-views.js',
			'/wp-includes/js/media-views.min.js',
			'/wp-includes/js/quicktags.js',
			'/wp-includes/js/quicktags.min.js',
			'/wp-includes/js/shortcode.js',
			'/wp-includes/js/shortcode.min.js',
			'/wp-includes/js/swfobject.js',
			'/wp-includes/js/tw-sack.js',
			'/wp-includes/js/tw-sack.min.js',
			'/wp-includes/js/twemoji.js',
			'/wp-includes/js/twemoji.min.js',
			'/wp-includes/js/underscore.js',
			'/wp-includes/js/underscore.min.js',
			'/wp-includes/js/utils.js',
			'/wp-includes/js/utils.min.js',
			'/wp-includes/js/wp-ajax-response.js',
			'/wp-includes/js/wp-ajax-response.min.js',
			'/wp-includes/js/wp-api.js',
			'/wp-includes/js/wp-api.min.js',
			'/wp-includes/js/wp-auth-check.js',
			'/wp-includes/js/wp-auth-check.min.js',
			'/wp-includes/js/wp-backbone.js',
			'/wp-includes/js/wp-backbone.min.js',
			'/wp-includes/js/wp-custom-header.js',
			'/wp-includes/js/wp-custom-header.min.js',
			'/wp-includes/js/wp-embed-template.js',
			'/wp-includes/js/wp-embed-template.min.js',
			'/wp-includes/js/wp-embed.js',
			'/wp-includes/js/wp-embed.min.js',
			'/wp-includes/js/wp-emoji-loader.js',
			'/wp-includes/js/wp-emoji-loader.min.js',
			'/wp-includes/js/wp-emoji-release.min.js',
			'/wp-includes/js/wp-emoji.js',
			'/wp-includes/js/wp-emoji.min.js',
			'/wp-includes/js/wp-list-revisions.js',
			'/wp-includes/js/wp-list-revisions.min.js',
			'/wp-includes/js/wp-lists.js',
			'/wp-includes/js/wp-lists.min.js',
			'/wp-includes/js/wp-pointer.js',
			'/wp-includes/js/wp-pointer.min.js',
			'/wp-includes/js/wp-sanitize.js',
			'/wp-includes/js/wp-sanitize.min.js',
			'/wp-includes/js/wp-util.js',
			'/wp-includes/js/wp-util.min.js',
			'/wp-includes/js/wpdialog.js',
			'/wp-includes/js/wpdialog.min.js',
			'/wp-includes/js/wplink.js',
			'/wp-includes/js/wplink.min.js',
			'/wp-includes/js/zxcvbn-async.js',
			'/wp-includes/js/zxcvbn-async.min.js',
			'/wp-includes/js/zxcvbn.min.js',
			'/wp-includes/wlwmanifest.xml',
			'/wp-content/plugins/akismet/_inc/img/logo-full-2x.png',
			'/wp-content/plugins/akismet/_inc/akismet.css',
			'/wp-content/plugins/akismet/_inc/akismet.js',
			'/wp-content/plugins/akismet/_inc/form.js',
			'/wp-content/plugins/akismet/LICENSE.txt',
			'/wp-content/plugins/akismet/readme.txt',
			'/wp-content/themes/twentynineteen/fonts/NonBreakingSpaceOverride.woff',
			'/wp-content/themes/twentynineteen/fonts/NonBreakingSpaceOverride.woff2',
			'/wp-content/themes/twentynineteen/js/customize-controls.js',
			'/wp-content/themes/twentynineteen/js/customize-preview.js',
			'/wp-content/themes/twentynineteen/js/priority-menu.js',
			'/wp-content/themes/twentynineteen/js/skip-link-focus-fix.js',
			'/wp-content/themes/twentynineteen/js/touch-keyboard-navigation.js',
			'/wp-content/themes/twentynineteen/readme.txt',
			'/wp-content/themes/twentynineteen/print.css',
			'/wp-content/themes/twentynineteen/style-editor-customizer.css',
			'/wp-content/themes/twentynineteen/style-editor.css',
			'/wp-content/themes/twentynineteen/style-rtl.css',
			'/wp-content/themes/twentynineteen/style.css',
			'/wp-content/themes/twentynineteen/postcss.config.js',
			'/wp-content/themes/twentynineteen/screenshot.png',
			'/wp-content/themes/twentyseventeen/assets/css/blocks.css',
			'/wp-content/themes/twentyseventeen/assets/css/colors-dark.css',
			'/wp-content/themes/twentyseventeen/assets/css/editor-blocks.css',
			'/wp-content/themes/twentyseventeen/assets/css/editor-style.css',
			'/wp-content/themes/twentyseventeen/assets/css/ie8.css',
			'/wp-content/themes/twentyseventeen/assets/css/ie9.css',
			'/wp-content/themes/twentyseventeen/assets/images/coffee.jpg',
			'/wp-content/themes/twentyseventeen/assets/images/espresso.jpg',
			'/wp-content/themes/twentyseventeen/assets/images/header.jpg',
			'/wp-content/themes/twentyseventeen/assets/images/sandwich.jpg',
			'/wp-content/themes/twentyseventeen/assets/images/svg-icons.svg',
			'/wp-content/themes/twentyseventeen/assets/js/customize-controls.js',
			'/wp-content/themes/twentyseventeen/assets/js/customize-preview.js',
			'/wp-content/themes/twentyseventeen/assets/js/global.js',
			'/wp-content/themes/twentyseventeen/assets/js/html5.js',
			'/wp-content/themes/twentyseventeen/assets/js/jquery.scrollTo.js',
			'/wp-content/themes/twentyseventeen/assets/js/navigation.js',
			'/wp-content/themes/twentyseventeen/assets/js/skip-link-focus-fix.js',
			'/wp-content/themes/twentyseventeen/readme.txt',
			'/wp-content/themes/twentyseventeen/rtl.css',
			'/wp-content/themes/twentyseventeen/style.css',
			'/wp-content/themes/twentyseventeen/screenshot.png',
			'/wp-content/themes/twentysixteen/css/blocks.css',
			'/wp-content/themes/twentysixteen/css/editor-blocks.css',
			'/wp-content/themes/twentysixteen/css/editor-style.css',
			'/wp-content/themes/twentysixteen/css/ie.css',
			'/wp-content/themes/twentysixteen/css/ie7.css',
			'/wp-content/themes/twentysixteen/css/ie8.css',
			'/wp-content/themes/twentysixteen/genericons/COPYING.txt',
			'/wp-content/themes/twentysixteen/genericons/LICENSE.txt',
			'/wp-content/themes/twentysixteen/genericons/genericons.css',
			'/wp-content/themes/twentysixteen/genericons/Genericons.ttf',
			'/wp-content/themes/twentysixteen/genericons/Genericons.woff',
			'/wp-content/themes/twentysixteen/genericons/Genericons.eot',
			'/wp-content/themes/twentysixteen/genericons/Genericons.svg',
			'/wp-content/themes/twentysixteen/js/color-scheme-control.js',
			'/wp-content/themes/twentysixteen/js/customize-preview.js',
			'/wp-content/themes/twentysixteen/js/functions.js',
			'/wp-content/themes/twentysixteen/js/html5.js',
			'/wp-content/themes/twentysixteen/js/keyboard-image-navigation.js',
			'/wp-content/themes/twentysixteen/js/skip-link-focus-fix.js',
			'/wp-content/themes/twentysixteen/readme.txt',
			'/wp-content/themes/twentysixteen/rtl.css',
			'/wp-content/themes/twentysixteen/style.css',
			'/wp-content/themes/twentysixteen/screenshot.png',
			'/wp-content/themes/twentytwenty/assets/css/editor-style-block-rtl.css',
			'/wp-content/themes/twentytwenty/assets/css/editor-style-block.css',
			'/wp-content/themes/twentytwenty/assets/css/editor-style-classic-rtl.css',
			'/wp-content/themes/twentytwenty/assets/css/editor-style-classic.css',
			'/wp-content/themes/twentytwenty/assets/fonts/inter/Inter-italic-var.woff2',
			'/wp-content/themes/twentytwenty/assets/fonts/inter/Inter-upright-var.woff2',
			'/wp-content/themes/twentytwenty/assets/images/2020-landscape-1.png',
			'/wp-content/themes/twentytwenty/assets/images/2020-landscape-2.png',
			'/wp-content/themes/twentytwenty/assets/images/2020-square-1.png',
			'/wp-content/themes/twentytwenty/assets/images/2020-square-2.png',
			'/wp-content/themes/twentytwenty/assets/images/2020-three-quarters-1.png',
			'/wp-content/themes/twentytwenty/assets/images/2020-three-quarters-2.png',
			'/wp-content/themes/twentytwenty/assets/images/2020-three-quarters-3.png',
			'/wp-content/themes/twentytwenty/assets/images/2020-three-quarters-4.png',
			'/wp-content/themes/twentytwenty/assets/js/color-calculations.js',
			'/wp-content/themes/twentytwenty/assets/js/customize-controls.js',
			'/wp-content/themes/twentytwenty/assets/js/customize-preview.js',
			'/wp-content/themes/twentytwenty/assets/js/customize.js',
			'/wp-content/themes/twentytwenty/assets/js/editor-script-block.js',
			'/wp-content/themes/twentytwenty/assets/js/index.js',
			'/wp-content/themes/twentytwenty/assets/js/skip-link-focus-fix.js',
			'/wp-content/themes/twentytwenty/readme.txt',
			'/wp-content/themes/twentytwenty/print.css',
			'/wp-content/themes/twentytwenty/style-rtl.css',
			'/wp-content/themes/twentytwenty/style.css',
			'/wp-content/themes/twentytwenty/screenshot.png',
			'/wp-content/uploads/2020/03/test.txt',
		);
		$expect      = array();
		foreach ( $expect_urls as $expect_url ) {
			$expect[] = array(
				'type'          => 'static_file',
				'url'           => $expect_url,
				'last_modified' => DATE_FOR_TEST,
			);
		}
		return $expect;
	}
	/**
	 * Asserts URLs.
	 * 
	 * @param array $expect Expect URLs.
	 * @param array $actual Actual URLs.
	 */
	private function assert_urls( $expect, $actual ) {
		$length_expect = count( $expect );
		$this->assertEquals( $length_expect, count( $actual ) );
		for ( $index = 0; $index < $length_expect; $index ++ ) {
			$expect_url = $expect[ $index ];
			$actual_url = $actual[ $index ];
			$this->assertEquals(
				array_key_exists( 'last_modified', $expect_url ),
				array_key_exists( 'last_modified', $actual_url ),
				'Existance of last_modified is not same. Index = ' . $index
			);
			if ( array_key_exists( 'last_modified', $actual_url ) ) {
				if ( is_null( $actual_url['last_modified'] ) ) {
					$this->assertNull( $actual_url['last_modified'] );
				} else {
					$this->assertRegExp(
						'/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/i',
						$actual_url['last_modified'],
						'$actual_url[\last_modified\'] is not mutch regex \'/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/i\'. Index = ' . $index
					);
				}
			}
			unset( $expect_url['last_modified'] );
			unset( $actual_url['last_modified'] );
			$this->assertEquals( $expect_url, $actual_url );
		}
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
	 * Function test_fetch_start_time() should return fetch_start_time in transient_key
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
	 * Function get_transient_key() should return appropriate string when current user id is not set.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_get_transient_key() {
		$result = $this->create_accessable_method( 'get_transient_key', array() );
		$this->assertEquals( 'static static', $result );
	}

	/**
	 * Function get_transient_key() should return appropriate string when current user id is set.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_get_transient_key_current_user() {
		wp_set_current_user( 1 );
		$result = $this->create_accessable_method( 'get_transient_key', array() );
		$this->assertEquals( 'static static - 1', $result );
	}

	/**
	 * Creates accessable method.
	 * 
	 * @param string $method_name     Method name.
	 * @param array  $array_parameter Array of parameter.
	 */
	private function create_accessable_method( $method_name, $array_parameter ) {
		$static_press = new Static_Press( 'staticpress' );
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $static_press, $array_parameter );
	}
	/**
	 * Creates response.
	 * 
	 * @param string $url       URL.
	 * @param string $file_name File name.
	 * @return array Responce.
	 */
	private function create_response( $url, $file_name ) {
		$body        = file_get_contents( dirname( __FILE__ ) . '/../testresources/' . $file_name );
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

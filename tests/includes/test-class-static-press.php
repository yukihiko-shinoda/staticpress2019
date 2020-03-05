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
	 * Function front_page_url() should return appropriate URLs.
	 */
	public function test_front_page_url() {
		$expect        = array(
			array(
				'type'          => 'front_page',
				'url'           => '/',
				'last_modified' => '2019-12-23 12:34:56',
			),
		);
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
	 * Function single_url() should return URLs of posts.
	 * Function single_url() should return number of pages by split post content by nextpage tag.
	 */
	public function test_single_url() {
		$expect = array(
			array(
				'type'        => 'single',
				'url'         => '/?attachment_id=4/',
				'object_id'   => 4,
				'object_type' => 'attachment',
				'pages'       => 1,
			),
			array(
				'type'        => 'single',
				'url'         => '/?attachment_id=5/',
				'object_id'   => 5,
				'object_type' => 'attachment',
				'pages'       => 3,
			),
		);
		wp_insert_post(
			array(
				'post_title'   => '',
				'post_content' => '',
				'post_status'  => 'publish',
				'post_type'    => 'attachment',
			)
		);
		wp_insert_post(
			array(
				'post_title'   => '',
				'post_content' => 'test<!--nextpage-->test<!--nextpage-->test',
				'post_status'  => 'publish',
				'post_type'    => 'attachment',
			)
		);
		$actual = $this->create_accessable_method( 'single_url', array() );
		$this->assert_urls( $expect, $actual );
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
			$this->assertRegExp( '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/i', $actual_url['last_modified'] );
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

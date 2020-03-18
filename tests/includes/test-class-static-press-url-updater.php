<?php
/**
 * Class Static_Press_Url_Updater_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-expect-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-repository-for-test.php';
use static_press\includes\Static_Press_Ajax_Init;
use static_press\includes\Static_Press_Repository;
use static_press\includes\Static_Press_Url_Updater;
use static_press\tests\testlibraries\Expect_Url;
use static_press\tests\testlibraries\Model_Url;
use static_press\tests\testlibraries\Repository_For_Test;

/**
 * Static_Press_Url_Updater test case.
 */
class Static_Press_Url_Updater_Test extends \WP_UnitTestCase {
	const OUTPUT_DIRECTORY = '/tmp/static/';
	/**
	 * Function update() should update enable when URL exists in database table.
	 * Function update() should insert URL when URL doesn't exist in database table.
	 * Function update() should save as disable when URL is PHP file.
	 * Function update() should save as disable when URL is get request with parameter.
	 * Function update() should save as disable when URL is WordPress admin home page.
	 * Function update() should save as disable when URL is readme.
	 * Function update() should save as disable when URL is not exist.
	 * Function update() should save as enable when URL is activated plugin's static file.
	 * Function update() should save as disable when URL is not current theme's static file.
	 * Function update() should save as enable when URL is current theme's static file.
	 * 
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_update() {
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
				Model_Url::TYPE_OTHER_PAGE,
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
		$repository  = new Static_Press_Repository();
		$url_updater = new Static_Press_Url_Updater( $repository, null );
		$url_updater->update( $urls );
		$static_press = new Static_Press_Ajax_Init(
			null,
			null,
			$repository,
			null
		);
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'fetch_start_time' );
		$method->setAccessible( true );
		$start_time = $method->invokeArgs( $static_press, array() );
		$results    = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function update() should save as disable when dump directory is same with absolute path.
	 */
	public function test_update_case_dump_directory_is_absolute_path() {
		$urls                    = array(
			array(
				'url'  => '/',
				'type' => Model_Url::TYPE_STATIC_FILE,
			),
		);
		$expect_urls_in_database = array();
		$repository              = new Static_Press_Repository();
		$url_updater             = new Static_Press_Url_Updater( $repository, ABSPATH );
		$url_updater->update( $urls );
		$static_press = new Static_Press_Ajax_Init(
			null,
			ABSPATH,
			$repository,
			null
		);
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'fetch_start_time' );
		$method->setAccessible( true );
		$start_time = $method->invokeArgs( $static_press, array() );
		$results    = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function update() should save as disable when file is not updated after last dump.
	 */
	public function test_update_case_non_update_file() {
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
		$repository              = new Static_Press_Repository();
		$url_updater             = new Static_Press_Url_Updater( $repository, self::OUTPUT_DIRECTORY );
		$url_updater->update( $urls );
		$static_press = new Static_Press_Ajax_Init(
			null,
			self::OUTPUT_DIRECTORY,
			$repository,
			null
		);
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'fetch_start_time' );
		$method->setAccessible( true );
		$start_time = $method->invokeArgs( $static_press, array() );
		$results    = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function update() should save as disable when URL is not activated plugin's static file.
	 */
	public function test_update_case_non_active_plugin_static_file() {
		deactivate_plugins( array( 'akismet/akismet.php' ) );
		$urls                    = array(
			array(
				'url'  => '/wp-content/plugins/akismet/_inc/akismet.css',
				'type' => Model_Url::TYPE_STATIC_FILE,
			),
		);
		$expect_urls_in_database = array();
		$repository              = new Static_Press_Repository();
		$url_updater             = new Static_Press_Url_Updater( $repository, null );
		$url_updater->update( $urls );
		$static_press = new Static_Press_Ajax_Init(
			null,
			null,
			$repository,
			null
		);
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'fetch_start_time' );
		$method->setAccessible( true );
		$start_time = $method->invokeArgs( $static_press, array() );
		$repository = new Static_Press_Repository();
		$results    = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function update() should save as disable when URL is not activated plugin's static file.
	 */
	public function test_update_case_static_file_not_plugin_nor_theme() {
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
		$repository              = new Static_Press_Repository();
		$url_updater             = new Static_Press_Url_Updater( $repository, null );
		$url_updater->update( $urls );
		$static_press = new Static_Press_Ajax_Init(
			null,
			null,
			$repository,
			null
		);
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'fetch_start_time' );
		$method->setAccessible( true );
		$start_time = $method->invokeArgs( $static_press, array() );
		$repository = new Static_Press_Repository();
		$results    = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}
}

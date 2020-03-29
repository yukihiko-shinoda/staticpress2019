<?php
/**
 * Class Static_Press_Url_Updater_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-environment.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-expect-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-repository-for-test.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-test-utility.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-theme-switcher.php';
use Mockery;
use static_press\includes\Static_Press_Model_Url;
use static_press\includes\Static_Press_Model_Url_Static_File;
use static_press\includes\Static_Press_Model_Url_Succeed;
use static_press\includes\Static_Press_Repository;
use static_press\includes\Static_Press_Transient_Service;
use static_press\includes\Static_Press_Url_Updater;
use static_press\tests\testlibraries\Environment;
use static_press\tests\testlibraries\Expect_Url;
use static_press\tests\testlibraries\Model_Url;
use static_press\tests\testlibraries\Repository_For_Test;
use static_press\tests\testlibraries\Test_Utility;
use static_press\tests\testlibraries\Theme_Switcher;

/**
 * Static_Press_Url_Updater test case.
 */
class Static_Press_Url_Updater_Test extends \WP_UnitTestCase {
	const DATE_FOR_TEST = '2019-12-23 12:34:56';
	/**
	 * Puts up output directory, Mockery.
	 */
	public function tearDown() {
		Test_Utility::delete_files();
		Mockery::close();
		parent::tearDown();
	}

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
		Repository_For_Test::truncate_table();
		Repository_For_Test::insert_url(
			new Model_Url(
				1,
				Static_Press_Model_Url::TYPE_OTHER_PAGE,
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
		$theme_switcher = new Theme_Switcher();
		activate_plugin( 'akismet/akismet.php' );
		$date_time_factory = Test_Utility::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' );
		$urls              = array(
			new Static_Press_Model_Url_Succeed( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test/', null, null, null, $date_time_factory ),
			new Static_Press_Model_Url_Succeed( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/', null, null, null, $date_time_factory ),
			new Static_Press_Model_Url_Succeed( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test.php', null, null, null, $date_time_factory ),
			new Static_Press_Model_Url_Succeed( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test?parameter=value', null, null, null, $date_time_factory ),
			new Static_Press_Model_Url_Succeed( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/wp-admin/', null, null, null, $date_time_factory ),
			Test_Utility::create_static_file_readme(),
			Test_Utility::create_static_file_not_exist(),
			Test_Utility::create_static_file_active_plugin(),
			$theme_switcher->create_static_file_active_theme(),
			$theme_switcher->create_static_file_theme_parent_activated(),
			$theme_switcher->create_static_file_non_active_theme(),
		);
		$repository        = new Static_Press_Repository();
		$url_updater       = new Static_Press_Url_Updater( $repository, null, Test_Utility::create_docuemnt_root_getter_mock() );
		$url_updater->update( $urls );
		$expect_urls_in_database = array(
			new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
			new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
			new Expect_Url(
				Static_Press_Model_Url::TYPE_STATIC_FILE,
				'/' . Environment::DIRECTORY_NAME_WORD_PRESS . '/wp-content/plugins/akismet/_inc/akismet.css',
				'1'
			),
			new Expect_Url(
				Static_Press_Model_Url::TYPE_STATIC_FILE,
				'/' . Environment::DIRECTORY_NAME_WORD_PRESS . "/wp-content/themes/{$theme_switcher->theme_to_activate}/style.css",
				'1'
			),
			new Expect_Url(
				Static_Press_Model_Url::TYPE_STATIC_FILE,
				'/' . Environment::DIRECTORY_NAME_WORD_PRESS . "/wp-content/themes/{$theme_switcher->theme_parent_activated}/style.css",
				'1'
			),
		);
		$results                 = $repository->get_all_url( '2019-12-23 12:34:57' );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function update() should save as disable when dump directory is same with absolute path.
	 */
	public function test_update_case_dump_directory_is_absolute_path() {
		$urls                    = array(
			new Static_Press_Model_Url_Static_File( Static_Press_Model_Url::TYPE_STATIC_FILE, '/', '/' ),
		);
		$expect_urls_in_database = array();
		$repository              = new Static_Press_Repository();
		$url_updater             = new Static_Press_Url_Updater( $repository, ABSPATH );
		$url_updater->update( $urls );
		$transient_service = new Static_Press_Transient_Service();
		$start_time        = $transient_service->fetch_start_time();
		$results           = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function update() should save as disable when file is not updated after last dump.
	 */
	public function test_update_case_non_update_file() {
		$urls                    = array(
			Test_Utility::create_static_file_not_updated(),
		);
		$expect_urls_in_database = array();
		$repository              = new Static_Press_Repository();
		$url_updater             = new Static_Press_Url_Updater( $repository, Test_Utility::OUTPUT_DIRECTORY );
		$url_updater->update( $urls );
		$transient_service = new Static_Press_Transient_Service();
		$start_time        = $transient_service->fetch_start_time();
		$results           = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function update() should save as disable when URL is not activated plugin's static file.
	 */
	public function test_update_case_non_active_plugin_static_file() {
		$urls                    = array( Test_Utility::create_static_file_non_active_plugin() );
		$expect_urls_in_database = array();
		$repository              = new Static_Press_Repository();
		$url_updater             = new Static_Press_Url_Updater( $repository, null );
		$url_updater->update( $urls );
		$transient_service = new Static_Press_Transient_Service();
		$start_time        = $transient_service->fetch_start_time();
		$results           = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function update() should save as disable when URL is not activated plugin's static file nor theme's static file.
	 */
	public function test_update_case_static_file_not_plugin_nor_theme() {
		$urls                    = array( Test_Utility::create_static_file_not_plugin_nor_theme() );
		$expect_urls_in_database = array(
			new Expect_Url(
				Static_Press_Model_Url::TYPE_STATIC_FILE,
				'/' . Environment::DIRECTORY_NAME_WORD_PRESS . '/wp-content/uploads/2020/03/test.txt',
				'1'
			),
		);
		$repository              = new Static_Press_Repository();
		$url_updater             = new Static_Press_Url_Updater( $repository, null, Test_Utility::create_docuemnt_root_getter_mock() );
		$url_updater->update( $urls );
		$transient_service = new Static_Press_Transient_Service();
		$start_time        = $transient_service->fetch_start_time();
		$results           = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}
}

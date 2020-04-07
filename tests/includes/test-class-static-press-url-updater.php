<?php
/**
 * Class Static_Press_Url_Updater_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/infrastructure/class-environment.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/infrastructure/class-file-system-operator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-mock-creator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-static-file-creator-for-test.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/repositories/class-repository-for-test.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-expect-url.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-model-url.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-theme-switcher.php';
use Mockery;
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\models\Static_Press_Model_Url_Static_File;
use static_press\includes\models\Static_Press_Model_Url_Succeed;
use static_press\includes\repositories\Static_Press_Repository;
use static_press\includes\repositories\Static_Press_Repository_Progress;
use static_press\includes\Static_Press_Url_Updater;
use static_press\tests\testlibraries\infrastructure\Environment;
use static_press\tests\testlibraries\infrastructure\File_System_Operator;
use static_press\tests\testlibraries\creators\Mock_Creator;
use static_press\tests\testlibraries\creators\Static_File_Creator_For_Test;
use static_press\tests\testlibraries\repositories\Repository_For_Test;
use static_press\tests\testlibraries\Expect_Url;
use static_press\tests\testlibraries\Model_Url;
use static_press\tests\testlibraries\Theme_Switcher;
/**
 * Static_Press_Url_Updater test case.
 */
class Static_Press_Url_Updater_Test extends \WP_UnitTestCase {
	/**
	 * Puts up output directory, Mockery.
	 */
	public function tearDown() {
		File_System_Operator::delete_files();
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
		$date_time_factory = Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' );
		$urls              = array(
			new Static_Press_Model_Url_Succeed( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test/', null, null, null, $date_time_factory ),
			new Static_Press_Model_Url_Succeed( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/', null, null, null, $date_time_factory ),
			new Static_Press_Model_Url_Succeed( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test.php', null, null, null, $date_time_factory ),
			new Static_Press_Model_Url_Succeed( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test?parameter=value', null, null, null, $date_time_factory ),
			new Static_Press_Model_Url_Succeed( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/wp-admin/', null, null, null, $date_time_factory ),
			Static_File_Creator_For_Test::create_static_file_readme(),
			Static_File_Creator_For_Test::create_static_file_not_exist(),
			Static_File_Creator_For_Test::create_static_file_active_plugin(),
			$theme_switcher->create_static_file_active_theme(),
			$theme_switcher->create_static_file_theme_parent_activated(),
			$theme_switcher->create_static_file_non_active_theme(),
		);
		$repository        = new Static_Press_Repository();
		$url_updater       = new Static_Press_Url_Updater( $repository, null, Mock_Creator::create_docuemnt_root_getter_mock() );
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
		$transient_service = new Static_Press_Repository_Progress();
		$start_time        = $transient_service->fetch_start_time();
		$results           = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function update() should save as disable when file is not updated after last dump.
	 */
	public function test_update_case_non_update_file() {
		$urls                    = array( Static_File_Creator_For_Test::create_static_file_not_updated() );
		$expect_urls_in_database = array();
		$repository              = new Static_Press_Repository();
		$url_updater             = new Static_Press_Url_Updater( $repository, File_System_Operator::OUTPUT_DIRECTORY );
		$url_updater->update( $urls );
		$transient_service = new Static_Press_Repository_Progress();
		$start_time        = $transient_service->fetch_start_time();
		$results           = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function update() should save as disable when URL is not activated plugin's static file.
	 */
	public function test_update_case_non_active_plugin_static_file() {
		$urls                    = array( Static_File_Creator_For_Test::create_static_file_non_active_plugin() );
		$expect_urls_in_database = array();
		$repository              = new Static_Press_Repository();
		$url_updater             = new Static_Press_Url_Updater( $repository, null );
		$url_updater->update( $urls );
		$transient_service = new Static_Press_Repository_Progress();
		$start_time        = $transient_service->fetch_start_time();
		$results           = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function update() should save as disable when URL is not activated plugin's static file nor theme's static file.
	 */
	public function test_update_case_static_file_not_plugin_nor_theme() {
		$urls                    = array( Static_File_Creator_For_Test::create_static_file_not_plugin_nor_theme() );
		$expect_urls_in_database = array(
			new Expect_Url(
				Static_Press_Model_Url::TYPE_STATIC_FILE,
				'/' . Environment::DIRECTORY_NAME_WORD_PRESS . '/wp-content/uploads/2020/03/test.txt',
				'1'
			),
		);
		$repository              = new Static_Press_Repository();
		$url_updater             = new Static_Press_Url_Updater( $repository, null, Mock_Creator::create_docuemnt_root_getter_mock() );
		$url_updater->update( $urls );
		$transient_service = new Static_Press_Repository_Progress();
		$start_time        = $transient_service->fetch_start_time();
		$results           = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}
}

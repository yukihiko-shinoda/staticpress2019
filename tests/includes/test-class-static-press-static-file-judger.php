<?php
/**
 * Class Static_Press_Static_FIle_Jugder_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-polyfill-wp-unittestcase.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-mock-creator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-static-file-creator-for-test.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/infrastructure/class-file-system-operator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-theme-switcher.php';

use Mockery;
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\models\Static_Press_Model_Url_Static_File;
use static_press\includes\Static_Press_Static_FIle_Judger;
use static_press\tests\testlibraries\creators\Mock_Creator;
use static_press\tests\testlibraries\creators\Static_File_Creator_For_Test;
use static_press\tests\testlibraries\infrastructure\File_System_Operator;
use static_press\tests\testlibraries\Polyfill_WP_UnitTestCase;
use static_press\tests\testlibraries\Theme_Switcher;

/**
 * Static_Press_Static_FIle_Jugder test case.
 */
class Static_Press_Static_FIle_Jugder_Test extends Polyfill_WP_UnitTestCase {
	/**
	 * Theme switcher.
	 * 
	 * @var Theme_Switcher
	 */
	private $theme_switcher;
	/**
	 * Constructor.
	 * 
	 * @param string $name      Name.
	 * @param array  $data      Data.
	 * @param string $data_name Data name.
	 */
	public function __construct( $name = null, $data = array(), $data_name = '' ) {
		parent::__construct( $name, $data, $data_name );
		$this->theme_switcher = new Theme_Switcher();
	}
	/**
	 * Ensure file existance.
	 * Activbate plugin and theme.
	 */
	public function set_up() {
		parent::set_up();
		Static_File_Creator_For_Test::create_static_file_readme();
		Static_File_Creator_For_Test::create_static_file_not_exist();
		Static_File_Creator_For_Test::create_static_file_not_updated();
		Static_File_Creator_For_Test::create_static_file_active_plugin();
		Static_File_Creator_For_Test::create_static_file_not_plugin_nor_theme();
		Static_File_Creator_For_Test::create_content_file_not_plugin_nor_theme();
		$this->theme_switcher->create_static_file_active_theme();
		$this->theme_switcher->switch_theme();
	}

	/**
	 * Puts up output directory, Mockery.
	 */
	public function tear_down() {
		File_System_Operator::delete_files();
		File_System_Operator::delete_files( ABSPATH . 'wp-content/uploads/' );
		File_System_Operator::delete_files( WP_CONTENT_DIR . '/app/uploads/' );
		Mockery::close();
		parent::tear_down();
	}

	/**
	 * Test step for classify().
	 * 
	 * @dataProvider provider_classify
	 * @param Static_Press_Model_Url_Static_File $url    URL.
	 * @param integer                            $expect Expect.
	 */
	public function test_classify( $url, $expect ) {
		$static_file_judger = new Static_Press_Static_FIle_Judger( File_System_Operator::OUTPUT_DIRECTORY, Mock_Creator::create_docuemnt_root_getter_mock() );
		$this->assertEquals( $expect, $static_file_judger->classify( $url ) );
	}

	/**
	 * Function classify() should save as disable when URL is readme.
	 * Function classify() should save as disable when URL is not exist.
	 * Function classify() should save as enable when URL is activated plugin's static file.
	 * Function classify() should save as enable when URL is current theme's static file.
	 * Function classify() should save as disable when URL is not current theme's static file.
	 */
	public function provider_classify() {
		return array(
			array( Static_File_Creator_For_Test::create_static_file_readme(), 0 ),
			array( Static_File_Creator_For_Test::create_static_file_not_exist(), 0 ),
			array( Static_File_Creator_For_Test::create_static_file_active_plugin(), 1 ),
			array( $this->theme_switcher->create_static_file_active_theme(), 1 ),
			array( $this->theme_switcher->create_static_file_theme_parent_activated(), 1 ),
			array( $this->theme_switcher->create_static_file_non_active_theme(), 0 ),
		);
	}

	/**
	 * Function classify() should save as disable when dump directory is same with absolute path.
	 */
	public function test_classify_case_dump_directory_is_absolute_path() {
		$static_file_judger = new Static_Press_Static_FIle_Judger( ABSPATH, Mock_Creator::create_docuemnt_root_getter_mock() );
		$url                = new Static_Press_Model_Url_Static_File( Static_Press_Model_Url::TYPE_STATIC_FILE, '/', '/' );
		$this->assertEquals( 0, $static_file_judger->classify( $url ) );
	}

	/**
	 * Function classify() should save as disable when file is not updated after last dump.
	 */
	public function test_classify_case_non_update_file() {
		$static_file_judger = new Static_Press_Static_FIle_Judger( File_System_Operator::OUTPUT_DIRECTORY, Mock_Creator::create_docuemnt_root_getter_mock() );
		$url                = Static_File_Creator_For_Test::create_static_file_not_updated();
		$this->assertEquals( 0, $static_file_judger->classify( $url ) );
	}

	/**
	 * Function classify() should save as disable when URL is not activated plugin's static file.
	 */
	public function test_classify_case_non_active_plugin_static_file() {
		$static_file_judger = new Static_Press_Static_FIle_Judger( File_System_Operator::OUTPUT_DIRECTORY, Mock_Creator::create_docuemnt_root_getter_mock() );
		$url                = Static_File_Creator_For_Test::create_static_file_non_active_plugin();
		$this->assertEquals( 0, $static_file_judger->classify( $url ) );
	}

	/**
	 * Test step for classify().
	 * 
	 * @dataProvider provider_classify_case_static_file_not_plugin_nor_theme
	 * @param Static_Press_Model_Url_Static_File $url URL.
	 */
	public function test_classify_case_static_file_not_plugin_nor_theme( $url ) {
		$static_file_judger = new Static_Press_Static_FIle_Judger( File_System_Operator::OUTPUT_DIRECTORY, Mock_Creator::create_docuemnt_root_getter_mock() );
		$this->assertEquals( 1, $static_file_judger->classify( $url ) );
	}

	/**
	 * Function classify() should save as disable when URL is not activated plugin's static file nor theme's static file.
	 */
	public function provider_classify_case_static_file_not_plugin_nor_theme() {
		return array(
			array( Static_File_Creator_For_Test::create_static_file_not_plugin_nor_theme() ),
			array( Static_File_Creator_For_Test::create_content_file_not_plugin_nor_theme() ),
		);
	}
}

<?php
/**
 * Class Static_Press_Static_FIle_Jugder_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-test-utility.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-theme-switcher.php';

use Mockery;
use static_press\includes\Static_Press_Model_Url;
use static_press\includes\Static_Press_Model_Url_Static_File;
use static_press\includes\Static_Press_Static_FIle_Judger;
use static_press\tests\testlibraries\Test_Utility;
use static_press\tests\testlibraries\Theme_Switcher;

/**
 * Static_Press_Static_FIle_Jugder test case.
 */
class Static_Press_Static_FIle_Jugder_Test extends \WP_UnitTestCase {
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
	public function setUp() {
		parent::setUp();
		Test_Utility::create_static_file_readme();
		Test_Utility::create_static_file_not_exist();
		Test_Utility::create_static_file_not_updated();
		Test_Utility::create_static_file_active_plugin();
		Test_Utility::create_static_file_not_plugin_nor_theme();
		Test_Utility::create_content_file_not_plugin_nor_theme();
		$this->theme_switcher->switch_theme();
	}

	/**
	 * Puts up output directory, Mockery.
	 */
	public function tearDown() {
		Test_Utility::delete_files();
		Test_Utility::delete_files( ABSPATH . 'wp-content/uploads/' );
		Test_Utility::delete_files( WP_CONTENT_DIR . '/app/uploads/' );
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test step for classify().
	 * 
	 * @dataProvider provider_classify
	 * @param Static_Press_Model_Url_Static_File $url    URL.
	 * @param integer                            $expect Expect.
	 */
	public function test_classify( $url, $expect ) {
		$static_file_judger = new Static_Press_Static_FIle_Judger( Test_Utility::OUTPUT_DIRECTORY, Test_Utility::create_docuemnt_root_getter_mock() );
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
			array( Test_Utility::create_static_file_readme(), 0 ),
			array( Test_Utility::create_static_file_not_exist(), 0 ),
			array( Test_Utility::create_static_file_active_plugin(), 1 ),
			array( $this->theme_switcher->create_static_file_active_theme(), 1 ),
			array( $this->theme_switcher->create_static_file_non_active_theme(), 0 ),
		);
	}

	/**
	 * Function classify() should save as disable when dump directory is same with absolute path.
	 */
	public function test_classify_case_dump_directory_is_absolute_path() {
		$static_file_judger = new Static_Press_Static_FIle_Judger( ABSPATH, Test_Utility::create_docuemnt_root_getter_mock() );
		$url                = new Static_Press_Model_Url_Static_File( Static_Press_Model_Url::TYPE_STATIC_FILE, '/', '/' );
		$this->assertEquals( 0, $static_file_judger->classify( $url ) );
	}

	/**
	 * Function classify() should save as disable when file is not updated after last dump.
	 */
	public function test_classify_case_non_update_file() {
		$static_file_judger = new Static_Press_Static_FIle_Judger( Test_Utility::OUTPUT_DIRECTORY, Test_Utility::create_docuemnt_root_getter_mock() );
		$url                = Test_Utility::create_static_file_not_updated();
		$this->assertEquals( 0, $static_file_judger->classify( $url ) );
	}

	/**
	 * Function classify() should save as disable when URL is not activated plugin's static file.
	 */
	public function test_classify_case_non_active_plugin_static_file() {
		$static_file_judger = new Static_Press_Static_FIle_Judger( Test_Utility::OUTPUT_DIRECTORY, Test_Utility::create_docuemnt_root_getter_mock() );
		$url                = Test_Utility::create_static_file_non_active_plugin();
		$this->assertEquals( 0, $static_file_judger->classify( $url ) );
	}

	/**
	 * Test step for classify().
	 * 
	 * @dataProvider provider_classify_case_static_file_not_plugin_nor_theme
	 * @param Static_Press_Model_Url_Static_File $url URL.
	 */
	public function test_classify_case_static_file_not_plugin_nor_theme( $url ) {
		$static_file_judger = new Static_Press_Static_FIle_Judger( Test_Utility::OUTPUT_DIRECTORY, Test_Utility::create_docuemnt_root_getter_mock() );
		$this->assertEquals( 1, $static_file_judger->classify( $url ) );
	}

	/**
	 * Function classify() should save as disable when URL is not activated plugin's static file nor theme's static file.
	 */
	public function provider_classify_case_static_file_not_plugin_nor_theme() {
		return array(
			array( Test_Utility::create_static_file_not_plugin_nor_theme() ),
			array( Test_Utility::create_content_file_not_plugin_nor_theme() ),
		);
	}
}

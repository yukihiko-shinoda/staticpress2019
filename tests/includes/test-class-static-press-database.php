<?php
/**
 * Class Static_Press_Database_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-die-exception.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-repository-for-test.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-test-utility.php';
use static_press\includes\Static_Press;
use static_press\includes\Static_Press_Model_Url;
use static_press\tests\testlibraries\Die_Exception;
use static_press\tests\testlibraries\Model_Url;
use static_press\tests\testlibraries\Repository_For_Test;
use static_press\tests\testlibraries\Test_Utility;

/**
 * StaticPress database test case.
 *
 * @noinspection PhpUndefinedClassInspection
 */
class Static_Press_Database_Test extends \WP_UnitTestCase {
	/**
	 * Remove filters.
	 * Running StaticPress test in separate process causes mysqli_errno(): Couldn't fetch mysqli.
	 * To fix, PHPUnit 6 is required, however, WordPress plugin test are only supported by PHPUnit 5.*...
	 * 
	 * @see https://stackoverflow.com/questions/45989601/wordpress-develop-unit-testing-couldnt-fetch-mysqli/51098542#51098542
	 * @see https://make.wordpress.org/cli/handbook/plugin-unit-tests/#running-tests-locally
	 */
	public function setUp() {
		parent::setUp();
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
	}
	/**
	 * Restore filters.
	 */
	public function tearDown() {
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		parent::tearDown();
	}
	/**
	 * Function activate() should ensure that database table which list URL exists.
	 */
	public function test_constructor_create_table() {
		Repository_For_Test::ensure_table_is_dropped();
		$this->assertFalse( Repository_For_Test::url_table_exists() );
		new Static_Press();
		$this->assertTrue( Repository_For_Test::url_table_exists() );
	}

	/**
	 * Function activate() should ensure that database table which list URL exists.
	 */
	public function test_activate() {
		$static_press = new Static_Press();
		Repository_For_Test::ensure_table_is_dropped();
		$this->assertFalse( Repository_For_Test::url_table_exists() );
		$static_press->activate();
		$this->assertTrue( Repository_For_Test::url_table_exists() );
	}

	/**
	 * Function activate() should ensure that database table which list URL has column 'enable'.
	 */
	public function test_activate_2() {
		Repository_For_Test::ensure_table_is_dropped();
		Repository_For_Test::create_legacy_table();
		$this->assertFalse( Repository_For_Test::column_enable_exists() );
		$static_press = new Static_Press();
		$static_press->activate();
		$this->assertTrue( Repository_For_Test::column_enable_exists() );
		$column = Repository_For_Test::get_column_enable();
		$this->assertEquals( 'enable', $column->Field );         // phpcs:ignore
		$this->assertEquals( 'int(1) unsigned', $column->Type ); // phpcs:ignore
		$this->assertEquals( 'NO', $column->Null );              // phpcs:ignore
		$this->assertEquals( '', $column->Key );                 // phpcs:ignore
		$this->assertEquals( '1', $column->Default );            // phpcs:ignore
		$this->assertEquals( '', $column->Extra );               // phpcs:ignore
	}

	/**
	 * Function activate() should ensure that database table which list URL exists.
	 */
	public function test_deactivate() {
		$static_press = new Static_Press();
		Repository_For_Test::ensure_table_is_created();
		$this->assertTrue( Repository_For_Test::url_table_exists() );
		$static_press->deactivate();
		$this->assertFalse( Repository_For_Test::url_table_exists() );
		Repository_For_Test::create_latest_table();
	}

	/**
	 * Function ajax_init() should die.
	 * 
	 * @runInSeparateProcess
	 */
	public function test_ajax_init() {
		$this->sign_on_to_word_press();
		$static_press = new Static_Press( '/', '', array(), null, Test_Utility::set_up_seo_url( 'http://example.org/' ) );
		ob_start();
		try {
			$static_press->ajax_init( Test_Utility::create_terminator_mock() );
		} catch ( Die_Exception $exception ) {
			$output = ob_get_clean();
			$this->assertEquals( 'Dead!', $exception->getMessage() );
			$array_json = json_decode( $output, true );
			$this->assertTrue( $array_json['result'] );
			$array_urls_count  = $array_json['urls_count'];
			$url_count_content = $array_urls_count[0];
			$this->assertEquals( 'content_file', $url_count_content['type'] );
			$this->assertGreaterThan( 0, $url_count_content['count'] );
			$url_count_front = $array_urls_count[1];
			$this->assertEquals( 'front_page', $url_count_front['type'] );
			$this->assertEquals( 1, $url_count_front['count'] );
			$url_count_seo = $array_urls_count[2];
			$this->assertEquals( 'seo_files', $url_count_seo['type'] );
			$this->assertEquals( 5, $url_count_seo['count'] );
			return;
		}
		$this->fail();
	}

	/**
	 * Function ajax_fetch() should die.
	 * 
	 * @runInSeparateProcess
	 */
	public function test_ajax_fetch_without_record() {
		$this->sign_on_to_word_press();

		$expect       = '{"result":false,"final":true}';
		$static_press = new Static_Press();
		ob_start();
		try {
			$static_press->ajax_fetch( Test_Utility::create_terminator_mock() );
		} catch ( Die_Exception $exception ) {
			$output = ob_get_clean();
			$this->assertEquals( 'Dead!', $exception->getMessage() );
			$this->assertEquals( $expect, $output );
			return;
		}
		$this->fail();
	}

	/**
	 * Test steps for ajax_fetch_with_record().
	 * Function ajax_fetch() should die.
	 * 
	 * @dataProvider provider_ajax_fetch_with_record
	 * 
	 * @param string $array_record   Array record.
	 * @param string $expect         Expect return value.
	 * @runInSeparateProcess
	 */
	public function test_ajax_fetch_with_record( $array_record, $expect ) {
		$this->sign_on_to_word_press();
		Repository_For_Test::truncate_table();
		foreach ( $array_record as $record ) {
			Repository_For_Test::insert_url( $record );
		}

		$static_press = new Static_Press( '/', '', array(), null, Test_Utility::create_remote_getter_mock() );
		ob_start();
		try {
			$static_press->ajax_fetch( Test_Utility::create_terminator_mock() );
		} catch ( Die_Exception $exception ) {
			$output = ob_get_clean();
			$this->assertEquals( 'Dead!', $exception->getMessage() );
			$this->assertEquals( $expect, json_decode( $output, true ) );
			return;
		}
		$this->fail();
	}

	/**
	 * Function ajax_fetch() should .
	 */
	public function provider_ajax_fetch_with_record() {
		return array(
			array(
				array(
					new Model_Url(
						1,
						Static_Press_Model_Url::TYPE_OTHER_PAGE,
						'/test1/',
						0,
						'',
						0,
						2,
						1,
						'',
						'0000-00-00 00:00:00',
						0,
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00'
					),
					new Model_Url(
						2,
						Static_Press_Model_Url::TYPE_OTHER_PAGE,
						'/test2/',
						0,
						'',
						0,
						2,
						1,
						'',
						'0000-00-00 00:00:00',
						0,
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00'
					),
				),
				array(
					'result' => true,
					'files'  => array(
						'1'   => array(
							'ID'     => '1',
							'page'   => 1,
							'type'   => Static_Press_Model_Url::TYPE_OTHER_PAGE,
							'url'    => '/test1/',
							'static' => ABSPATH . 'test1/index.html',
						),
						'1-2' => array(
							'ID'     => '1',
							'page'   => 2,
							'type'   => Static_Press_Model_Url::TYPE_OTHER_PAGE,
							'url'    => '/test1/page/2',
							'static' => ABSPATH . 'test1/page/2/index.html',
						),
						'2'   => array(
							'ID'     => '2',
							'page'   => 1,
							'type'   => Static_Press_Model_Url::TYPE_OTHER_PAGE,
							'url'    => '/test2/',
							'static' => ABSPATH . 'test2/index.html',
						),
						'3'   => array(
							'ID'     => '3',
							'page'   => 1,
							'type'   => Static_Press_Model_Url::TYPE_OTHER_PAGE,
							'url'    => '/test1/page/',
							'static' => ABSPATH . 'test1/page/index.html',
						),
					),
					'final'  => true,
				),
			),
			array(
				array(
					new Model_Url(
						1,
						Static_Press_Model_Url::TYPE_SINGLE,
						'/?attachment_id=3/',
						3,
						'attachment',
						0,
						2,
						1,
						'',
						'0000-00-00 00:00:00',
						0,
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00'
					),
					new Model_Url(
						2,
						Static_Press_Model_Url::TYPE_SINGLE,
						'/?attachment_id=4/',
						4,
						'attachment',
						0,
						2,
						1,
						'',
						'0000-00-00 00:00:00',
						0,
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00'
					),
				),
				array(
					'result' => true,
					'files'  => array(
						'1'   => array(
							'ID'     => '1',
							'page'   => 1,
							'type'   => Static_Press_Model_Url::TYPE_SINGLE,
							'url'    => '/?attachment_id=3/',
							'static' => ABSPATH . '?attachment_id=3/index.html',
						),
						'1-2' => array(
							'ID'     => '1',
							'page'   => 2,
							'type'   => Static_Press_Model_Url::TYPE_SINGLE,
							'url'    => '/?attachment_id=3/2',
							'static' => ABSPATH . '?attachment_id=3/2/index.html',
						),
						'2'   => array(
							'ID'     => '2',
							'page'   => 1,
							'type'   => Static_Press_Model_Url::TYPE_SINGLE,
							'url'    => '/?attachment_id=4/',
							'static' => ABSPATH . '?attachment_id=4/index.html',
						),
					),
					'final'  => true,
				),
			),
		);
	}

	/**
	 * Function ajax_finalyze() should die.
	 * 
	 * @runInSeparateProcess
	 */
	public function test_ajax_finalyze() {
		$user_id = $this->sign_on_to_word_press();
		set_transient( "static static - {$user_id}", array( 'fetch_last_id' => 2 ), 3600 );
		$expect       = '{"result":true}';
		$static_press = new Static_Press();
		ob_start();
		try {
			$static_press->ajax_finalyze( Test_Utility::create_terminator_mock() );
		} catch ( Die_Exception $exception ) {
			$output = ob_get_clean();
			$this->assertEquals( 'Dead!', $exception->getMessage() );
			$this->assertEquals( $expect, $output );
			$this->assertFalse( get_transient( 'static static' ) );
			return;
		}
		$this->fail();
	}

	/**
	 * Signs on to WordPress.
	 */
	private function sign_on_to_word_press() {
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
		return $result->ID;
	}
}

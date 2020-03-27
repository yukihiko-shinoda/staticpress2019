<?php
/**
 * Class Static_Press_Database_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-repository-for-test.php';
use static_press\includes\Static_Press;
use static_press\tests\testlibraries\Repository_For_Test;

/**
 * StaticPress database test case.
 *
 * @noinspection PhpUndefinedClassInspection
 */
class Static_Press_Database_Test extends \WP_UnitTestCase {
	/**
	 * Remove filters.
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
}

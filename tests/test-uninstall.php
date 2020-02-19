<?php
/**
 * Class Uninstall_Test
 *
 * @package staticpress\tests
 */

/**
 * Uninstall test case.
 */
class Uninstall_Test extends \WP_UnitTestCase {
	const OPTION_STATIC_URL   = 'StaticPress::static url';
	const OPTION_STATIC_DIR   = 'StaticPress::static dir';
	const OPTION_STATIC_BASIC = 'StaticPress::basic auth';
	/**
	 * Sets administrator as current user.
	 *
	 * @see https://wordpress.stackexchange.com/a/207363
	 */
	public function setUp() {
		parent::setUp();
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		update_option( self::OPTION_STATIC_URL, 'a' );
		update_option( self::OPTION_STATIC_DIR, 'b' );
		update_option( self::OPTION_STATIC_BASIC, 'c' );
	}

	/**
	 * Sets administrator as current user.
	 *
	 * @see https://wordpress.stackexchange.com/a/207363
	 */
	public function tearDown() {
		delete_option( self::OPTION_STATIC_URL );
		delete_option( self::OPTION_STATIC_DIR );
		delete_option( self::OPTION_STATIC_BASIC );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		parent::tearDown();
	}

	/**
	 * File uninstall.php should drop URL table from database.
	 * File uninstall.php should delete options.
	 */
	public function test() {
		global $wpdb;
		$this->assertEquals( 'wptests_urls', $wpdb->get_var( "show tables like 'wptests_urls'" ) );
		$this->assertEquals( 'a', get_option( self::OPTION_STATIC_URL ) );
		$this->assertEquals( 'b', get_option( self::OPTION_STATIC_DIR ) );
		$this->assertEquals( 'c', get_option( self::OPTION_STATIC_BASIC ) );
		define( 'WP_UNINSTALL_PLUGIN', '' );
		require dirname( dirname( __FILE__ ) ) . '/uninstall.php';
		$this->assertEquals( '', $wpdb->get_var( "show tables like 'wptests_urls'" ) );
		$this->assertFalse( get_option( self::OPTION_STATIC_URL ) );
		$this->assertFalse( get_option( self::OPTION_STATIC_DIR ) );
		$this->assertFalse( get_option( self::OPTION_STATIC_BASIC ) );
	}
}

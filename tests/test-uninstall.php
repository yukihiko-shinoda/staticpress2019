<?php
/**
 * Class Uninstall_Test
 *
 * @package static_press\tests
 */

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-polyfill-wp-unittestcase.php';
use static_press\tests\testlibraries\Polyfill_WP_UnitTestCase;

/**
 * Uninstall test case.
 */
class Uninstall_Test extends Polyfill_WP_UnitTestCase {
	const UNINSTALL_PHP         = 'uninstall.php';
	const OPTION_STATIC_URL     = 'StaticPress::static url';
	const OPTION_STATIC_DIR     = 'StaticPress::static dir';
	const OPTION_STATIC_BASIC   = 'StaticPress::basic auth';
	const OPTION_STATIC_TIMEOUT = 'StaticPress::timeout';
	/**
	 * Sets administrator as current user.
	 *
	 * @see https://wordpress.stackexchange.com/a/207363
	 */
	public function set_up() {
		parent::set_up();
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		update_option( self::OPTION_STATIC_URL, 'a' );
		update_option( self::OPTION_STATIC_DIR, 'b' );
		update_option( self::OPTION_STATIC_BASIC, 'c' );
		update_option( self::OPTION_STATIC_TIMEOUT, 'd' );
	}

	/**
	 * Sets administrator as current user.
	 *
	 * @see https://wordpress.stackexchange.com/a/207363
	 */
	public function tear_down() {
		delete_option( self::OPTION_STATIC_URL );
		delete_option( self::OPTION_STATIC_DIR );
		delete_option( self::OPTION_STATIC_BASIC );
		delete_option( self::OPTION_STATIC_TIMEOUT );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		parent::tear_down();
	}

	/**
	 * File uninstall.php should exists on plugin root directory.
	 * File uninstall.php should drop URL table from database.
	 * File uninstall.php should delete options.
	 */
	public function test() {
		global $wpdb;
		$this->assertEquals( 'wptests_urls', $wpdb->get_var( "show tables like 'wptests_urls'" ) );
		$this->assertEquals( 'a', get_option( self::OPTION_STATIC_URL ) );
		$this->assertEquals( 'b', get_option( self::OPTION_STATIC_DIR ) );
		$this->assertEquals( 'c', get_option( self::OPTION_STATIC_BASIC ) );
		$this->assertEquals( 'd', get_option( self::OPTION_STATIC_TIMEOUT ) );
		$plugin_root_directory = dirname( dirname( __FILE__ ) ) . '/';
		$this->assertFileExists( $plugin_root_directory . self::UNINSTALL_PHP );
		// Important! Never remove following definition, otherwise, whole PHPUnit process will die!
		define( 'WP_UNINSTALL_PLUGIN', '' );
		require $plugin_root_directory . self::UNINSTALL_PHP;
		$this->assertEquals( '', $wpdb->get_var( "show tables like 'wptests_urls'" ) );
		$this->assertFalse( get_option( self::OPTION_STATIC_URL ) );
		$this->assertFalse( get_option( self::OPTION_STATIC_DIR ) );
		$this->assertFalse( get_option( self::OPTION_STATIC_BASIC ) );
		$this->assertFalse( get_option( self::OPTION_STATIC_TIMEOUT ) );
	}
}

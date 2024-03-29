<?php
/**
 * Class Plugin_Test
 *
 * @package static_press\tests
 */

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-polyfill-wp-unittestcase.php';
use static_press\tests\testlibraries\Polyfill_WP_UnitTestCase;

/**
 * Plugin test case.
 *
 * @noinspection PhpUndefinedClassInspection
 */
class Plugin_Test extends Polyfill_WP_UnitTestCase {
	/**
	 * Path to plugin.php file.
	 * 
	 * @var string
	 */
	private $path_to_plugin_file;
	/**
	 * Sets path to plugin file and require.
	 */
	public function set_up() {
		parent::set_up();
		$this->path_to_plugin_file = dirname( dirname( __FILE__ ) ) . '/plugin.php';
		require $this->path_to_plugin_file;
		remove_all_filters( 'StaticPress::get_url' );
		remove_all_filters( 'StaticPress::put_content' );
		remove_all_filters( 'https_local_ssl_verify' );
		remove_all_actions( 'activate_' . ltrim( $this->path_to_plugin_file, '/' ) );
		remove_all_actions( 'deactivate_' . ltrim( $this->path_to_plugin_file, '/' ) );
	}

	/**
	 * File plugin.php should define variable named "$staticpress".
	 * (because StaticPress-S3 already refer it.)
	 * File plugin.php should add filters.
	 */
	public function test() {
		$this->assertEquals( false, has_filter( 'StaticPress::get_url' ) );
		$this->assertEquals( false, has_filter( 'StaticPress::put_content' ) );
		$this->assertEquals( false, has_filter( 'https_local_ssl_verify' ) );
		$this->assertEquals( false, has_action( 'activate_' . ltrim( $this->path_to_plugin_file, '/' ) ) );
		$this->assertEquals( false, has_action( 'deactivate_' . ltrim( $this->path_to_plugin_file, '/' ) ) );
		require $this->path_to_plugin_file;
		$variable_name = 'staticpress';
		$this->assertTrue( isset( ${$variable_name} ) );
		$this->assertEquals( 10, has_filter( 'StaticPress::get_url', array( $staticpress, 'replace_url' ) ) );
		$this->assertEquals( 10, has_filter( 'StaticPress::put_content', array( $staticpress, 'rewrite_generator_tag' ) ) );
		$this->assertEquals( 10, has_filter( 'StaticPress::put_content', array( $staticpress, 'add_last_modified' ) ) );
		$this->assertEquals( 10, has_filter( 'StaticPress::put_content', array( $staticpress, 'remove_link_tag' ) ) );
		$this->assertEquals( 10, has_filter( 'StaticPress::put_content', array( $staticpress, 'replace_relative_uri' ) ) );
		$this->assertEquals( 10, has_filter( 'https_local_ssl_verify', '__return_false' ) );
		$this->assertEquals( 10, has_action( 'activate_' . ltrim( $this->path_to_plugin_file, '/' ), array( $staticpress, 'activate' ) ) );
		$this->assertEquals( 10, has_action( 'deactivate_' . ltrim( $this->path_to_plugin_file, '/' ), array( $staticpress, 'deactivate' ) ) );
	}
}

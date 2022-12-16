<?php
/**
 * Class Static_Press_Site_Dependency_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-polyfill-wp-unit-test-case.php';
use static_press\includes\Static_Press_Site_Dependency;
use static_press\tests\testlibraries\Polyfill_WP_UnitTestCase;

/**
 * Reposistory test case.
 */
class Static_Press_Site_Dependency_Test extends Polyfill_WP_UnitTestCase {
	const DOMAIN_ANOTHER_BLOG = 'something.example.com';
	const PATH_ANOTHER_BLOG   = '/';
	/**
	 * For WordPress
	 * 
	 * @var string
	 */
	private $url;
	/**
	 * For WordPress
	 * 
	 * @var string
	 */
	private $url_previous;
	/**
	 * For WordPress
	 * 
	 * @var string
	 */
	private $blog_id_another_blog;
	/**
	 * For WordPress
	 * 
	 * @var string
	 */
	private $blog_id_previous;
	/**
	 * Creates another blog.
	 */
	public function set_up() {
		parent::set_up();
		global $wp_version;
		if ( defined( 'MULTISITE' ) && MULTISITE === true && version_compare( $wp_version, '5.0.0', '>=' ) ) {
			/**
			 *  In WordPress 4.3, wpmu_create_blog() breaks MySQL connection from separate process.
			 *  mysqli_errno(): Couldn't fetch mysqli
			 *  To fix, PHPUnit 6 is required, however, WordPress plugin test are only supported by PHPUnit 5.*...
			 * 
			 *  @see https://stackoverflow.com/questions/45989601/wordpress-develop-unit-testing-couldnt-fetch-mysqli/51098542#51098542
			 *  @see https://make.wordpress.org/cli/handbook/plugin-unit-tests/#running-tests-locally
			 */
			remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
			remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
			wpmu_delete_blog( domain_exists( self::DOMAIN_ANOTHER_BLOG, self::PATH_ANOTHER_BLOG, 1 ), true );
			/**
			 * To prevent Undefined index: REMOTE_ADDR when WordPress 4.3
			 * /tmp/wordpress/wp-includes/ms-functions.php:1809
			 */
			global $_SERVER;
			$_SERVER['REMOTE_ADDR']     = '0.0.0.0';
			$title                      = 'Look at my awesome site';
			$this->blog_id_previous     = get_current_blog_id();
			$this->blog_id_another_blog = wpmu_create_blog( self::DOMAIN_ANOTHER_BLOG, self::PATH_ANOTHER_BLOG, $title, 1 );
			switch_to_blog( $this->blog_id_another_blog );
			$this->url          = 'https://' . self::DOMAIN_ANOTHER_BLOG . '/sub/';
			$this->url_previous = get_option( 'home' );
			update_option( 'home', $this->url );
		} else {
			$this->url = 'http://example.org/';
		}
	}

	/**
	 * Removes another blog.
	 */
	public function tear_down() {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( false );
		global $wp_version;
		if ( defined( 'MULTISITE' ) && MULTISITE === true && version_compare( $wp_version, '5.0.0', '>=' ) ) {
			update_option( 'home', $this->url_previous );
			switch_to_blog( $this->blog_id_previous );
			restore_current_blog();
			wpmu_delete_blog( domain_exists( self::DOMAIN_ANOTHER_BLOG, self::PATH_ANOTHER_BLOG, 1 ), true );
			global $_SERVER;
			unset( $_SERVER['REMOTE_ADDR'] );
			add_filter( 'query', array( $this, '_create_temporary_tables' ) );
			add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		}
		parent::tear_down();
	}

	/**
	 * Function get_site_url() should get site home URL.
	 * Function get_site_url() should get appropriate blog's home URL when multisite.
	 */
	public function test_get_site_url() {
		$result = Static_Press_Site_Dependency::get_site_url();
		$this->assertEquals( $this->url, $result );
	}
}

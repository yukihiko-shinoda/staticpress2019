<?php
/**
 * Class Static_Press_Site_Dependency_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

use static_press\includes\Static_Press_Site_Dependency;

/**
 * Reposistory test case.
 */
class Static_Press_Site_Dependency_Test extends \WP_UnitTestCase {
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
	 * Creates another blog.
	 */
	public function setUp() {
		parent::setUp();
		if ( defined( 'MULTISITE' ) && MULTISITE === true ) {
			remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
			remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
			$domain = 'something.example.com';
			$path   = '/';
			$title  = 'Look at my awesome site';
			wpmu_delete_blog( domain_exists( $domain, $path, 1 ), true );
			$this->blog_id_another_blog = wpmu_create_blog( $domain, $path, $title, 1 );
			switch_to_blog( $this->blog_id_another_blog );
			$this->url          = "https://{$domain}/sub/";
			$this->url_previous = get_option( 'home' );
			update_option( 'home', $this->url );
		} else {
			$this->url = 'http://example.org/';
		}
	}

	/**
	 * Removes another blog.
	 */
	public function tearDown() {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( false );
		if ( defined( 'MULTISITE' ) && MULTISITE === true ) {
			update_option( 'home', $this->url_previous );
			restore_current_blog();
			add_filter( 'query', array( $this, '_create_temporary_tables' ) );
			add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		}
		parent::tearDown();
	}

	/**
	 * Function get_site_url() should return site URL.
	 */
	public function test_get_site_url() {
		$result = Static_Press_Site_Dependency::get_site_url();
		$this->assertEquals( $this->url, $result );
	}
}

<?php
/**
 * Class Static_Press_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-mock-creator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/infrastructure/class-file-system-operator.php';
use Mockery;
use static_press\includes\Static_Press;
use static_press\tests\testlibraries\creators\Mock_Creator;
use static_press\tests\testlibraries\infrastructure\File_System_Operator;

/**
 * StaticPress test case.
 *
 * @noinspection PhpUndefinedClassInspection
 */
class Static_Press_Test extends \WP_UnitTestCase {
	/**
	 * Puts up Mockery.
	 */
	public function tearDown() {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test steps for constructor.
	 * 
	 * @dataProvider provider_init_param_static_url
	 * 
	 * @param string $static_url Argument.
	 * @param string $expect     Expect return value.
	 */
	public function test_init_param_static_url( $static_url, $expect ) {
		$static_press       = new Static_Press( $static_url );
		$reflector          = new \ReflectionClass( $static_press );
		$reflector_property = $reflector->getProperty( 'static_site_url' );
		$reflector_property->setAccessible( true );

		$this->assertEquals( $expect, $reflector_property->getValue( $static_press ) );
	}

	/**
	 * Function init_param() should set
	 * persed home URL or DOMAIN_CURRENT_SITE or contents of the Host: header from the current request
	 * when parameter is not HTTP nor HTTPS.
	 * Function init_param() should set parameter when parameter is HTTP.
	 * Function init_param() should set parameter when parameter is HTTPS.
	 */
	public function provider_init_param_static_url() {
		return array(
			array( '/', 'http://example.org/' ),
			array( '//domain.com/', 'http://example.org/' ),
			array( 'http://domain.com/', 'http://domain.com/' ),
			array( 'https://domain.com/', 'https://domain.com/' ),
		);
	}

	/**
	 * Test steps for constructor.
	 * 
	 * @dataProvider provider_init_param_dump_directory
	 * 
	 * @param string $static_url     Argument.
	 * @param string $dump_directory Argument.
	 * @param string $expect         Expect return value.
	 */
	public function test_init_param_dump_directory( $static_url, $dump_directory, $expect ) {
		$static_press       = new Static_Press( $static_url, $dump_directory );
		$reflector          = new \ReflectionClass( $static_press );
		$reflector_property = $reflector->getProperty( 'dump_directory' );
		$reflector_property->setAccessible( true );

		$this->assertEquals( $expect, $reflector_property->getValue( $static_press ) );
	}

	/**
	 * Function init_param() should set WordPress directory when parameter is empty.
	 * Function init_param() should set parameter when parameter is not empty.
	 * Function init_param() should set path which end with slash.
	 * Function init_param() should set path added relative URL.
	 */
	public function provider_init_param_dump_directory() {
		return array(
			array( '/', '', ABSPATH ),
			array( 'http://domain.com/', '', ABSPATH ),
			array( 'https://domain.com/test', '', ABSPATH . 'test/' ),
			array( '/', '/tmp/', '/tmp/' ),
			array( '/', '/tmp', '/tmp/' ),
			array( 'http://domain.com/', '/tmp', '/tmp/' ),
			array( 'https://domain.com/test', '/tmp/', '/tmp/test/' ),
		);
	}

	/**
	 * Test steps for replace_url().
	 *
	 * @dataProvider provider_replace_url
	 *
	 * @param string $url argument.
	 * @param string $expect Expect return value.
	 */
	public function test_replace_url( $url, $expect ) {
		$static_press = new Static_Press();
		$this->assertEquals( $expect, $static_press->replace_url( $url ) );
	}

	/**
	 * Function replace_url() should return relative URL when same host.
	 * Function replace_url() should return absolute URL when different host.
	 * Function replace_url() should return URL end with '/' when no extension is set.
	 * Function replace_url() should return URL end without '/' when extension is registered.
	 * Function replace_url() should return URL end with '/' when extension is not registered.
	 */
	public function provider_replace_url() {
		return array(
			array( '', '/' ),
			array( 'http://example.org/', '/' ),
			array( 'http://google.com/', 'http://google.com/' ),
			array( 'http://example.org/test', '/test/' ),
			array( 'http://example.org/test.php', '/test.php' ),
			array( 'http://example.org/test.xlsx', '/test.xlsx/' ),  // Maybe, not intended.
		);
	}

	/**
	 * Function remove_link_tag() should remove link tag of pingback.
	 * Function remove_link_tag() should remove link tag of EditURI.
	 * Function remove_link_tag() should remove link tag of shortlink.
	 * Function remove_link_tag() should remove link tag of wlwmanifest.
	 * Function remove_link_tag() should not remove link tag of shortcut icon.
	 * Function remove_link_tag() should remove link tag of alternate type of application/rss+xml.
	 * Function remove_link_tag() should not remove link tag of alternate type of application/atom+xml.
	 */
	public function test_remove_link_tag() {
		$parameter    = File_System_Operator::get_test_resource_content( 'remove-link-tag-before.html' );
		$expect       = File_System_Operator::get_test_resource_content( 'remove-link-tag-after.html' );
		$static_press = new Static_Press();
		$actual       = $static_press->remove_link_tag( $parameter );
		$this->assertEquals( $expect, $actual );
	}

	/**
	 * Test steps for add_last_modified().
	 * 
	 * @dataProvider provider_add_last_modified
	 * 
	 * @param string $file_name_before File name of before state.
	 * @param string $http_code        HTTP status code.
	 * @param string $file_name_after  File name of after state.
	 */
	public function test_add_last_modified( $file_name_before, $http_code, $file_name_after ) {
		$content      = File_System_Operator::get_test_resource_content( $file_name_before );
		$expect       = File_System_Operator::get_test_resource_content( $file_name_after );
		$static_press = new Static_Press(
			'/',
			'',
			array(),
			Mock_Creator::create_date_time_factory_mock( 'create_gmdate', 'D, d M Y H:i:s', 'Mon, 23 Des 2019 12:34:56' )
		);
		$actual       = $static_press->add_last_modified( $content, $http_code );
		$this->assertEquals( $expect, $actual );
	}

	/**
	 * Function add_last_modified() should add whether URL exists or not.
	 *
	 * @return array[]
	 */
	public function provider_add_last_modified() {
		return array(
			array(
				'add-last-modified-html-without-attribute-before.html',
				200,
				'add-last-modified-html-without-attribute-after.html',
			),
			array(
				'add-last-modified-html-with-attribute-before.html',
				200,
				'add-last-modified-html-with-attribute-after.html',
			),
			array(
				'add-last-modified-xhtml-without-attribute-before.html',
				200,
				'add-last-modified-xhtml-without-attribute-after.html',
			),
			array(
				'add-last-modified-xhtml-with-attribute-before.html',
				200,
				'add-last-modified-xhtml-with-attribute-after.html',
			),
			array(
				'add-last-modified-html-without-attribute-before.html',
				404,
				'add-last-modified-html-without-attribute-before.html',
			),
			array(
				'add-last-modified-html-with-attribute-before.html',
				404,
				'add-last-modified-html-with-attribute-before.html',
			),
			array(
				'add-last-modified-xhtml-without-attribute-before.html',
				404,
				'add-last-modified-xhtml-without-attribute-before.html',
			),
			array(
				'add-last-modified-xhtml-with-attribute-before.html',
				404,
				'add-last-modified-xhtml-with-attribute-before.html',
			),
		);
	}

	/**
	 * Function rewrite_generator_tag() should return generator meta tag which added plugin name and version.
	 */
	public function test_rewrite_generator_tag() {
		$content        = '<meta name="generator" content="WordPress 5.3" />';
		$file_data      = get_file_data(
			dirname( dirname( dirname( __FILE__ ) ) ) . '/plugin.php',
			array(
				'pluginname' => 'Plugin Name',
				'version'    => 'Version',
			)
		);
		$plugin_name    = $file_data['pluginname'];
		$plugin_version = $file_data['version'];
		$expect         = '<meta name="generator" content="WordPress 5.3 with ' . $plugin_name . ' ver.' . $plugin_version . '" />';

		$static_press = new Static_Press();
		$result       = $static_press->rewrite_generator_tag( $content );
		$this->assertEquals( $expect, $result );
	}

	/**
	 * Function replace_relative_uri() should return generator meta tag which added plugin name and version.
	 */
	public function test_replace_relative_uri() {
		update_option( 'home', 'https://dynamic-site.com/sub/' );
		$content      = File_System_Operator::get_test_resource_content( 'replace_relative_uri-before.html' );
		$expect       = File_System_Operator::get_test_resource_content( 'replace_relative_uri-after.html' );
		$static_press = new Static_Press( 'https://static-site.com/sub/' );
		$result       = $static_press->replace_relative_uri( $content );
		$this->assertEquals( $expect, $result );
	}

	/**
	 * Test steps for test_replace_relative_uri().
	 *
	 * @dataProvider provider_replace_relative_uri2
	 *
	 * @param string $content argument.
	 * @param string $expect Expect return value.
	 */
	public function test_replace_relative_uri2( $content, $expect ) {
		$static_press = new Static_Press( 'http://example.org/static' );
		$this->assertEquals( $expect, $static_press->replace_relative_uri( $content ) );
	}

	/**
	 * Function replace_relative_uri() should replace site URL to the static URL.
	 * Function replace_relative_uri() should replace relative path in the attributes to the static path.
	 * Function replace_relative_uri() should not replace external URL starts with "//".
	 */
	public function provider_replace_relative_uri2() {
		return array(
			array(
				'http://example.org/foo/bar/',
				'http://example.org/static/foo/bar/',
			),
			array(
				'<a href="/foo/bar/"></a>',
				'<a href="/static/foo/bar/"></a>',
			),
			array(
				'<a href="//example.test/foo/bar/"></a>',
				'<a href="//example.test/foo/bar/"></a>',
			),
		);
	}

	/**
	 * Test steps for test_replace_relative_uri().
	 *
	 * @dataProvider provider_replace_relative_uri3
	 *
	 * @param string $content argument.
	 * @param string $expect Expect return value.
	 */
	public function test_replace_relative_uri3( $content, $expect ) {
		update_option( 'home', 'https://dynamic-site.com/sub/' );
		$static_press = new Static_Press( 'http://example.org' );
		$this->assertEquals( $expect, $static_press->replace_relative_uri( $content ) );
	}

	/**
	 * Function replace_relative_uri() should replace site URL to the static URL.
	 * Function replace_relative_uri() should replace relative path in the attributes to the static path.
	 * Function replace_relative_uri() should not replace external URL starts with "//".
	 */
	public function provider_replace_relative_uri3() {
		return array(
			array(
				'https://dynamic-site.com/sub/foo/bar/',
				'http://example.org/foo/bar/',
			),
			array(
				'<a href="https://dynamic-site.com/sub"></a>',
				'<a href="/"></a>',
			),
			array(
				'<a href="https://dynamic-site.com/sub/foo/bar/"></a>',
				'<a href="/foo/bar/"></a>',
			),
			array(
				'<a href="//example.test/foo/bar/"></a>',
				'<a href="//example.test/foo/bar/"></a>',
			),
		);
	}
}

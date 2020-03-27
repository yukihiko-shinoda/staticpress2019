<?php
/**
 * Class Static_Press_Model_Static_File_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

use static_press\includes\Static_Press_Model_Static_File;

/**
 * Static_Press_Model_Static_File test case.
 */
class Static_Press_Model_Static_File_Test extends \WP_UnitTestCase {
	/**
	 * Test steps for static_url().
	 *
	 * @dataProvider provider_static_url
	 *
	 * @param string $permalink argument.
	 * @param string $expect    Expect return value.
	 */
	public function test_static_url( $permalink, $expect ) {
		$this->assertEquals( $expect, Static_Press_Model_Static_File::static_url( $permalink ) );
	}

	/**
	 * Function static_url() should return index.html based on permalink when permalink doesn't end with extension.
	 * Function static_url() should return argument when permalink ends with extension.
	 */
	public function provider_static_url() {
		return array(
			array( '/', '/index.html' ),
			array( '/test', '/test/index.html' ),
			array( '/test/', '/test/index.html' ),
			array( '/test/test', '/test/test/index.html' ),
			array( '/test/test.png', '/test/test.png' ),
			array( '/sitemap.xml', '/sitemap.xml' ),
		);
	}
}

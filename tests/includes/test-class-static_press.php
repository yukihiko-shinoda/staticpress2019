<?php
/**
 * Class StaticPressTest
 *
 * @package StaticPress
 */

/**
 * StaticPress test case.
 */
/** @noinspection PhpUndefinedClassInspection */
class StaticPressTest extends WP_UnitTestCase {

    /**
     * url_table() should return prefix for WordPress tables + 'urls'.
     */
    public function test_url_table() {
        $this->assertEquals( 'wptests_urls', static_press::url_table() );
    }
}

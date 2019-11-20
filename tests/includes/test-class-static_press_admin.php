<?php
/**
 * Class StaticPressAdminTest
 *
 * @package StaticPress
 */

/**
 * StaticPress test case.
 */
/** @noinspection PhpUndefinedClassInspection */
class StaticPressAdminTest extends WP_UnitTestCase {
    // @see https://wordpress.stackexchange.com/a/207363
    public function setUp() {
        parent::setUp();
        $user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $user_id );
    }

    public function tearDown() {
        parent::tearDown();
    }

    /**
     * admin_menu() should not throw any exception.
     */
    public function test_admin_menu() {
        $staticPressAdmin = new static_press_admin(plugin_basename(__FILE__));
        $staticPressAdmin->admin_menu();
    }
}

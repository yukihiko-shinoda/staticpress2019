<?php
/**
 * Uninstall process
 *
 * @package staticpress\tests
 * @see https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/#method-2-uninstall-php
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

if ( ! class_exists( 'Static_Press_Admin' ) ) {
	require dirname( __FILE__ ) . '/includes/class-static-press-admin.php';
}
if ( ! class_exists( 'static_press\includes\repositories\Static_Press_Repository' ) ) {
	require dirname( __FILE__ ) . '/includes/repositories/class-static-press-repository.php';
}
use static_press\includes\repositories\Static_Press_Repository;

delete_option( Static_Press_Admin::OPTION_STATIC_URL );
delete_option( Static_Press_Admin::OPTION_STATIC_DIR );
delete_option( Static_Press_Admin::OPTION_STATIC_BASIC );
delete_option( Static_Press_Admin::OPTION_STATIC_TIMEOUT );

$static_press_repository = new Static_Press_Repository();
$static_press_repository->drop_table_if_exists();

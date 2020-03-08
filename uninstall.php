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
if ( ! class_exists( 'static_press\includes\Static_Press' ) ) {
	require dirname( __FILE__ ) . '/includes/class-static-press.php';
}
use static_press\includes\Static_Press;

delete_option( Static_Press_Admin::OPTION_STATIC_URL );
delete_option( Static_Press_Admin::OPTION_STATIC_DIR );
delete_option( Static_Press_Admin::OPTION_STATIC_BASIC );
delete_option( Static_Press_Admin::OPTION_STATIC_TIMEOUT );

global $wpdb;
$wpdb->query( 'DROP TABLE IF EXISTS ' . static_press::url_table() );

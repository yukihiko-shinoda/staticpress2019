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

if ( ! class_exists( 'static_press_admin' ) ) {
	require dirname( __FILE__ ) . '/includes/class-static_press_admin.php';
}
if ( ! class_exists( 'staticpress\includes\static_press' ) ) {
	require dirname( __FILE__ ) . '/includes/class-static_press.php';
}
use staticpress\includes\static_press;

delete_option( static_press_admin::OPTION_STATIC_URL );
delete_option( static_press_admin::OPTION_STATIC_DIR );
delete_option( static_press_admin::OPTION_STATIC_BASIC );
delete_option( static_press_admin::OPTION_STATIC_TIMEOUT );

global $wpdb;
$wpdb->query( 'DROP TABLE IF EXISTS ' . static_press::url_table() );

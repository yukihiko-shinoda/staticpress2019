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

if ( ! defined( 'STATIC_PRESS_PLUGIN_DIR' ) ) {
	/**
	 * Plugin Directory.
	 *
	 * @var string $STATIC_PRESS_PLUGIN_DIR Plugin folder directory path. Eg. `/var/www/html/web/app/plugins/staticpress2019/`
	 */
	define( 'STATIC_PRESS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-admin.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/repositories/class-static-press-repository.php';
use static_press\includes\repositories\Static_Press_Repository;

delete_option( Static_Press_Admin::OPTION_STATIC_URL );
delete_option( Static_Press_Admin::OPTION_STATIC_DIR );
delete_option( Static_Press_Admin::OPTION_STATIC_BASIC );
delete_option( Static_Press_Admin::OPTION_STATIC_TIMEOUT );

$static_press_repository = new Static_Press_Repository();
$static_press_repository->drop_table_if_exists();

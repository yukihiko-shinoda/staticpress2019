<?php
/**
 * StaticPress2019
 * 
 * @package     PluginPackage
 * @author      wokamoto
 * @copyright   2013 wokamoto
 * @license     GPL-2.0-or-later
 * 
 * @wordpress-plugin
 * Plugin Name: StaticPress2019
 * Plugin URI:  https://github.com/yukihiko-shinoda/staticpress
 * Description: Transform your WordPress into static websites and blogs.
 * Version:     0.4.8
 * Author:      wokamoto
 * Author URI:  http://www.digitalcube.jp/
 * Text Domain: static-press
 * Domain Path: /languages
 * 
 * License:
 *  Released under the GPL license
 *   http://www.gnu.org/copyleft/gpl.html
 * 
 *   Copyright 2013 (email : wokamoto@digitalcube.jp)
 * 
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 * 
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 * 
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( ! class_exists( 'static_press_admin' ) ) {
	require dirname( __FILE__ ) . '/includes/class-static_press_admin.php';
}
if ( ! class_exists( 'staticpress\includes\static_press' ) ) {
	require dirname( __FILE__ ) . '/includes/class-static_press.php';
}
use staticpress\includes\static_press;

load_plugin_textdomain( static_press_admin::TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
$staticpress_instance = new static_press(
	plugin_basename( __FILE__ ),
	static_press_admin::static_url(),
	static_press_admin::static_dir(),
	static_press_admin::remote_get_option()
);
add_filter( 'StaticPress::get_url', array( $staticpress_instance, 'replace_url' ) );
add_filter( 'StaticPress::static_url', array( $staticpress_instance, 'static_url' ) );
add_filter( 'StaticPress::put_content', array( $staticpress_instance, 'rewrite_generator_tag' ), 10, 2 );
add_filter( 'StaticPress::put_content', array( $staticpress_instance, 'add_last_modified' ), 10, 2 );
add_filter( 'StaticPress::put_content', array( $staticpress_instance, 'remove_link_tag' ), 10, 2 );
add_filter( 'StaticPress::put_content', array( $staticpress_instance, 'replace_relative_URI' ), 10, 2 );
add_filter( 'https_local_ssl_verify', '__return_false' );

register_activation_hook( __FILE__, array( $staticpress_instance, 'activate' ) );
register_deactivation_hook( __FILE__, array( $staticpress_instance, 'deactivate' ) );

if ( is_admin() ) {
	new static_press_admin( plugin_basename( __FILE__ ) );
}

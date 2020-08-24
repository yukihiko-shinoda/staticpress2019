<?php
/**
 * StaticPress2019
 * 
 * @package     static_press
 * @author      Yukihiko Shinoda <yuk.hik.future@gmail.com>
 * @author      wokamoto
 * @author      megumitheme
 * @author      amimotoami
 * @author      cat
 * @author      kazuaki.matsuhashi
 * @author      Olav Schettler
 * @author      mecrazy
 * @author      Caglar Akpinar
 * @author      Pete Schuster
 * @author      yukku0423
 * @author      k1LoW
 * @author      Your mother
 * @author      hideokamoto
 * @author      arobindorath
 * @author      chrome-cgi
 * @copyright   2013 wokamoto
 * @license     GPL-2.0-or-later
 * 
 * @wordpress-plugin
 * Plugin Name: StaticPress2019
 * Plugin URI:  https://wordpress.org/plugins/staticpress2019/
 * Description: Transforms your WordPress into static websites and blogs.
 * Version:     0.6.3
 * Author:      yshinoda, wokamoto, amimotoami, pas5027, hideokamoto
 * Author URI:  https://github.com/yukihiko-shinoda
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

if ( ! defined( 'STATIC_PRESS_PLUGIN_DIR' ) ) {
	/**
	 * Plugin Directory.
	 *
	 * @var string $STATIC_PRESS_PLUGIN_DIR Plugin folder directory path. Eg. `/var/www/html/web/app/plugins/staticpress2019/`
	 */
	define( 'STATIC_PRESS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-admin.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press.php';
use static_press\includes\Static_Press;

load_plugin_textdomain( Static_Press_Admin::TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
// Reason: StaticPress-S3 refers this global variable not $static_press but $staticpress.
// phpcs:ignore
$staticpress = new Static_Press(
	Static_Press_Admin::static_url(),
	Static_Press_Admin::static_dir(),
	Static_Press_Admin::remote_get_option()
);
add_filter( 'StaticPress::get_url', array( $staticpress, 'replace_url' ) );
add_filter( 'StaticPress::put_content', array( $staticpress, 'rewrite_generator_tag' ), 10, 2 );
add_filter( 'StaticPress::put_content', array( $staticpress, 'add_last_modified' ), 10, 2 );
add_filter( 'StaticPress::put_content', array( $staticpress, 'remove_link_tag' ), 10, 2 );
add_filter( 'StaticPress::put_content', array( $staticpress, 'replace_relative_uri' ), 10, 2 );
add_filter( 'https_local_ssl_verify', '__return_false' );

register_activation_hook( __FILE__, array( $staticpress, 'activate' ) );
register_deactivation_hook( __FILE__, array( $staticpress, 'deactivate' ) );

if ( is_admin() ) {
	new Static_Press_Admin( plugin_basename( __FILE__ ) );
}

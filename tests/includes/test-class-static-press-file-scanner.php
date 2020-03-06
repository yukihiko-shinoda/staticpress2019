<?php
/**
 * Class Static_Press_File_Scanner_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

use static_press\includes\Static_Press_File_Scanner;

/**
 * StaticPress test case.
 */
class Static_Press_File_Scanner_Test extends \WP_UnitTestCase {
	/**
	 * Function scan_file should returns list of file.
	 */
	public function test_scan_file() {
		$static_files_ext          = array(
			'html','htm','txt','css','js','gif','png','jpg','jpeg',
			'mp3','ico','ttf','woff','woff2','otf','eot','svg','svgz','xml',
			'gz','zip', 'pdf', 'swf', 'xsl', 'mov', 'mp4', 'wmv', 'flv',
			'webm', 'ogg', 'oga', 'ogv', 'ogx', 'spx', 'opus',
		);
		$expect                    = array(
			'/usr/src/wordpress/wp-content/plugins/akismet/_inc/img/logo-full-2x.png',
			'/usr/src/wordpress/wp-content/plugins/akismet/_inc/akismet.css',
			'/usr/src/wordpress/wp-content/plugins/akismet/_inc/akismet.js',
			'/usr/src/wordpress/wp-content/plugins/akismet/_inc/form.js',
			'/usr/src/wordpress/wp-content/plugins/akismet/LICENSE.txt',
			'/usr/src/wordpress/wp-content/plugins/akismet/readme.txt',
			'/usr/src/wordpress/wp-content/themes/twentynineteen/fonts/NonBreakingSpaceOverride.woff',
			'/usr/src/wordpress/wp-content/themes/twentynineteen/fonts/NonBreakingSpaceOverride.woff2',
			'/usr/src/wordpress/wp-content/themes/twentynineteen/js/customize-controls.js',
			'/usr/src/wordpress/wp-content/themes/twentynineteen/js/customize-preview.js',
			'/usr/src/wordpress/wp-content/themes/twentynineteen/js/priority-menu.js',
			'/usr/src/wordpress/wp-content/themes/twentynineteen/js/skip-link-focus-fix.js',
			'/usr/src/wordpress/wp-content/themes/twentynineteen/js/touch-keyboard-navigation.js',
			'/usr/src/wordpress/wp-content/themes/twentynineteen/readme.txt',
			'/usr/src/wordpress/wp-content/themes/twentynineteen/print.css',
			'/usr/src/wordpress/wp-content/themes/twentynineteen/style-editor-customizer.css',
			'/usr/src/wordpress/wp-content/themes/twentynineteen/style-editor.css',
			'/usr/src/wordpress/wp-content/themes/twentynineteen/style-rtl.css',
			'/usr/src/wordpress/wp-content/themes/twentynineteen/style.css',
			'/usr/src/wordpress/wp-content/themes/twentynineteen/postcss.config.js',
			'/usr/src/wordpress/wp-content/themes/twentynineteen/screenshot.png',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/css/blocks.css',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/css/colors-dark.css',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/css/editor-blocks.css',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/css/editor-style.css',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/css/ie8.css',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/css/ie9.css',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/images/coffee.jpg',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/images/espresso.jpg',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/images/header.jpg',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/images/sandwich.jpg',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/images/svg-icons.svg',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/js/customize-controls.js',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/js/customize-preview.js',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/js/global.js',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/js/html5.js',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/js/jquery.scrollTo.js',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/js/navigation.js',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/assets/js/skip-link-focus-fix.js',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/readme.txt',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/rtl.css',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/style.css',
			'/usr/src/wordpress/wp-content/themes/twentyseventeen/screenshot.png',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/css/blocks.css',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/css/editor-blocks.css',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/css/editor-style.css',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/css/ie.css',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/css/ie7.css',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/css/ie8.css',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/genericons/COPYING.txt',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/genericons/LICENSE.txt',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/genericons/genericons.css',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/genericons/Genericons.ttf',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/genericons/Genericons.woff',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/genericons/Genericons.eot',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/genericons/Genericons.svg',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/js/color-scheme-control.js',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/js/customize-preview.js',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/js/functions.js',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/js/html5.js',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/js/keyboard-image-navigation.js',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/js/skip-link-focus-fix.js',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/readme.txt',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/rtl.css',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/style.css',
			'/usr/src/wordpress/wp-content/themes/twentysixteen/screenshot.png',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/css/editor-style-block-rtl.css',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/css/editor-style-block.css',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/css/editor-style-classic-rtl.css',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/css/editor-style-classic.css',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/fonts/inter/Inter-italic-var.woff2',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/fonts/inter/Inter-upright-var.woff2',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/images/2020-landscape-1.png',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/images/2020-landscape-2.png',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/images/2020-square-1.png',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/images/2020-square-2.png',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/images/2020-three-quarters-1.png',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/images/2020-three-quarters-2.png',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/images/2020-three-quarters-3.png',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/images/2020-three-quarters-4.png',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/js/color-calculations.js',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/js/customize-controls.js',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/js/customize-preview.js',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/js/customize.js',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/js/editor-script-block.js',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/js/index.js',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/assets/js/skip-link-focus-fix.js',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/readme.txt',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/print.css',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/style-rtl.css',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/style.css',
			'/usr/src/wordpress/wp-content/themes/twentytwenty/screenshot.png',
			'/usr/src/wordpress/wp-content/uploads/2020/03/test.txt',
		);
		$static_press_file_scanner = new Static_Press_File_Scanner( apply_filters( 'StaticPress::static_files_filter', $static_files_ext ) );
		$actual                    = $static_press_file_scanner->scan( trailingslashit( WP_CONTENT_DIR ), true );
		$this->assertEquals( $expect, $actual );
	}
}

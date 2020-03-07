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
			ABSPATH . 'wp-content/plugins/akismet/_inc/img/logo-full-2x.png',
			ABSPATH . 'wp-content/plugins/akismet/_inc/akismet.css',
			ABSPATH . 'wp-content/plugins/akismet/_inc/akismet.js',
			ABSPATH . 'wp-content/plugins/akismet/_inc/form.js',
			ABSPATH . 'wp-content/plugins/akismet/LICENSE.txt',
			ABSPATH . 'wp-content/plugins/akismet/readme.txt',
			ABSPATH . 'wp-content/themes/twentynineteen/fonts/NonBreakingSpaceOverride.woff',
			ABSPATH . 'wp-content/themes/twentynineteen/fonts/NonBreakingSpaceOverride.woff2',
			ABSPATH . 'wp-content/themes/twentynineteen/js/customize-controls.js',
			ABSPATH . 'wp-content/themes/twentynineteen/js/customize-preview.js',
			ABSPATH . 'wp-content/themes/twentynineteen/js/priority-menu.js',
			ABSPATH . 'wp-content/themes/twentynineteen/js/skip-link-focus-fix.js',
			ABSPATH . 'wp-content/themes/twentynineteen/js/touch-keyboard-navigation.js',
			ABSPATH . 'wp-content/themes/twentynineteen/readme.txt',
			ABSPATH . 'wp-content/themes/twentynineteen/print.css',
			ABSPATH . 'wp-content/themes/twentynineteen/style-editor-customizer.css',
			ABSPATH . 'wp-content/themes/twentynineteen/style-editor.css',
			ABSPATH . 'wp-content/themes/twentynineteen/style-rtl.css',
			ABSPATH . 'wp-content/themes/twentynineteen/style.css',
			ABSPATH . 'wp-content/themes/twentynineteen/postcss.config.js',
			ABSPATH . 'wp-content/themes/twentynineteen/screenshot.png',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/css/blocks.css',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/css/colors-dark.css',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/css/editor-blocks.css',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/css/editor-style.css',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/css/ie8.css',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/css/ie9.css',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/images/coffee.jpg',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/images/espresso.jpg',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/images/header.jpg',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/images/sandwich.jpg',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/images/svg-icons.svg',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/js/customize-controls.js',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/js/customize-preview.js',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/js/global.js',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/js/html5.js',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/js/jquery.scrollTo.js',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/js/navigation.js',
			ABSPATH . 'wp-content/themes/twentyseventeen/assets/js/skip-link-focus-fix.js',
			ABSPATH . 'wp-content/themes/twentyseventeen/readme.txt',
			ABSPATH . 'wp-content/themes/twentyseventeen/rtl.css',
			ABSPATH . 'wp-content/themes/twentyseventeen/style.css',
			ABSPATH . 'wp-content/themes/twentyseventeen/screenshot.png',
			ABSPATH . 'wp-content/themes/twentysixteen/css/blocks.css',
			ABSPATH . 'wp-content/themes/twentysixteen/css/editor-blocks.css',
			ABSPATH . 'wp-content/themes/twentysixteen/css/editor-style.css',
			ABSPATH . 'wp-content/themes/twentysixteen/css/ie.css',
			ABSPATH . 'wp-content/themes/twentysixteen/css/ie7.css',
			ABSPATH . 'wp-content/themes/twentysixteen/css/ie8.css',
			ABSPATH . 'wp-content/themes/twentysixteen/genericons/COPYING.txt',
			ABSPATH . 'wp-content/themes/twentysixteen/genericons/LICENSE.txt',
			ABSPATH . 'wp-content/themes/twentysixteen/genericons/genericons.css',
			ABSPATH . 'wp-content/themes/twentysixteen/genericons/Genericons.ttf',
			ABSPATH . 'wp-content/themes/twentysixteen/genericons/Genericons.woff',
			ABSPATH . 'wp-content/themes/twentysixteen/genericons/Genericons.eot',
			ABSPATH . 'wp-content/themes/twentysixteen/genericons/Genericons.svg',
			ABSPATH . 'wp-content/themes/twentysixteen/js/color-scheme-control.js',
			ABSPATH . 'wp-content/themes/twentysixteen/js/customize-preview.js',
			ABSPATH . 'wp-content/themes/twentysixteen/js/functions.js',
			ABSPATH . 'wp-content/themes/twentysixteen/js/html5.js',
			ABSPATH . 'wp-content/themes/twentysixteen/js/keyboard-image-navigation.js',
			ABSPATH . 'wp-content/themes/twentysixteen/js/skip-link-focus-fix.js',
			ABSPATH . 'wp-content/themes/twentysixteen/readme.txt',
			ABSPATH . 'wp-content/themes/twentysixteen/rtl.css',
			ABSPATH . 'wp-content/themes/twentysixteen/style.css',
			ABSPATH . 'wp-content/themes/twentysixteen/screenshot.png',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/css/editor-style-block-rtl.css',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/css/editor-style-block.css',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/css/editor-style-classic-rtl.css',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/css/editor-style-classic.css',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/fonts/inter/Inter-italic-var.woff2',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/fonts/inter/Inter-upright-var.woff2',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/images/2020-landscape-1.png',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/images/2020-landscape-2.png',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/images/2020-square-1.png',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/images/2020-square-2.png',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/images/2020-three-quarters-1.png',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/images/2020-three-quarters-2.png',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/images/2020-three-quarters-3.png',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/images/2020-three-quarters-4.png',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/js/color-calculations.js',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/js/customize-controls.js',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/js/customize-preview.js',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/js/customize.js',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/js/editor-script-block.js',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/js/index.js',
			ABSPATH . 'wp-content/themes/twentytwenty/assets/js/skip-link-focus-fix.js',
			ABSPATH . 'wp-content/themes/twentytwenty/readme.txt',
			ABSPATH . 'wp-content/themes/twentytwenty/print.css',
			ABSPATH . 'wp-content/themes/twentytwenty/style-rtl.css',
			ABSPATH . 'wp-content/themes/twentytwenty/style.css',
			ABSPATH . 'wp-content/themes/twentytwenty/screenshot.png',
			ABSPATH . 'wp-content/uploads/2020/03/test.txt',
		);
		$static_press_file_scanner = new Static_Press_File_Scanner( apply_filters( 'StaticPress::static_files_filter', $static_files_ext ) );
		$actual                    = $static_press_file_scanner->scan( trailingslashit( WP_CONTENT_DIR ), true );
		$this->assertEquals( $expect, $actual );
	}
}

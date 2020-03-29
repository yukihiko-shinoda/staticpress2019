<?php
/**
 * Function for child theme.
 * 
 * @see https://developer.wordpress.org/themes/advanced-topics/child-themes/
 * @package ?
 */

add_action( 'wp_enqueue_scripts', 'twentyfifteen_child_my_theme_enqueue_styles' );
/**
 * Enqueues my theme's style.
 * Reason: This file is belonging to not StaticPress but theme.
 */
function twentyfifteen_child_my_theme_enqueue_styles() { // phpcs:ignore
	$parent_style = 'twentyfifteen-style';
 
	wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
	wp_enqueue_style(
		'child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( $parent_style ),
		wp_get_theme()->get( 'Version' )
	);
}

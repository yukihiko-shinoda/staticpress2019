<?php
/**
 * Class Post_Array_Creator
 *
 * @package static_press\tests\testlibraries\creators
 */

namespace static_press\tests\testlibraries\creators;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-mock-creator.php';
use static_press\tests\testlibraries\creators\Mock_Creator;
use WP_Error;

/**
 * Post array creator.
 */
class Post_Array_Creator {
	/**
	 * Creates array post single.
	 * 
	 * @param string $post_title   Post title.
	 * @param string $post_content Post content.
	 * @return array Array post term.
	 * @throws \LogicException When failed to insert category.
	 */
	public static function create_single( $post_title = 'Post Title 1', $post_content = 'Post content 1.' ) {
		return array(
			'post_title'        => $post_title,
			'post_content'      => $post_content,
			'post_status'       => 'publish',
			'post_type'         => 'attachment',
			'post_modified'     => Mock_Creator::DATE_FOR_TEST,
			'post_modified_gmt' => Mock_Creator::DATE_FOR_TEST,
		);
	}

	/**
	 * Creates array post term.
	 * 
	 * @param string $term Term.
	 * @return array Array post term.
	 * @throws \LogicException When failed to insert category.
	 */
	public static function create_term( $term ) {
		return array(
			'post_title'        => 'Test Title',
			'post_content'      => 'Test content.',
			'post_status'       => 'publish',
			'post_type'         => 'post',
			'post_author'       => 1,
			'post_category'     => array( $term ),
			'post_modified'     => Mock_Creator::DATE_FOR_TEST,
			'post_modified_gmt' => Mock_Creator::DATE_FOR_TEST,
		);
	}

	/**
	 * Creates array post author.
	 * 
	 * @param integer $author_id Author ID.
	 * @return array Array post author.
	 * @throws \LogicException When failed to insert post.
	 */
	public static function create_author( $author_id ) {
		return array(
			'post_title'        => 'Post Title Author',
			'post_content'      => 'Post content Author.',
			'post_status'       => 'publish',
			'post_type'         => 'post',
			'post_author'       => $author_id,
			'post_modified'     => Mock_Creator::DATE_FOR_TEST,
			'post_modified_gmt' => Mock_Creator::DATE_FOR_TEST,
		);
	}
}

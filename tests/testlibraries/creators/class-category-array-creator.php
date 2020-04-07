<?php
/**
 * Class Category_Array_Creator
 *
 * @package static_press\tests\testlibraries\creators
 */

namespace static_press\tests\testlibraries\creators;

/**
 * Category array creator.
 */
class Category_Array_Creator {
	/**
	 * Creates array post term.
	 * 
	 * @return array Array post term.
	 * @throws \LogicException When failed to insert category.
	 */
	public static function create_parent() {
		return array(
			'cat_name' => 'category parent',
		);
	}

	/**
	 * Creates array post term.
	 * 
	 * @param integer $term_parent Term parent.
	 * @return array Array post term.
	 * @throws \LogicException When failed to insert category.
	 */
	public static function create_child( $term_parent ) {
		return array(
			'cat_name'             => 'category child',
			'category_description' => '',
			'category_nicename'    => '',
			'category_parent'      => $term_parent,
		);
	}
}

<?php
/**
 * Class Fixture_Category
 *
 * @package static_press\tests\testlibraries\fixtures
 */

namespace static_press\tests\testlibraries\fixtures;

use WP_Error;

/**
 * Fixture category.
 */
class Fixture_Category {
	/**
	 * Category ID.
	 * 
	 * @var integer
	 */
	public $category_id;

	/**
	 * Inserts category.
	 * 
	 * @param array $catarr Category array.
	 * @throws \LogicException When failed to delete category.
	 */
	public function __construct( $catarr ) {
		$this->category_id = wp_insert_category( $catarr );
		if ( 0 === $this->category_id || $this->category_id instanceof WP_Error ) {
			throw new \LogicException( 'Failed to insert category.' );
		}
	}

	/**
	 * Deletes category.
	 * 
	 * @throws \LogicException When failed to delete category.
	 */
	public function delete() {
		$result = wp_delete_category( $this->category_id );
		if ( true !== $result ) {
			throw new \LogicException( 'Failed to delete category.' );
		} 
	}
}

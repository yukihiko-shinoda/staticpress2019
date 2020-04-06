<?php
/**
 * Class Fixture_Post
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

use WP_Error;

/**
 * Fixture post.
 */
class Fixture_Post {
	/**
	 * Porst ID.
	 * 
	 * @var integer
	 */
	public $post_id;
	/**
	 * Post content.
	 * 
	 * @var string
	 */
	public $post_title;

	/**
	 * Inserts post.
	 * 
	 * @param array $postarr Post array.
	 * @throws \LogicException When failed to delete post.
	 */
	public function __construct( $postarr ) {
		add_filter( 'wp_insert_post_data', array( $this, 'alter_post_modification_time' ), 99, 2 );
		add_filter( 'wp_insert_attachment_data', array( $this, 'alter_post_modification_time' ), 99, 2 );
		$this->post_id = wp_insert_post( $postarr );
		remove_filter( 'wp_insert_attachment_data', array( $this, 'alter_post_modification_time' ), 99, 2 );
		remove_filter( 'wp_insert_post_data', array( $this, 'alter_post_modification_time' ), 99, 2 );
		if ( 0 === $this->post_id || $this->post_id instanceof WP_Error ) {
			throw new \LogicException( 'Failed to insert post.' );
		}
		$this->post_title = $postarr['post_title'];
	}

	/**
	 * Deletes post.
	 * 
	 * @throws \LogicException When failed to delete post.
	 */
	public function delete() {
		$post_data = wp_delete_post( $this->post_id );
		if ( false === $post_data || null === $post_data ) {
			throw new \LogicException( 'Failed to delete post.' );
		} 
	}

	/**
	 * Alters post modification time.
	 * 
	 * @see https://wordpress.stackexchange.com/questions/224161/cant-edit-post-modified-in-wp-insert-post-bug/224189#224189
	 * @param array $data    Data.
	 * @param array $postarr Post array.
	 */
	public function alter_post_modification_time( $data, $postarr ) {
		if ( ! empty( $postarr['post_modified'] ) && ! empty( $postarr['post_modified_gmt'] ) ) {
			$data['post_modified']     = $postarr['post_modified'];
			$data['post_modified_gmt'] = $postarr['post_modified_gmt'];
		}
	
		return $data;
	}
}

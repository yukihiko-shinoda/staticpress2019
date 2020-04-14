<?php
/**
 * Class Static_Press_Model_Url_Author
 *
 * @package static_press\includes\models
 */

namespace static_press\includes\models;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/repositories/class-static-press-repository.php';
use static_press\includes\repositories\Static_Press_Repository;
/**
 * Model URL author.
 */
class Static_Press_Model_Url_Author extends Static_Press_Model_Url {
	/**
	 * Constructor.
	 * 
	 * @param Object $author      Author.
	 * @param Object $author_data Author data.
	 */
	public function __construct( $author, $author_data ) {
		parent::__construct(
			null,
			Static_Press_Model_Url::TYPE_AUTHOR_ARCHIVE,
			apply_filters( 'StaticPress::get_url', get_author_posts_url( $author_data->ID, $author_data->user_nicename ) ),
			intval( $author->post_author ),
			null,
			null,
			intval( $author->count / intval( get_option( 'posts_per_page' ) ) ) + 1,
			null,
			null,
			null,
			null,
			$author->modified
		);
	}

	/**
	 * Converts to array.
	 * 
	 * @return array
	 */
	public function to_array() {
		return array_merge(
			$this->to_array_common(),
			array(
				Static_Press_Repository::FIELD_NAME_TYPE  => $this->get_type(),
				Static_Press_Repository::FIELD_NAME_OBJECT_ID => $this->get_object_id(),
				Static_Press_Repository::FIELD_NAME_PAGES => $this->get_pages(),
				Static_Press_Repository::FIELD_NAME_LAST_MODIFIED => $this->get_last_modified(),
			)
		);
	}
}

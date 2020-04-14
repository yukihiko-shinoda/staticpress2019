<?php
/**
 * Class Static_Press_Model_Url_Single
 *
 * @package static_press\includes\models
 */

namespace static_press\includes\models;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/models/class-static-press-model-url.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/repositories/class-static-press-repository.php';
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\repositories\Static_Press_Repository;
/**
 * Model URL single.
 */
class Static_Press_Model_Url_Single extends Static_Press_Model_Url {
	/**
	 * Constructor.
	 * 
	 * @param Object $post Post.
	 */
	public function __construct( $post ) {
		$count  = 1;
		$splite = preg_split( '#<!--nextpage-->#', $post->post_content );
		if ( $splite ) {
			$count = count( $splite );
		}
		parent::__construct(
			null,
			Static_Press_Model_Url::TYPE_SINGLE,
			apply_filters( 'StaticPress::get_url', get_permalink( $post->ID ) ),
			intval( $post->ID ),
			$post->post_type,
			null,
			$count,
			null,
			null,
			null,
			null,
			$post->post_modified
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
				Static_Press_Repository::FIELD_NAME_OBJECT_TYPE => $this->get_object_type(),
				Static_Press_Repository::FIELD_NAME_PAGES => $this->get_pages(),
				Static_Press_Repository::FIELD_NAME_LAST_MODIFIED => $this->get_last_modified(),
			)
		);
	}
}

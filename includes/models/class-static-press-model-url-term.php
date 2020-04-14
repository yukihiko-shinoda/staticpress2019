<?php
/**
 * Class Static_Press_Model_Url_Term
 *
 * @package static_press\includes\models
 */

namespace static_press\includes\models;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/models/class-static-press-model-url.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/repositories/class-static-press-repository.php';
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\repositories\Static_Press_Repository;
/**
 * Model URL term.
 */
class Static_Press_Model_Url_Term extends Static_Press_Model_Url {
	/**
	 * Constructor.
	 * 
	 * @param Object  $term     Term.
	 * @param string  $taxonomy Taxonomy.
	 * @param string  $modified Modified.
	 * @param integer $count    count.
	 */
	public function __construct( $term, $taxonomy, $modified, $count ) {
		parent::__construct(
			null,
			Static_Press_Model_Url::TYPE_TERM_ARCHIVE,
			apply_filters( 'StaticPress::get_url', get_term_link( $term->slug, $taxonomy ) ),
			intval( $term->term_id ),
			$term->taxonomy,
			$term->parent,
			$this->calculate_page_count( $count ),
			null,
			null,
			null,
			null,
			$modified
		);
	}

	/**
	 * Calculates page count.
	 * 
	 * @param integer $count Count.
	 */
	private function calculate_page_count( $count ) {
		return intval( $count / intval( get_option( 'posts_per_page' ) ) ) + 1;
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
				Static_Press_Repository::FIELD_NAME_TYPE   => $this->get_type(),
				Static_Press_Repository::FIELD_NAME_OBJECT_ID => $this->get_object_id(),
				Static_Press_Repository::FIELD_NAME_OBJECT_TYPE => $this->get_object_type(),
				Static_Press_Repository::FIELD_NAME_PARENT => $this->get_parent(),
				Static_Press_Repository::FIELD_NAME_PAGES  => $this->get_pages(),
				Static_Press_Repository::FIELD_NAME_LAST_MODIFIED => $this->get_last_modified(),
			)
		);
	}
}

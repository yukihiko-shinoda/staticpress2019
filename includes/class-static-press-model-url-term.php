<?php
/**
 * Class Static_Press_Model_Url_Term
 *
 * @package static_press\includes
 */

namespace static_press\includes;

/**
 * Model URL term.
 */
class Static_Press_Model_Url_Term extends Static_Press_Model_Url {
	/**
	 * Constructor.
	 * 
	 * @param Object                  $term              Term.
	 * @param string                  $taxonomy          Taxonomy.
	 * @param Static_Press_Repository $repository        Repository.
	 * @param Date_Time_Factory       $date_time_factory Date time factory.
	 */
	public function __construct( $term, $taxonomy, $repository, $date_time_factory ) {
		list( $modified, $page_count ) = $this->get_term_info( $term->term_id, $repository, $date_time_factory );
		parent::__construct(
			null,
			Static_Press_Model_Url::TYPE_TERM_ARCHIVE,
			apply_filters( 'StaticPress::get_url', get_term_link( $term->slug, $taxonomy ) ),
			intval( $term->term_id ),
			$term->taxonomy,
			$term->parent,
			$page_count,
			null,
			null,
			null,
			null,
			$modified
		);
	}

	/**
	 * Gets term information.
	 * 
	 * @param int                     $term_id           Term ID.
	 * @param Static_Press_Repository $repository        Repository.
	 * @param Date_Time_Factory       $date_time_factory Date time factory.
	 */
	private function get_term_info( $term_id, $repository, $date_time_factory ) {
		$result = $repository->get_term_info( $term_id, get_post_types( array( 'public' => true ) ) );
		if ( ! is_wp_error( $result ) ) {
			$modified = $result->last_modified;
			$count    = $result->count;
		} else {
			$modified = $date_time_factory->create_date( 'Y-m-d h:i:s' );
			$count    = 1;
		}
		$page_count = intval( $count / intval( get_option( 'posts_per_page' ) ) ) + 1;
		return array( $modified, $page_count );
	}

	/**
	 * Converts to array.
	 * 
	 * @return array
	 */
	public function to_array() {
		return array(
			Static_Press_Repository::FIELD_NAME_TYPE      => $this->get_type(),
			Static_Press_Repository::FIELD_NAME_URL       => $this->get_url(),
			Static_Press_Repository::FIELD_NAME_OBJECT_ID => $this->get_object_id(),
			Static_Press_Repository::FIELD_NAME_OBJECT_TYPE => $this->get_object_type(),
			Static_Press_Repository::FIELD_NAME_PARENT    => $this->get_parent(),
			Static_Press_Repository::FIELD_NAME_PAGES     => $this->get_pages(),
			Static_Press_Repository::FIELD_NAME_LAST_MODIFIED => $this->get_last_modified(),
		);
	}
}

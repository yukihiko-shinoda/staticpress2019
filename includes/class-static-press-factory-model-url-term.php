<?php
/**
 * Class Static_Press_Factory_Model_Url_Static_File
 *
 * @package static_press\includes
 */

namespace static_press\includes;

/**
 * Path converter.
 */
class Static_Press_Factory_Model_Url_Term {
	/**
	 * Date time factory.
	 * 
	 * @var Static_Press_Date_Time_Factory
	 */
	private $date_time_factory;
	/**
	 * Repository.
	 * 
	 * @var Static_Press_Repository
	 */
	private $repository;

	/**
	 * Constructor.
	 * 
	 * @param Date_Time_Factory $date_time_factory Date time factory.
	 */
	public function __construct( $date_time_factory ) {
		$this->date_time_factory = $date_time_factory;
		$this->repository        = new Static_Press_Repository();
	}

	/**
	 * Creates.
	 * 
	 * @param Object $term     Term.
	 * @param Object $taxonomy Taxonomy.
	 */
	public function create( $term, $taxonomy ) {
		$result = $this->repository->get_term_info( $term->term_id, get_post_types( array( 'public' => true ) ) );
		if ( is_wp_error( $result ) ) {
			return new Static_Press_Model_Url_Term(
				$term,
				$taxonomy,
				$this->date_time_factory->create_date( 'Y-m-d h:i:s' ),
				1
			);
		}
		return new Static_Press_Model_Url_Term(
			$term,
			$taxonomy,
			$result->last_modified,
			$result->count
		);
	}
}

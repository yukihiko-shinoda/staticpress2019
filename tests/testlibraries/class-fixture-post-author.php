<?php
/**
 * Class Fixture_Post_Author
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

require_once dirname( __FILE__ ) . '/../testlibraries/class-fixture-post.php';
use static_press\tests\testlibraries\Fixture_Post;
/**
 * Fixture post author.
 */
class Fixture_Post_Author extends Fixture_Post {
	/**
	 * Author ID.
	 * 
	 * @var integer
	 */
	public $author_id;

	/**
	 * Constructor.
	 * 
	 * @param array $postarr Post array.
	 */
	public function __construct( $postarr ) {
		parent::__construct( $postarr );
		$this->author_id = $postarr['post_author'];
	}
}

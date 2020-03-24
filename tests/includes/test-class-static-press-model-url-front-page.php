<?php
/**
 * Class Static_Press_Model_Url_Front_Page
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-test-utility.php';
use static_press\includes\Static_Press_Model_Url_Front_Page;
use static_press\tests\testlibraries\Test_Utility;
use static_press\tests\testlibraries\Model_Url;
/**
 * Reposistory test case.
 */
class Static_Press_Model_Url_Front_Page_Test extends \WP_UnitTestCase {
	/**
	 * Constructor should sets appropriate values to properties.
	 */
	public function test() {
		$expect         = array(
			'type'          => Model_Url::TYPE_FRONT_PAGE,
			'url'           => '/',
			'last_modified' => Test_Utility::DATE_FOR_TEST,
			'enable'        => null,
		);
		$url_front_page = new Static_Press_Model_Url_Front_Page(
			Test_Utility::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' )
		);
		$this->assertEquals( $expect, $url_front_page->to_array() );
	}
}

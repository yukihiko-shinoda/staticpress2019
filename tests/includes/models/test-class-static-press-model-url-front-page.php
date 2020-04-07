<?php
/**
 * Class Static_Press_Model_Url_Front_Page_Test
 *
 * @package static_press\tests\includes\models
 */

namespace static_press\tests\includes\models;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-mock-creator.php';
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\models\Static_Press_Model_Url_Front_Page;
use static_press\tests\testlibraries\creators\Mock_Creator;
/**
 * Static_Press_Model_Url_Front_Page test case.
 */
class Static_Press_Model_Url_Front_Page_Test extends \WP_UnitTestCase {
	/**
	 * Constructor should sets appropriate values to properties.
	 */
	public function test() {
		$expect         = array(
			'type'          => Static_Press_Model_Url::TYPE_FRONT_PAGE,
			'url'           => '/',
			'last_modified' => Mock_Creator::DATE_FOR_TEST,
			'enable'        => null,
		);
		$url_front_page = new Static_Press_Model_Url_Front_Page(
			Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' )
		);
		$this->assertEquals( $expect, $url_front_page->to_array() );
	}
}

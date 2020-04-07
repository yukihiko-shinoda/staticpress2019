<?php
/**
 * Class Static_Press_Model_Url_Other_Test
 *
 * @package static_press\tests\includes\models
 */

namespace static_press\tests\includes\models;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-mock-creator.php';
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\models\Static_Press_Model_Url_Other;
use static_press\tests\testlibraries\creators\Mock_Creator;
/**
 * Static_Press_Model_Url_Other test case.
 */
class Static_Press_Model_Url_Other_Test extends \WP_UnitTestCase {
	/**
	 * Constructor should set.
	 */
	public function test_constructor() {
		$model_url_other = new Static_Press_Model_Url_Other( '/', Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' ) );
		$this->assertEquals( '/', $model_url_other->get_url() );
		$this->assertEquals(
			array(
				'type'          => Static_Press_Model_Url::TYPE_OTHER_PAGE,
				'last_modified' => Mock_Creator::DATE_FOR_TEST,
				'url'           => '/',
				'enable'        => null,
			),
			$model_url_other->to_array()
		);
	}
}

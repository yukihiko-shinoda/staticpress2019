<?php
/**
 * Class Static_Press_Repository_Test
 *
 * @package static_press\tests\includes\repositories
 */

namespace static_press\tests\includes\repositories;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/class-expect-url.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/repositories/class-repository-for-test.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-mock-creator.php';
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\models\Static_Press_Model_Url_Other;
use static_press\includes\repositories\Static_Press_Repository;
use static_press\includes\repositories\Static_Press_Repository_Progress;
use static_press\includes\Static_Press_Url_Updater;
use static_press\tests\testlibraries\Expect_Url;
use static_press\tests\testlibraries\repositories\Repository_For_Test;
use static_press\tests\testlibraries\creators\Mock_Creator;

/**
 * Static_Press_Repository test case.
 */
class Static_Press_Repository_Test extends \WP_UnitTestCase {
	/**
	 * Property $url_table should be WordPress database prefix + 'urls'.
	 */
	public function test_constructor() {
		$repository = new Static_Press_Repository();
		$this->assertSame( Repository_For_Test::url_table(), $this->getPropertyValue( $repository, 'url_table' ) );
	}

	/**
	 * Function get_all_url() should return array of inserted URL object.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_get_all_url() {
		$date_time_factory = Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' );
		$urls              = array(
			new Static_Press_Model_Url_Other( '/', $date_time_factory ),
			new Static_Press_Model_Url_Other( '/test/', $date_time_factory ),
		);

		$repository  = new Static_Press_Repository();
		$url_updater = new Static_Press_Url_Updater( $repository, null );
		$url_updater->update( $urls );
		$transient_service = new Static_Press_Repository_Progress();
		$start_time        = $transient_service->fetch_start_time();
		$results           = $repository->get_all_url( $start_time );
		Expect_Url::assert_url(
			$this,
			array(
				new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
				new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
			),
			$results
		);
	}
}

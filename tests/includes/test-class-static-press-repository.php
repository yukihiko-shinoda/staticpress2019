<?php
/**
 * Class Static_Press_Repository_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-expect-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url.php';
use static_press\includes\Static_Press_Model_Url_Other;
use static_press\includes\Static_Press_Repository;
use static_press\includes\Static_Press_Transient_Service;
use static_press\includes\Static_Press_Url_Updater;
use static_press\tests\testlibraries\Expect_Url;
use static_press\tests\testlibraries\Model_Url;
use static_press\tests\testlibraries\Test_Utility;

/**
 * Reposistory test case.
 */
class Static_Press_Repository_Test extends \WP_UnitTestCase {
	/**
	 * Function get_all_url() should return array of inserted URL object.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_get_all_url() {
		$date_time_factory = Test_Utility::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' );
		$urls              = array(
			new Static_Press_Model_Url_Other( '/', $date_time_factory ),
			new Static_Press_Model_Url_Other( '/test/', $date_time_factory ),
		);

		$repository  = new Static_Press_Repository();
		$url_updater = new Static_Press_Url_Updater( $repository, null );
		$url_updater->update( $urls );
		$transient_service = new Static_Press_Transient_Service();
		$start_time        = $transient_service->fetch_start_time();
		$results           = $repository->get_all_url( $start_time );
		Expect_Url::assert_url(
			$this,
			array(
				new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
				new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
			),
			$results
		);
	}
}

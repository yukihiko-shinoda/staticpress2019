<?php
/**
 * Class Static_Press_Static_File_Creator_Local_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-expect-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-repository-for-test.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-test-utility.php';
use static_press\includes\Static_Press_Model_Url;
use static_press\includes\Static_Press_Repository;
use static_press\includes\Static_Press_Transient_Service;
use static_press\includes\Static_Press_Static_File_Creator_Local;
use static_press\tests\testlibraries\Expect_Url;
use static_press\tests\testlibraries\Model_Url;
use static_press\tests\testlibraries\Repository_For_Test;
use static_press\tests\testlibraries\Test_Utility;

/**
 * Static_Press_Static_File_Creator_Local test case.
 */
class Static_Press_Static_File_Creator_Local_Test extends \WP_UnitTestCase {
	const OUTPUT_DIRECTORY = '/tmp/static/';
	/**
	 * Function delete_url() should delete URLs specified by key "url" of arrays.
	 */
	public function test_delete_url() {
		Repository_For_Test::insert_url(
			new Model_Url(
				1,
				Static_Press_Model_Url::TYPE_OTHER_PAGE,
				'/test1/',
				0,
				'',
				0,
				1,
				1,
				'',
				'0000-00-00 00:00:00',
				0,
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00'
			)
		);
		Repository_For_Test::insert_url(
			new Model_Url(
				2,
				Static_Press_Model_Url::TYPE_OTHER_PAGE,
				'/test2/',
				0,
				'',
				0,
				1,
				1,
				'',
				'0000-00-00 00:00:00',
				0,
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00'
			)
		);
		Repository_For_Test::insert_url(
			new Model_Url(
				3,
				Static_Press_Model_Url::TYPE_OTHER_PAGE,
				'/test3/',
				0,
				'',
				0,
				1,
				1,
				'',
				'0000-00-00 00:00:00',
				0,
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00'
			)
		);
		Repository_For_Test::insert_url(
			new Model_Url(
				4,
				Static_Press_Model_Url::TYPE_OTHER_PAGE,
				'/test4/',
				0,
				'',
				0,
				1,
				1,
				'',
				'0000-00-00 00:00:00',
				0,
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00',
				'0000-00-00 00:00:00'
			)
		);
		$parameter               = array(
			array(),
			array( 'url' => '/test1/' ),
			array( 'url' => '/test3/' ),
		);
		$expect_urls_in_database = array(
			new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test2/', '1' ),
			new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test4/', '1' ),
		);

		$static_press = new Static_Press_Static_File_Creator_Local(
			Static_Press_Model_Url::TYPE_STATIC_FILE,
			self::OUTPUT_DIRECTORY,
			null,
			new Static_Press_Repository(),
			Test_Utility::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s', '2019-12-23 12:34:56' )
		);
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'delete_url' );
		$method->setAccessible( true );
		$actual = $method->invokeArgs( $static_press, array( $parameter ) );
		$this->assertEquals( $parameter, $actual );
		$transient_service = new Static_Press_Transient_Service();
		$start_time        = $transient_service->fetch_start_time();
		$repository        = new Static_Press_Repository();
		$results           = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}
}

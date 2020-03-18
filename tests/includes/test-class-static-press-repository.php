<?php
/**
 * Class Static_Press_Repository_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-expect-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url.php';
use static_press\includes\Static_Press_Ajax_Init;
use static_press\includes\Static_Press_Repository;
use static_press\tests\testlibraries\Expect_Url;
use static_press\tests\testlibraries\Model_Url;

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
		$urls = array(
			array(
				'url' => '/',
			),
			array(
				'url' => '/test/',
			),
		);

		$repository   = new Static_Press_Repository();
		$static_press = new Static_Press_Ajax_Init(
			null,
			null,
			$repository,
			null
		);
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'update_url' );
		$method->setAccessible( true );
		$method->invokeArgs( $static_press, array( $urls ) );
		$method = $reflection->getMethod( 'fetch_start_time' );
		$method->setAccessible( true );
		$start_time = $method->invokeArgs( $static_press, array() );
		$results    = $repository->get_all_url( $start_time );
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

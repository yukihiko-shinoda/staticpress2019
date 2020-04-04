<?php
/**
 * Class Static_Press_Response_Processor_200_Crawl_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-expect-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-repository-for-test.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-mock-creator.php';
use static_press\includes\Static_Press_Model_Url;
use static_press\includes\Static_Press_Model_Url_Other;
use static_press\includes\Static_Press_Repository;
use static_press\includes\Static_Press_Response_Processor_200_Crawl;
use static_press\includes\Static_Press_Transient_Service;
use static_press\tests\testlibraries\Expect_Url;
use static_press\tests\testlibraries\Repository_For_Test;
use static_press\tests\testlibraries\Mock_Creator;

/**
 * Static_Press_Response_Processor_200_Crawl test case.
 */
class Static_Press_Response_Processor_200_Crawl_Test extends \WP_UnitTestCase {
	/**
	 * Test steps for crawl_body().
	 *
	 * @dataProvider provider_crawl_body
	 *
	 * @param string       $content                 Argument.
	 * @param Expect_Url[] $expect_urls_in_database Expect URLs in table.
	 *
	 * @throws ReflectionException     When fail to create ReflectionClass instance.
	 */
	public function test_crawl_body( $content, $expect_urls_in_database ) {
		$date_time_factoy_mock = Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' );
		$urls                  = array(
			new Static_Press_Model_Url_Other( '/', $date_time_factoy_mock ),
			new Static_Press_Model_Url_Other( '/test/', $date_time_factoy_mock ),
		);
		foreach ( $urls as $url ) {
			$url->judge_to_dump( '/' );
			Repository_For_Test::insert_url( $url );
		}

		$this->invoke_private_method( 'crawl_body', array( $content ) );
		$transient_service = new Static_Press_Transient_Service( $date_time_factoy_mock );
		$start_time        = $transient_service->fetch_start_time();
		$repository        = new Static_Press_Repository();
		$results           = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function crawl_body() should insert URL to table when any of self or parent URL exists.
	 * Function crawl_body() should add URLs of other page included in content
	 * when content includes link to other page.
	 *
	 * @return array[]
	 */
	public function provider_crawl_body() {
		return array(
			array(
				'',
				array(
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
				),
			),
			array(
				'href="http://example.org/test"',
				array(
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
				),
			),
			array(
				'href="http://example.org/test/test"',
				array(
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test/test/', '1' ),
				),
			),
			array(
				'href="http://example.org/test/test/index.html"' . "\n" . 'href="http://example.org/test/test2/index.html"',
				array(
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test/test/index.html', '1' ),
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test/test2/index.html', '1' ),
				),
			),
		);
	}

	/**
	 * Test steps for crawl_url().
	 *
	 * @dataProvider provider_crawl_url
	 *
	 * @param string       $url                     Argument.
	 * @param Expect_Url[] $expect_urls_in_database Expect URLs in table.
	 *
	 * @throws ReflectionException     When fail to create ReflectionClass instance.
	 */
	public function test_crawl_url( $url, $expect_urls_in_database ) {
		$date_time_factoy_mock = Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' );
		$urls                  = array(
			new Static_Press_Model_Url_Other( '/', $date_time_factoy_mock ),
			new Static_Press_Model_Url_Other( '/test/', $date_time_factoy_mock ),
		);
		foreach ( $urls as $url_object ) {
			$url_object->judge_to_dump( '/' );
			Repository_For_Test::insert_url( $url_object );
		}

		$this->invoke_private_method( 'crawl_url', array( $url ) );
		$transient_service = new Static_Press_Transient_Service( $date_time_factoy_mock );
		$start_time        = $transient_service->fetch_start_time();
		$repository        = new Static_Press_Repository();
		$results           = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function crawl_url() should return empty array when all of self or parent URL exists.
	 * Function crawl_url() shouldn't insert URL to table when all of self or parent URL exists.
	 * Function crawl_url() shouldn't add any URL when content doesn't include link to other page.
	 * Function crawl_url() should return array of map of all existing URL data
	 * when any of self or parent URL doesn't exist.
	 *
	 * @return array[]
	 */
	public function provider_crawl_url() {
		return array(
			array(
				'/',
				array(
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
				),
			),
			array(
				'/test/',
				array(
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
				),
			),
			array(
				'/test/index.html',
				array(
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
				),
			),
			array(
				'/test/test/index.html',
				array(
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
					new Expect_Url( Static_Press_Model_Url::TYPE_OTHER_PAGE, '/test/test/', '1' ),
				),
			),
		);
	}

	/**
	 * Test steps for has_list().
	 *
	 * @dataProvider provider_has_listed
	 *
	 * @param string $link   Argument.
	 * @param bool   $expect Expect return value.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_has_listed( $link, $expect ) {
		$date_time_factoy_mock = Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' );
		$urls                  = array(
			new Static_Press_Model_Url_Other( '/', $date_time_factoy_mock ),
		);
		foreach ( $urls as $url ) {
			Repository_For_Test::insert_url( $url );
		}
		$static_press = new Static_Press_Response_Processor_200_Crawl(
			null,
			new Static_Press_Repository(),
			null
		);
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'has_listed' );
		$method->setAccessible( true );
		$result = $method->invokeArgs( $static_press, array( $link ) );
		$this->assertEquals( $expect, $result );
	}

	/**
	 * Function has_listed() should return whether URL has listed or not.
	 *
	 * @return array[]
	 */
	public function provider_has_listed() {
		return array(
			array( '', true ),
			array( '/', true ),
			array( '/test', false ),
			array( '/test.php', false ),
		);
	}

	/**
	 * Invokes private method.
	 * 
	 * @param string $method_name Method name.
	 * @param array  $arguments   Arguments.
	 */
	private function invoke_private_method( $method_name, $arguments ) {
		$date_time_factoy_mock = Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' );
		$static_press          = new Static_Press_Response_Processor_200_Crawl(
			null,
			new Static_Press_Repository(),
			$date_time_factoy_mock
		);
		$reflection            = new \ReflectionClass( get_class( $static_press ) );
		$method                = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		$method->invokeArgs( $static_press, $arguments );
	}
}

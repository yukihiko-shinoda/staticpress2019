<?php
/**
 * Class Static_Press_Response_Processor_200_Crawl_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-expect-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-test-utility.php';
use static_press\includes\Static_Press_Repository;
use static_press\includes\Static_Press_Response_Processor_200_Crawl;
use static_press\includes\Static_Press_Transient_Service;
use static_press\tests\testlibraries\Expect_Url;
use static_press\tests\testlibraries\Model_Url;
use static_press\tests\testlibraries\Test_Utility;

/**
 * Static_Press_Response_Processor_200_Crawl test case.
 */
class Static_Press_Response_Processor_200_Crawl_Test extends \WP_UnitTestCase {
	const DATE_FOR_TEST = '2019-12-23 12:34:56';
	/**
	 * Test steps for other_url().
	 *
	 * @dataProvider provider_other_url
	 *
	 * @param string       $content                 Argument.
	 * @param string       $url                     Argument.
	 * @param array        $expect                  Expect return value.
	 * @param Expect_Url[] $expect_urls_in_database Expect URLs in table.
	 *
	 * @throws ReflectionException     When fail to create ReflectionClass instance.
	 */
	public function test_other_url( $content, $url, $expect, $expect_urls_in_database ) {
		$urls                  = array(
			array(
				'url' => '/',
			),
			array(
				'url' => '/test/',
			),
		);
		$date_time_factoy_mock = Test_Utility::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s', self::DATE_FOR_TEST );
		$static_press          = new Static_Press_Response_Processor_200_Crawl(
			null,
			new Static_Press_Repository(),
			$date_time_factoy_mock
		);
		$reflection            = new \ReflectionClass( get_class( $static_press ) );
		$method                = $reflection->getMethod( 'update_url' );
		$method->setAccessible( true );
		$method->invokeArgs( $static_press, array( $urls ) );
		$method = $reflection->getMethod( 'other_url' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $static_press, array( $content, $url ) );
		$this->assertEquals( $expect, $result );
		$transient_service = new Static_Press_Transient_Service( $date_time_factoy_mock );
		$start_time        = $transient_service->fetch_start_time();
		$repository        = new Static_Press_Repository();
		$results           = $repository->get_all_url( $start_time );
		Expect_Url::assert_url( $this, $expect_urls_in_database, $results );
	}

	/**
	 * Function other_url() should return empty array when all of self or parent URL exists.
	 * Function other_url() shouldn't insert URL to table when all of self or parent URL exists.
	 * Function other_url() shouldn't add any URL when content doesn't include link to other page.
	 * Function other_url() should return array of map of all existing URL data
	 * when any of self or parent URL doesn't exist.
	 * Function other_url() should insert URL to table when any of self or parent URL exists.
	 * Function other_url() should add URLs of other page included in content
	 * when content includes link to other page.
	 *
	 * @return array[]
	 */
	public function provider_other_url() {
		return array(
			array(
				'',
				'/',
				array(),
				array(
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
				),
			),
			array(
				'',
				'/test/',
				array(),
				array(
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
				),
			),
			array(
				'',
				'/test/index.html',
				array(),
				array(
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
				),
			),
			array(
				'',
				'/test/test/index.html',
				array(
					array(
						'url'           => '/test/test/',
						'last_modified' => self::DATE_FOR_TEST,
					),
				),
				array(
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/test/', '1' ),
				),
			),
			array(
				'href="http://example.org/test"',
				'/',
				array(),
				array(
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
				),
			),
			array(
				'href="http://example.org/test/test"',
				'/',
				array(
					array(
						'url'           => '/test/test/',
						'last_modified' => self::DATE_FOR_TEST,
					),
				),
				array(
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/test/', '1' ),
				),
			),
			array(
				'href="http://example.org/test/test/"',
				'/',
				array(
					array(
						'url'           => '/test/test/',
						'last_modified' => self::DATE_FOR_TEST,
					),
				),
				array(
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/test/', '1' ),
				),
			),
			array(
				'href="http://example.org/test/test/index.html"' . "\n" . 'href="http://example.org/test/test2/index.html"',
				'/',
				array(
					array(
						'url'           => '/test/test/index.html',
						'last_modified' => self::DATE_FOR_TEST,
					),
					array(
						'url'           => '/test/test2/index.html',
						'last_modified' => self::DATE_FOR_TEST,
					),
				),
				array(
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/test/index.html', '1' ),
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/test2/index.html', '1' ),
				),
			),
			array(
				'href="http://example.org/test/test/index.html"' . "\n" . 'href="http://example.org/test/test2/index.html"',
				'/test/test/index.html',
				array(
					array(
						'url'           => '/test/test/',
						'last_modified' => self::DATE_FOR_TEST,
					),
					array(
						'url'           => '/test/test/index.html',
						'last_modified' => self::DATE_FOR_TEST,
					),
					array(
						'url'           => '/test/test2/index.html',
						'last_modified' => self::DATE_FOR_TEST,
					),
				),
				array(
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/', '1' ),
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/', '1' ),
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/test/', '1' ),
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/test/index.html', '1' ),
					new Expect_Url( Model_Url::TYPE_OTHER_PAGE, '/test/test2/index.html', '1' ),
				),
			),
		);
	}

	/**
	 * Test steps for url_exists().
	 *
	 * @dataProvider provider_url_exists
	 *
	 * @param string $link   Argument.
	 * @param bool   $expect Expect return value.
	 *
	 * @throws ReflectionException When fail to create ReflectionClass instance.
	 */
	public function test_url_exists( $link, $expect ) {
		$urls = array(
			array(
				'url' => '/',
			),
		);

		$static_press = new Static_Press_Response_Processor_200_Crawl(
			null,
			new Static_Press_Repository(),
			null
		);
		$reflection   = new \ReflectionClass( get_class( $static_press ) );
		$method       = $reflection->getMethod( 'update_url' );
		$method->setAccessible( true );
		$method->invokeArgs( $static_press, array( $urls ) );
		$method = $reflection->getMethod( 'url_exists' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $static_press, array( $link ) );
		$this->assertEquals( $expect, $result );
	}

	/**
	 * Function test_rul_exists() should return whether URL exists or not.
	 *
	 * @return array[]
	 */
	public function provider_url_exists() {
		return array(
			array( '', true ),
			array( '/', true ),
			array( '/test', false ),
			array( '/test.php', false ),
		);
	}
}

<?php
/**
 * Model_Url_Handler
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

require_once dirname( __FILE__ ) . '/./class-business-logic-exception.php';
use static_press\includes\Static_Press_Model_Url;
use static_press\tests\testlibraries\Business_Logic_Exception;

/**
 * Class Model_Url_Handler
 */
class Model_Url_Handler {
	/**
	 * Asserts that URLs contains.
	 * 
	 * @param PHPUnit_Framework_TestCase $test_case   Test case.
	 * @param array                      $expect_urls Expect URLs.
	 * @param array                      $actual_urls Actual URLs.
	 * @throws Business_Logic_Exception Case when not contains.
	 */
	public static function assert_contains_urls( $test_case, $expect_urls, $actual_urls ) {
		$copy_expect_urls         = $expect_urls;
		$copy_actual_urls         = $actual_urls;
		$expect_url_not_contained = array();
		foreach ( $copy_expect_urls as $expect_url ) {
			try {
				$copy_actual_urls = self::assert_contains_url( $expect_url, $copy_actual_urls );
			} catch ( Business_Logic_Exception $exception ) {
				$expect_url_not_contained[] = $expect_url;
			}
		}
		$test_case->assertFalse(
			empty( $expect_url_not_contained ),
			"Actual URLs does not contain Expect URL. Not contained:\n" . var_export( $expect_url_not_contained, true )
		);
	}

	/**
	 * Asserts that URLs not contains.
	 * 
	 * @param PHPUnit_Framework_TestCase $test_case   Test case.
	 * @param Static_Press_Model_Url[]   $expect_urls Expect URLs.
	 * @param Static_Press_Model_Url[]   $actual_urls Actual URLs.
	 * @throws Business_Logic_Exception Case when contains.
	 */
	public static function assert_not_contains_urls( $test_case, $expect_urls, $actual_urls ) {
		$copy_expect_urls     = $expect_urls;
		$copy_actual_urls     = $actual_urls;
		$expect_url_contained = array();
		foreach ( $copy_expect_urls as $expect_url ) {
			try {
				$copy_actual_urls       = self::assert_contains_url( $expect_url, $copy_actual_urls );
				$expect_url_contained[] = $expect_url;
			} catch ( Business_Logic_Exception $exception ) {
				continue;
			}
		}
		$test_case->assertTrue(
			empty( $expect_url_contained ),
			"Actual URLs contains Expect URL. Contained:\nExpect URL contained:\n" . var_export( $expect_url_contained, true )
		);
	}

	/**
	 * Asserts that URL contains.
	 * 
	 * @param Static_Press_Model_Url   $expect_url  Expect URL.
	 * @param Static_Press_Model_Url[] $actual_urls Actual URLs.
	 * @return array Remaining actual URLs.
	 * @throws Business_Logic_Exception Case when not contains.
	 */
	private static function assert_contains_url( $expect_url, $actual_urls ) {
		$length_actual = count( $actual_urls );
		for ( $index = 0; $index < $length_actual; $index ++ ) {
			$actual_url = $actual_urls[ $index ];
			try {
				self::compare_url( $expect_url, $actual_url );
			} catch ( Business_Logic_Exception $exception ) {
				continue;
			}
			return array_splice( $actual_urls, $index, 1 );
		}
		throw new Business_Logic_Exception();
	}

	/**
	 * Compares two URLs.
	 * 
	 * @param Static_Press_Model_Url $expect Expect URL.
	 * @param Static_Press_Model_Url $actual Actual URL.
	 * @throws Business_Logic_Exception Case when different.
	 */
	private static function compare_url( $expect, $actual ) {
		if ( ! $expect instanceof $actual ) {
			throw new Business_Logic_Exception( 'Instance type is different. ' . self::debug_urls_instance_type( $expect, $actual ) );
		}
		if ( ! $expect->equals( $actual ) ) {
			throw new Business_Logic_Exception( 'Expect is not same with actual.' );
		}
	}

	/**
	 * Debug URLs.
	 * 
	 * @param mixed $expect Expect URL.
	 * @param mixed $actual Actual URL.
	 */
	private static function debug_urls_instance_type( $expect, $actual ) {
		return "EXPECT:\n" . get_class( $expect ) . "ACTUAL:\n" . get_class( $actual ) . "\n";
	}

	/**
	 * Asserts URLs.
	 * 
	 * @param PHPUnit_Framework_TestCase $test_case Test case.
	 * @param Static_Press_Model_Url[]   $expect    Expect URLs.
	 * @param Static_Press_Model_Url[]   $actual    Actual URLs.
	 */
	public static function assert_array_model_url( $test_case, $expect, $actual ) {
		$length_expect = count( $expect );
		$length_actual = count( $actual );
		$test_case->assertEquals(
			$length_expect,
			$length_actual,
			"Failed asserting that {$length_actual} matches expected {$length_expect}."
		);
		for ( $index = 0; $index < $length_expect; $index ++ ) {
			$expect_url = $expect[ $index ];
			$actual_url = $actual[ $index ];
			$test_case->assertEquals( $expect_url, $actual_url );
		}
	}

	/**
	 * Asserts URLs.
	 * 
	 * @deprecated Now this function is not required.
	 * @see compare_url()
	 * @param PHPUnit_Framework_TestCase $test_case Test case.
	 * @param array                      $expect    Expect URLs.
	 * @param array                      $actual    Actual URLs.
	 */
	public static function assert_urls( $test_case, $expect, $actual ) {
		$length_expect = count( $expect );
		$length_actual = count( $actual );
		$test_case->assertEquals(
			$length_expect,
			$length_actual,
			"Failed asserting that {$length_actual} matches expected {$length_expect}. URL list:\n" . self::urls_to_string( $actual )
		);
		for ( $index = 0; $index < $length_expect; $index ++ ) {
			$expect_url = $expect[ $index ];
			$actual_url = $actual[ $index ];
			$test_case->assertEquals(
				array_key_exists( 'last_modified', $expect_url ),
				array_key_exists( 'last_modified', $actual_url ),
				'Existance of last_modified is not same. Index = ' . $index
			);
			if ( array_key_exists( 'last_modified', $actual_url ) ) {
				if ( is_null( $actual_url['last_modified'] ) ) {
					$test_case->assertNull( $actual_url['last_modified'] );
				} else {
					$test_case->assertRegExp(
						'/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/i',
						$actual_url['last_modified'],
						'$actual_url[\last_modified\'] is not mutch regex \'/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/i\'. Index = ' . $index
					);
				}
			}
			unset( $expect_url['last_modified'] );
			unset( $actual_url['last_modified'] );
			$test_case->assertEquals( $expect_url, $actual_url );
		}
	}

	/**
	 * Converts urls to string.
	 * 
	 * @param array $urls URLs.
	 * @return string Converted URLs.
	 */
	private static function urls_to_string( $urls ) {
		$string = '';
		foreach ( $urls as $url ) {
			$string .= "{$url['url']}\n";
		}
		return $string;
	}
}

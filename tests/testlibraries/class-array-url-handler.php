<?php
/**
 * Array_Url_Handler
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

require_once dirname( __FILE__ ) . '/./class-business-logic-exception.php';
use static_press\tests\testlibraries\Business_Logic_Exception;

/**
 * Class Array_Url_Handler
 */
class Array_Url_Handler {
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
			"Actual URLs does not contain Expect URL. Not contained:\n" . self::convert_array_to_string( $expect_url_not_contained )
		);
	}

	/**
	 * Asserts that URLs not contains.
	 * 
	 * @param PHPUnit_Framework_TestCase $test_case   Test case.
	 * @param array                      $expect_urls Expect URLs.
	 * @param array                      $actual_urls Actual URLs.
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
			"Actual URLs contains Expect URL. Contained:\n" . self::convert_array_to_string( $expect_url_contained )
		);
	}

	/**
	 * Asserts that URL contains.
	 * 
	 * @param array $expect_url  Expect URL.
	 * @param array $actual_urls Actual URLs.
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
	 * @param array $expect Expect URL.
	 * @param array $actual Actual URL.
	 * @throws Business_Logic_Exception Case when different.
	 */
	private static function compare_url( $expect, $actual ) {
		if ( count( $expect ) !== count( $actual ) ) {
			throw new Business_Logic_Exception( 'Number of keys is different. ' . self::debug_urls( $expect, $actual ) );
		}
		if ( array_key_exists( 'last_modified', $actual ) ) {
			self::compare_last_modified( $expect, $actual );
			unset( $expect['last_modified'] );
			unset( $actual['last_modified'] );
		}
		$entries_only_expect = array_diff( $expect, $actual );
		$entries_only_actual = array_diff( $actual, $expect );
		if ( ! empty( $entries_only_expect ) || ! empty( $entries_only_actual ) ) {
			throw new Business_Logic_Exception( "Entries only expect containes:\n" . self::convert_array_to_string( $entries_only_expect ) . "Entries only actual containes:\n" . self::convert_array_to_string( $entries_only_actual ) );
		}
	}

	/**
	 * Compares last modified.
	 * 
	 * @param array $expect Expect URL.
	 * @param array $actual Actual URL.
	 * @throws Business_Logic_Exception Case when different.
	 * @throws \LogicException           Unexpected case.
	 */
	private static function compare_last_modified( $expect, $actual ) {
		$expect_has_last_modified = array_key_exists( 'last_modified', $expect );
		$actual_has_last_modified = array_key_exists( 'last_modified', $actual );
		switch ( true ) {
			case ( false === $expect_has_last_modified && false === $actual_has_last_modified ):
				return;
			case ( false === $expect_has_last_modified && true === $actual_has_last_modified ):
			case ( true === $expect_has_last_modified && false === $actual_has_last_modified ):
				throw new Business_Logic_Exception( 'Whether key "last_modified" incude or not is diffrent. ' . self::debug_urls( $expect, $actual ) );
			case ( true === $expect_has_last_modified && true === $actual_has_last_modified ):
				break;
			default:
				throw new \LogicException( 'Unexpected array. ' . self::debug_urls( $expect, $actual ) );
		}
		if ( is_null( $expect['last_modified'] ) ) {
			if ( ! is_null( $actual['last_modified'] ) ) {
				throw new Business_Logic_Exception( 'Existance of last_modified is not same. ' . self::debug_urls( $expect, $actual ) );
			}
			return;
		} else {
			if ( ! preg_match( '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/i', $actual['last_modified'] ) ) {
				throw new Business_Logic_Exception( 'Actual URL "last_modified" is not mutch regex \'/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/i\'.' );
			}
			return;
		}
	}

	/**
	 * Debug URLs.
	 * 
	 * @param array $expect Expect URL.
	 * @param array $actual Actual URL.
	 */
	private static function debug_urls( $expect, $actual ) {
		return "EXPECT:\n" . self::convert_array_to_string( $expect ) . "ACTUAL:\n" . self::convert_array_to_string( $actual );
	}

	/**
	 * Converts array to string.
	 * 
	 * @param array $array Array.
	 * @param int   $nest  Number of current nest of array.
	 * @return string String.
	 */
	private static function convert_array_to_string( $array, $nest = 0 ) {
		$indent = str_repeat( '  ', $nest );
		$string = "{$indent}{\n";
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				self::convert_array_to_string( $value, $nest + 1 );
			} else {
				$string .= "{$indent}  {$key} => {$value}\n";
			}
		}
		$string = "{$indent}}\n";
		return $string;
	}
}

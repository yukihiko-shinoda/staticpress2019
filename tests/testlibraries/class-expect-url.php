<?php
/**
 * Expect_Url
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

/**
 * Class Expect_Url
 */
class Expect_Url {
	/**
	 * Expect type of URL object.
	 *
	 * @var string
	 */
	public $type;
	/**
	 * Expect URL of URL object.
	 *
	 * @var string
	 */
	public $url;
	/**
	 * Expect pages of URL object.
	 *
	 * @var string
	 */
	public $pages;

	/**
	 * ExpectUrl constructor.
	 *
	 * @param string $type   Expect type of URL object.
	 * @param string $url    Expect URL of URL object.
	 * @param string $pages  Expect pages of URL object.
	 */
	public function __construct( $type, $url, $pages ) {
		$this->type  = $type;
		$this->url   = $url;
		$this->pages = $pages;
	}

	/**
	 * Asserts Url data.
	 *
	 * @param PHPUnit_Framework_TestCase $test_case Test case.
	 * @param Expect_Url[]               $expect    Expect url data.
	 * @param array|object|null          $actual    Actual url data.
	 */
	public static function assert_url( $test_case, $expect, $actual ) {
		$length_expect = count( $expect );
		$length_actual = count( $actual );
		$test_case->assertEquals( $length_expect, $length_actual, "Expect $length_expect, but $length_actual.\n\$expect = " . implode( ',', $expect ) . "\n\$actual = " . self::convert_actual_to_string( $actual ) );
		for ( $index = 0; $index < $length_expect; $index ++ ) {
			$expect_url = $expect[ $index ];
			$actual_url = $actual[ $index ];
			$test_case->assertInternalType( 'string', $actual_url->ID );
			$test_case->assertNotEquals( 0, intval( $actual_url->ID ) );
			$test_case->assertEquals( $expect_url->type, $actual_url->type );
			$test_case->assertEquals( $expect_url->url, $actual_url->url );
			$test_case->assertEquals( $expect_url->pages, $actual_url->pages );
		}
	}

	/**
	 * For debug.
	 * 
	 * @param  array $actual Actual URLs.
	 * @return string Converted string.
	 */
	private static function convert_actual_to_string( $actual ) {
		$string = '(';
		foreach ( $actual as $actual_url ) {
			$string .= "(ID = $actual_url->ID, Type = $actual_url->type, URL = $actual_url->url, Pages = $actual_url->pages)";
		}
		$string .= ')';
		return $string;
	}

	/**
	 * For debug.
	 */
	public function __toString() {
		return "Type = $this->type, URL = $this->url, Pages = $this->pages";
	}
}

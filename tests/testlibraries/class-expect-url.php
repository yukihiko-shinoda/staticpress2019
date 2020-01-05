<?php
/**
 * Expect_Url
 *
 * @package plugin\staticpress\tests\testlibraries
 */

namespace staticpress\tests\testlibraries;

/**
 * Class Expect_Url
 */
class Expect_Url {
	const TYPE_OTHER_PAGE = 'other_page';

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
	 * @param string $type  Expect type of URL object.
	 * @param string $url   Expect URL of URL object.
	 * @param string $pages Expect pages of URL object.
	 */
	public function __construct( $type, $url, $pages ) {
		$this->type  = $type;
		$this->url   = $url;
		$this->pages = $pages;
	}
}

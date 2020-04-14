<?php
/**
 * Class Static_Press_Model_Url_Fetched
 *
 * @package static_press\includes\models
 */

namespace static_press\includes\models;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/exceptions/class-static-press-business-logic-exception.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/models/class-static-press-model-url.php';
use static_press\includes\exceptions\Static_Press_Business_Logic_Exception;
use static_press\includes\models\Static_Press_Model_Url;
/**
 * Model URL other.
 */
class Static_Press_Model_Url_Fetched extends Static_Press_Model_Url {
	/**
	 * Constructor.
	 * 
	 * @param Object $result Result.
	 */
	public function __construct( $result ) {
		parent::__construct( $result->ID, $result->type, $result->url, null, null, null, $result->pages );
	}

	/**
	 * Getter.
	 */
	public function get_id_fetched() {
		return $this->get_id();
	}

	/**
	 * Getter.
	 */
	public function get_type_fetched() {
		return $this->get_type();
	}

	/**
	 * Getter.
	 */
	public function get_pages_fetched() {
		return $this->get_pages();
	}

	/**
	 * Returns whether has multiple pages or not.
	 * 
	 * @return bool true: has multiple pages, false: doesn't have multiple pages.
	 */
	public function has_multiple_page() {
		return $this->get_pages() > 1;
	}

	/**
	 * Returns whether is static file or not.
	 * 
	 * @return bool true: is static file, false: is not static file.
	 */
	public function is_static_file() {
		return self::TYPE_STATIC_FILE === $this->get_type() || self::TYPE_CONTENT_FILE === $this->get_type();
	}

	/**
	 * Creates static file.
	 * 
	 * @param int $page Page.
	 * @return string Local file path to static file.
	 * @throws Static_Press_Business_Logic_Exception Unexpected URL type.
	 */
	public function create_page_url( $page ) {
		$page_url = untrailingslashit( trim( $this->get_url() ) );
		switch ( $this->get_type_fetched() ) {
			case self::TYPE_TERM_ARCHIVE:
			case self::TYPE_AUTHOR_ARCHIVE:
			case self::TYPE_OTHER_PAGE:
				return sprintf( '%s/page/%d', $page_url, $page );
			case self::TYPE_SINGLE:
				return sprintf( '%s/%d', $page_url, $page );
			default:
				throw new Static_Press_Business_Logic_Exception();
		}
	}

	/**
	 * Converts to array.
	 * 
	 * @throws \LogicException This function should not be called.
	 */
	public function to_array() {
		throw new \LogicException();
	}
}

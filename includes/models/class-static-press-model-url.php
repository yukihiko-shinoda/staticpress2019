<?php
/**
 * Class Static_Press_Model_Url
 *
 * @package static_press\includes\models
 */

namespace static_press\includes\models;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/exceptions/class-static-press-business-logic-exception.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/repositories/class-static-press-repository.php';
use static_press\includes\exceptions\Static_Press_Business_Logic_Exception;
use static_press\includes\repositories\Static_Press_Repository;
/**
 * Model URL.
 */
abstract class Static_Press_Model_Url {
	const TYPE_FRONT_PAGE     = 'front_page';
	const TYPE_SINGLE         = 'single';
	const TYPE_TERM_ARCHIVE   = 'term_archive';
	const TYPE_AUTHOR_ARCHIVE = 'author_archive';
	const TYPE_SEO_FILES      = 'seo_files';
	const TYPE_OTHER_PAGE     = 'other_page';
	const TYPE_STATIC_FILE    = 'static_file';
	const TYPE_CONTENT_FILE   = 'content_file';
	/**
	 * ID.
	 * 
	 * @var int
	 */
	private $id;
	/**
	 * Type.
	 * 
	 * @var string
	 */
	private $type;
	/**
	 * URL.
	 * This URL should be related URL from home URL.
	 * 
	 * @var string
	 */
	private $url;
	/**
	 * Object ID.
	 * 
	 * @var int
	 */
	private $object_id;
	/**
	 * Object type.
	 * 
	 * @var string
	 */
	private $object_type;
	/**
	 * Parent.
	 * 
	 * @var int
	 */
	private $parent;
	/**
	 * Pages.
	 * 
	 * @var int
	 */
	private $pages;
	/**
	 * Eneble.
	 * 
	 * @var int
	 */
	private $enable;
	/**
	 * File name.
	 * 
	 * @var string
	 */
	private $file_name;
	/**
	 * File date.
	 * 
	 * @var DateTime
	 */
	private $file_date;
	/**
	 * Last status code.
	 * 
	 * @var int
	 */
	private $last_status_code;
	/**
	 * Last modified.
	 * 
	 * @var DateTime
	 */
	private $last_modified;
	/**
	 * Last upload.
	 * 
	 * @var DateTime
	 */
	private $last_upload;
	/**
	 * Create date.
	 * 
	 * @var DateTime
	 */
	private $create_date;

	/**
	 * Constructor.
	 * 
	 * @param int      $id              ID.
	 * @param string   $type            Type.
	 * @param string   $url             URL.
	 * @param int      $object_id       Object ID.
	 * @param string   $object_type     Object type.
	 * @param int      $parent          Parent.
	 * @param int      $pages           Pages.
	 * @param int      $enable          Enable.
	 * @param string   $file_name       File name.
	 * @param DateTime $file_date       File date.
	 * @param DateTime $last_status_code Last status code.
	 * @param DateTime $last_modified   Last modified.
	 * @param DateTime $last_upload     Last upload.
	 * @param DateTime $create_date     Create date.
	 */
	protected function __construct(
		$id, $type, $url, $object_id = null, $object_type = null, $parent = null, $pages = null, $enable = null,
		$file_name = null, $file_date = null, $last_status_code = null, $last_modified = null, $last_upload = null, $create_date = null
	) {
		$this->id               = $id;
		$this->type             = $type;
		$this->url              = $url;
		$this->object_id        = $object_id;
		$this->object_type      = $object_type;
		$this->parent           = $parent;
		$this->pages            = $pages;
		$this->enable           = $enable;
		$this->file_name        = $file_name;
		$this->file_date        = $file_date;
		$this->last_status_code = $last_status_code;
		$this->last_modified    = $last_modified;
		$this->last_upload      = $last_upload;
		$this->create_date      = $create_date;
	}

	/**
	 * Getter.
	 */
	protected function get_id() {
		return $this->id;
	}

	/**
	 * Getter.
	 */
	protected function get_type() {
		return $this->type;
	}

	/**
	 * Getter.
	 */
	public function get_url() {
		return $this->url;
	}

	/**
	 * Getter.
	 */
	protected function get_object_id() {
		return $this->object_id;
	}

	/**
	 * Getter.
	 */
	protected function get_object_type() {
		return $this->object_type;
	}

	/**
	 * Getter.
	 */
	protected function get_parent() {
		return $this->parent;
	}

	/**
	 * Getter.
	 */
	protected function get_pages() {
		return $this->pages;
	}

	/**
	 * Getter.
	 */
	private function get_enable() {
		return $this->enable;
	}

	/**
	 * Getter.
	 */
	protected function get_file_name() {
		return $this->file_name;
	}

	/**
	 * Getter.
	 */
	protected function get_file_date() {
		return $this->file_date;
	}

	/**
	 * Getter.
	 */
	protected function get_last_modified() {
		return $this->last_modified;
	}

	/**
	 * Getter.
	 */
	protected function get_last_status_code() {
		return $this->last_status_code;
	}

	/**
	 * Getter.
	 */
	protected function get_last_upload() {
		return $this->last_upload;
	}

	/**
	 * Judges whether this URL should dump or not.
	 * 
	 * @throws Static_Press_Business_Logic_Exception Case when this URL type is static file.
	 */
	public function judge_to_dump() {
		$this->enable = $this->classify();
	}

	/**
	 * Classifies URL whether should dump or not.
	 * 
	 * @return int should not dump: 0, should dump: 1
	 * @throws Static_Press_Business_Logic_Exception Case when this URL type is static file.
	 */
	public function classify() {
		switch ( true ) {
			case preg_match( '#\.php$#i', $this->get_url() ):      // Seems to intend PHP file.
			case preg_match( '#\?[^=]+[=]?#i', $this->get_url() ): // Seems to intend get request with parameter.
			case preg_match( '#/wp-admin/$#i', $this->get_url() ): // Seems to intend WordPress admin home page.
				return 0;
			case null === $this->get_type():
			case self::TYPE_STATIC_FILE != $this->get_type() && self::TYPE_CONTENT_FILE != $this->get_type():
				return 1;
			default:
				throw new Static_Press_Business_Logic_Exception();
		}
	}

	/**
	 * Judges whether this URL should dump or not.
	 * 
	 * @param Static_Press_Static_File_Judger $static_file_judger Static file judger.
	 */
	public function judge_to_dump_for_static_file( $static_file_judger ) {
		$this->enable = $static_file_judger->classify( $this );
	}

	/**
	 * Converts to array.
	 * 
	 * @return array
	 */
	abstract public function to_array();

	/**
	 * Converts to array.
	 * 
	 * @return array
	 */
	protected function to_array_common() {
		return array(
			Static_Press_Repository::FIELD_NAME_URL    => $this->get_url(),
			Static_Press_Repository::FIELD_NAME_ENABLE => $this->get_enable(),
		);
	}

	/**
	 * Compares.
	 * 
	 * @param Static_Press_Model_Url $that That.
	 * @return bool True: Equals. False: Not equals.
	 */
	public function equals( $that ) {
		$vars_this = get_object_vars( $this );
		$vars_that = get_object_vars( $that );
		foreach ( $vars_this as $key => $value ) {
			if ( $value !== $vars_that[ $key ] ) {
				return false;
			}
		}
		return true;
	}
}

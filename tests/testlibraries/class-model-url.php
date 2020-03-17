<?php
/**
 * Model_Url
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

/**
 * Class Model_Url
 */
class Model_Url {
	const TYPE_OTHER_PAGE  = 'other_page';
	const TYPE_STATIC_FILE = 'static_file';
	/**
	 * ID.
	 * 
	 * @var int
	 */
	public $id;
	/**
	 * Type.
	 * 
	 * @var string
	 */
	public $type;
	/**
	 * URL.
	 * 
	 * @var string
	 */
	public $url;
	/**
	 * Object ID.
	 * 
	 * @var int
	 */
	public $object_id;
	/**
	 * Object type.
	 * 
	 * @var string
	 */
	public $object_type;
	/**
	 * Parent.
	 * 
	 * @var int
	 */
	public $parent;
	/**
	 * Pages.
	 * 
	 * @var int
	 */
	public $pages;
	/**
	 * Eneble.
	 * 
	 * @var int
	 */
	public $enable;
	/**
	 * File name.
	 * 
	 * @var string
	 */
	public $file_name;
	/**
	 * File date.
	 * 
	 * @var DateTime
	 */
	public $file_date;
	/**
	 * Last status code.
	 * 
	 * @var int
	 */
	public $last_statuscode;
	/**
	 * Last modified.
	 * 
	 * @var DateTime
	 */
	public $last_modified;
	/**
	 * Last upload.
	 * 
	 * @var DateTime
	 */
	public $last_upload;
	/**
	 * Create date.
	 * 
	 * @var DateTime
	 */
	public $create_date;

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
	 * @param DateTime $last_statuscode Last status code.
	 * @param DateTime $last_modified   Last modified.
	 * @param DateTime $last_upload     Last upload.
	 * @param DateTime $create_date     Create date.
	 */
	public function __construct(
		$id, $type, $url, $object_id, $object_type, $parent, $pages, $enable,
		$file_name, $file_date, $last_statuscode, $last_modified, $last_upload, $create_date
	) {
		$this->id              = $id;
		$this->type            = $type;
		$this->url             = $url;
		$this->object_id       = $object_id;
		$this->object_type     = $object_type;
		$this->parent          = $parent;
		$this->pages           = $pages;
		$this->enable          = $enable;
		$this->file_name       = $file_name;
		$this->file_date       = $file_date;
		$this->last_statuscode = $last_statuscode;
		$this->last_modified   = $last_modified;
		$this->last_upload     = $last_upload;
		$this->create_date     = $create_date;
	}

	/**
	 * Converts to array.
	 * 
	 * @return array
	 */
	public function to_array() {
		return get_object_vars( $this );
	}
}

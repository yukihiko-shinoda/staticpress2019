<?php
/**
 * Class Static_Press_Remote_Getter
 *
 * @package static_press\includes
 */

namespace static_press\includes;

/**
 * Remote getter.
 */
class Static_Press_Remote_Getter {
	/**
	 * $remote_get_option Remote get options.
	 * 
	 * @var array
	 */
	private $remote_get_option;
	/**
	 * Constructor.
	 * 
	 * @param array $remote_get_option Remote get options.
	 */
	public function __construct( $remote_get_option ) {
		$this->remote_get_option = $remote_get_option;
	}

	/**
	 * Executes wp_remote_get.
	 * 
	 * @param string $url URL.
	 * @return array Responce.
	 */
	public function remote_get( $url ) {
		return wp_remote_get( $url, $this->remote_get_option );
	}
}

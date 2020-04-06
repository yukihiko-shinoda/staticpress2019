<?php
/**
 * Class Static_Press_Adapter_Transient
 *
 * @package static_press\includes
 */

namespace static_press\includes;

/**
 * Adapter for Transient.
 */
class Static_Press_Adapter_Transient {
	const EXPIRES       = 3600; // 60min * 60sec = 1hour
	const TRANSIENT_KEY = 'static static';
	/**
	 * Transient key.
	 * 
	 * @var string
	 */
	private $transient_key;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->transient_key = $this->get_transient_key();
	}

	/**
	 * Gets from transient.
	 * 
	 * @return array Transient.
	 */
	public function get_transient() {
		$param = get_transient( $this->transient_key );
		return is_array( $param ) ? $param : array();
	}

	/**
	 * Sets to transient.
	 * 
	 * @param array $param Parameter.
	 */
	public function set_transient( $param ) {
		set_transient( $this->transient_key, $param, self::EXPIRES );
	}

	/**
	 * Deletes from transient.
	 */
	public static function delete_transient() {
		$transient_key = self::get_transient_key();
		if ( get_transient( $transient_key ) ) {
			delete_transient( $transient_key );
		}
	}

	/**
	 * Gets transient key.
	 * 
	 * @return string Transient key.
	 */
	private static function get_transient_key() {
		$current_user = function_exists( 'wp_get_current_user' ) ? wp_get_current_user() : '';
		if ( isset( $current_user->ID ) && $current_user->ID ) {
			return self::TRANSIENT_KEY . " - {$current_user->ID}";
		} else {
			return self::TRANSIENT_KEY;
		}
	}
}

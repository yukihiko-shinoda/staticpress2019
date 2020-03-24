<?php
/**
 * Repository_For_Test
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

use static_press\includes\Static_Press_Model_Url_Fetched;
use static_press\tests\testlibraries\Model_Url;

/**
 * Class Repository_for_Test
 */
class Repository_For_Test {
	/**
	 * Inserts URL.
	 * 
	 * @param Model_Url $url URL.
	 */
	public static function insert_url( $url ) {
		global $wpdb;
		$array_url  = $url->to_array();
		$sql        = 'INSERT INTO ' . self::url_table();
		$sql       .= ' (`' . implode( '`,`', array_keys( $array_url ) ) . '`)';
		$insert_val = array();
		foreach ( $array_url as $val ) {
			$insert_val[] = $wpdb->prepare( '%s', $val );
		}
		$sql .= ' VALUES (' . implode( ',', $insert_val ) . ')';
		$wpdb->query( $sql );
	}

	/**
	 * Returns database table name for URL list.
	 */
	private static function url_table() {
		global $wpdb;
		return $wpdb->prefix . 'urls';
	}

	/**
	 * Gets all URL.
	 * 
	 * @return array
	 */
	public static function get_all_url() {
		global $wpdb;
		$sql         = 'SELECT * FROM ' . self::url_table();
		$results     = $wpdb->get_results( $sql );
		$array_model = array();
		foreach ( $results as $result ) {
			$array_model[] = new Static_Press_Model_Url_Fetched( $result );
		}
		return $array_model;
	}

	/**
	 * Truncates table.
	 */
	public static function truncate_table() {
		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . self::url_table() );
	}
}

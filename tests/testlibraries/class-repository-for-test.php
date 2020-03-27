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
	 * Creates legacy table.
	 */
	public static function create_legacy_table() {
		global $wpdb;
		$url_table = self::url_table();
		$wpdb->query(
			"CREATE TABLE `{$url_table}` (
				`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`type` varchar(255) NOT NULL DEFAULT 'other_page',
				`url` varchar(255) NOT NULL,
				`object_id` bigint(20) unsigned NULL,
				`object_type` varchar(20) NULL ,
				`parent` bigint(20) unsigned NOT NULL DEFAULT 0,
				`pages` bigint(20) unsigned NOT NULL DEFAULT 1,
				`file_name` varchar(255) NOT NULL,
				`file_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`last_statuscode` int(20) NULL,
				`last_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`last_upload` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`create_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY (`ID`),
				KEY `type` (`type`),
				KEY `url` (`url`),
				KEY `file_name` (`file_name`),
				KEY `file_date` (`file_date`),
				KEY `last_upload` (`last_upload`)
			)"
		);
	}

	/**
	 * Creates latest table.
	 */
	public static function create_latest_table() {
		global $wpdb;
		$url_table = self::url_table();
		$wpdb->query(
			"CREATE TABLE `{$url_table}` (
				`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`type` varchar(255) NOT NULL DEFAULT 'other_page',
				`url` varchar(255) NOT NULL,
				`object_id` bigint(20) unsigned NULL,
				`object_type` varchar(20) NULL ,
				`parent` bigint(20) unsigned NOT NULL DEFAULT 0,
				`pages` bigint(20) unsigned NOT NULL DEFAULT 1,
				`enable` int(1) unsigned NOT NULL DEFAULT '1',
				`file_name` varchar(255) NOT NULL DEFAULT '',
				`file_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`last_statuscode` int(20) NULL,
				`last_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`last_upload` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`create_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY (`ID`),
				KEY `type` (`type`),
				KEY `url` (`url`),
				KEY `file_name` (`file_name`),
				KEY `file_date` (`file_date`),
				KEY `last_upload` (`last_upload`)
			)"
		);
	}

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
	public static function url_table() {
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

	/**
	 * Ensures that table is dropped.
	 */
	public static function ensure_table_is_dropped() {
		global $wpdb;
		$url_table = self::url_table();
		if ( self::url_table_exists() ) {
			$wpdb->query( "DROP TABLE `{$url_table}`" );
		}
	}

	/**
	 * Ensures that table is created.
	 */
	public static function ensure_table_is_created() {
		if ( ! self::url_table_exists() ) {
			self::create_latest_table();
		}
	}

	/**
	 * Returns whether URL table exists or not.
	 * 
	 * @return boolean true: Exists, false: Not exists.
	 */
	public static function url_table_exists() {
		global $wpdb;
		$url_table = self::url_table();
		return $url_table === $wpdb->get_var( "show tables like '{$url_table}'" );
	}

	/**
	 * Returns whether column 'enable' exists or not.
	 * 
	 * @return boolean true: Exists, false: Not exists.
	 * @throws \LogicException Includes bug.
	 */
	public static function column_enable_exists() {
		try {
			self::get_column_enable();
			return true;
		} catch ( Business_Logic_Exception $exception ) {
			return false;
		}
	}

	/**
	 * Gets column 'enable'.
	 * 
	 * @return Object Column 'enable'.
	 * @throws \LogicException          Includes bug.
	 * @throws Business_Logic_Exception Case when column is not exist.
	 */
	public static function get_column_enable() {
		global $wpdb;
		$url_table = self::url_table();
		$columns   = $wpdb->get_results( "show columns from {$url_table} like 'enable'" );
		if ( count( $columns ) === 2 ) {
			throw new \LogicException();
		}
		if ( count( $columns ) === 0 ) {
			throw new Business_Logic_Exception();
		}
		return $columns[0];
	}
}

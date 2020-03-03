<?php
/**
 * Class Static_Press_Plugin_Information
 *
 * @package static_press\includes
 */

namespace static_press\includes;

/**
 * Plugin information.
 */
class Static_Press_Repository {
	/**
	 * Table name for list up URL.
	 * 
	 * @var string
	 */
	private $url_table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->url_table = $wpdb->prefix . 'urls';
	}

	/**
	 * Ensures that table for list up URL exists.
	 */
	public function ensure_table_exists() {
		global $wpdb;

		if ( $wpdb->get_var( "show tables like '{$this->url_table}'" ) != $this->url_table ) {
			$this->create_table();
		} elseif ( ! $wpdb->get_row( "show fields from `{$this->url_table}` where field = 'enable'" ) ) {
			$wpdb->query( "ALTER TABLE `{$this->url_table}` ADD COLUMN `enable` int(1) unsigned NOT NULL DEFAULT '1'" );
		}
	}

	/**
	 * Creates table for list up URL.
	 */
	public function create_table() {
		global $wpdb;

		if ( $wpdb->get_var( "show tables like '{$this->url_table}'" ) != $this->url_table ) {
			$wpdb->query(
				"CREATE TABLE `{$this->url_table}` (
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
	}

	/**
	 * Ensures that table for listup URL doesn't exist.
	 */
	public function ensure_table_not_exists() {
		global $wpdb;

		if ( $wpdb->get_var( "show tables like '{$this->url_table}'" ) == $this->url_table ) {
			$this->drop_table();
		}
	}

	/**
	 * Drops table.
	 */
	private function drop_table() {
		global $wpdb;
		$wpdb->query( "DROP TABLE `{$this->url_table}`" );
	}
}

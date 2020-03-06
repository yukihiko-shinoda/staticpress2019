<?php
/**
 * Class Static_Press_Plugin_Information
 *
 * @package static_press\includes
 */

namespace static_press\includes;

/**
 * Repository (DDD).
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

	/**
	 * Gets ID of URL.
	 * 
	 * @param  string $url URL.
	 * @return string ID
	 */
	public function get_id( $url ) {
		global $wpdb;
		$sql = $wpdb->prepare(
			"select ID from {$this->url_table} where url=%s limit 1",
			$url
		);

		return $wpdb->get_var( $sql );
	}

	/**
	 * Inserts URL.
	 * 
	 * @param array $url URL.
	 */
	public function insert_url( $url ) {
		global $wpdb;
		$sql        = "insert into {$this->url_table}";
		$sql       .= ' (`' . implode( '`,`', array_keys( $url ) ) . '`,`create_date`)';
		$insert_val = array();
		foreach ( $url as $val ) {
			$insert_val[] = $wpdb->prepare( '%s', $val );
		}
		$insert_val[] = $wpdb->prepare( '%s', date( 'Y-m-d h:i:s' ) );
		$sql         .= ' values (' . implode( ',', $insert_val ) . ')';
		$wpdb->query( $sql );
	}

	/**
	 * Updates URL.
	 * 
	 * @param string $id  ID.
	 * @param array  $url URL.
	 */
	public function update_url( $id, $url ) {
		global $wpdb;
		$sql        = "update {$this->url_table}";
		$update_sql = array();
		foreach ( $url as $key => $val ) {
			$update_sql[] = $wpdb->prepare( "{$key} = %s", $val );
		}
		$sql .= ' set ' . implode( ',', $update_sql );
		$sql .= $wpdb->prepare( ' where ID=%s', $id );
		$wpdb->query( $sql );
	}

	/**
	 * Gets all URL.
	 * 
	 * @param  string $start_time Start time.
	 * @return array
	 */
	public function get_all_url( $start_time ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT ID, type, url, pages FROM {$this->url_table} WHERE `last_upload` < %s and enable = 1",
			$start_time
		);
		return $wpdb->get_results( $sql );
	}

	/**
	 * Gets posts.
	 * 
	 * @param array $post_types Post type.
	 */
	public function get_posts( $post_types ) {
		global $wpdb;

		$concatenated_post_types = "'" . implode( "','", $post_types ) . "'";

		return $wpdb->get_results(
			"SELECT ID, post_type, post_content, post_status, post_modified
			from {$wpdb->posts}
			where (post_status = 'publish' or post_type = 'attachment')
			and post_type in ({$concatenated_post_types})
			order by post_type, ID"
		);
	}

	/**
	 * Gets post authors.
	 * 
	 * @param array $post_types Post type.
	 */
	public function get_post_authors( $post_types ) {
		global $wpdb;

		$concatenated_post_types = "'" . implode( "','", $post_types ) . "'";

		return $wpdb->get_results(
			"SELECT DISTINCT post_author, COUNT(ID) AS count, MAX(post_modified) AS modified
			FROM {$wpdb->posts} 
			where post_status = 'publish'
			and post_type in ({$concatenated_post_types})
			group by post_author
			order by post_author"
		);
	}
}

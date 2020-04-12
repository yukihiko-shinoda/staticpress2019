<?php
/**
 * Class Static_Press_Plugin_Information
 *
 * @package static_press\includes\repositories
 */

namespace static_press\includes\repositories;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/models/class-static-press-model-url-fetched.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-date-time-factory.php';
use static_press\includes\models\Static_Press_Model_Url_Fetched;
use static_press\includes\Static_Press_Date_Time_Factory;

/**
 * Repository (DDD).
 */
class Static_Press_Repository {
	const FIELD_NAME_TYPE             = 'type';
	const FIELD_NAME_URL              = 'url';
	const FIELD_NAME_OBJECT_ID        = 'object_id';
	const FIELD_NAME_OBJECT_TYPE      = 'object_type';
	const FIELD_NAME_PARENT           = 'parent';
	const FIELD_NAME_PAGES            = 'pages';
	const FIELD_NAME_ENABLE           = 'enable';
	const FIELD_NAME_FILE_NAME        = 'file_name';
	const FIELD_NAME_FILE_DATE        = 'file_date';
	const FIELD_NAME_LAST_STATUS_CODE = 'last_statuscode';
	const FIELD_NAME_LAST_UPLOAD      = 'last_upload';
	const FIELD_NAME_LAST_MODIFIED    = 'last_modified';
	/**
	 * Table name for list URL.
	 * 
	 * @var string
	 */
	private $url_table;
	/**
	 * Date time factory instance.
	 * 
	 * @var Static_Press_Date_Time_Factory
	 */
	private $date_time_factory;

	/**
	 * Constructor.
	 * 
	 * @param Static_Press_Date_Time_Factory $date_time_factory Date time factory.
	 */
	public function __construct( $date_time_factory = null ) {
		global $wpdb;
		$this->url_table         = $wpdb->prefix . 'urls';
		$this->date_time_factory = $date_time_factory ? $date_time_factory : new Static_Press_Date_Time_Factory();
	}

	/**
	 * Ensures that table for list URL exists.
	 */
	public function ensure_table_exists() {
		global $wpdb;

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$this->url_table}'" ) != $this->url_table ) {
			$this->create_table();
		} elseif ( ! $wpdb->get_row( "SHOW FIELDS FROM `{$this->url_table}` WHERE field = 'enable'" ) ) {
			$wpdb->query( "ALTER TABLE `{$this->url_table}` ADD COLUMN `enable` int(1) unsigned NOT NULL DEFAULT '1'" );
		}
	}

	/**
	 * Creates table for list URL.
	 */
	public function create_table() {
		global $wpdb;

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$this->url_table}'" ) != $this->url_table ) {
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
	 * Ensures that table for list URL doesn't exist.
	 */
	public function ensure_table_not_exists() {
		global $wpdb;

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$this->url_table}'" ) == $this->url_table ) {
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
	 * Drops table if exists.
	 */
	public function drop_table_if_exists() {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS `{$this->url_table}`" );
	}

	/**
	 * Trancates table.
	 */
	public function truncate_table() {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE `{$this->url_table}`" );
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
			"SELECT ID FROM {$this->url_table} WHERE url=%s LIMIT 1",
			$url
		);

		return $wpdb->get_var( $sql );
	}

	/**
	 * Gets all URL.
	 * 
	 * @param  string $start_time Start time.
	 * @return array
	 */
	public function get_all_url( $start_time ) {
		global $wpdb;

		$sql         = $wpdb->prepare(
			"SELECT ID, type, url, pages FROM {$this->url_table} WHERE `last_upload` < %s and enable = 1",
			$start_time
		);
		$results     = $wpdb->get_results( $sql );
		$array_model = array();
		foreach ( $results as $result ) {
			$array_model[] = new Static_Press_Model_Url_Fetched( $result );
		}
		return $array_model;
	}

	/**
	 * Gets next URL.
	 * 
	 * @param string $fetch_start_time Start time.
	 * @param int    $fetch_last_id    ID in database table which got last time.
	 */
	public function get_next_url( $fetch_start_time, $fetch_last_id ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT ID, type, url, pages FROM {$this->url_table} WHERE `last_upload` < %s and ID > %d and enable = 1 ORDER BY ID LIMIT 1",
			$fetch_start_time,
			$fetch_last_id
		);
		return $wpdb->get_row( $sql );
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
			FROM {$wpdb->posts}
			WHERE (post_status = 'publish' or post_type = 'attachment')
			and post_type in ({$concatenated_post_types})
			ORDER BY post_type, ID"
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
			WHERE post_status = 'publish'
			and post_type in ({$concatenated_post_types})
			GROUP BY post_author
			ORDER BY post_author"
		);
	}

	/**
	 * Counts URLs.
	 * 
	 * @param string $url URL.
	 */
	public function count_url( $url ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT count(*) FROM {$this->url_table} WHERE `url` = %s LIMIT 1",
			$url
		);
		return intval( $wpdb->get_var( $sql ) );
	}

	/**
	 * Counts URLs.
	 * 
	 * @param  string $start_time Start time.
	 * @return array Result.
	 */
	public function count_url_per_type( $start_time ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT type, count(*) as count FROM {$this->url_table} WHERE `last_upload` < %s and enable = 1 GROUP BY type",
			$start_time
		);
		return $wpdb->get_results( $sql );
	}

	/**
	 * Gets term infomation.
	 * 
	 * @param int   $term_id Term ID.
	 * @param array $post_types Post type.
	 */
	public function get_term_info( $term_id, $post_types ) {
		global $wpdb;

		$concatenated_post_types = "'" . implode( "','", $post_types ) . "'";

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT MAX(P.post_modified) AS last_modified, count(P.ID) AS count
				FROM {$wpdb->posts} AS P
				INNER JOIN {$wpdb->term_relationships} AS tr ON tr.object_id = P.ID
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
				WHERE P.post_status = %s and P.post_type in ({$concatenated_post_types})
				and tt.term_id = %d",
				'publish',
				intval( $term_id )
			)
		);
	}

	/**
	 * Inserts URL.
	 * This function sets only defined field and create_date.
	 * 
	 * @param Static_Press_Model_Url[] $url URL.
	 */
	public function insert_url( $url ) {
		$this->insert( $this->url_table, $url->to_array() );
	}

	/**
	 * Updates.
	 * 
	 * @param string $table_name      Table name.
	 * @param array  $map_field_value Array. Key: Field name, Value: Value for update.
	 */
	private function insert( $table_name, $map_field_value ) {
		global $wpdb;
		$sql        = "INSERT INTO {$table_name}";
		$sql       .= ' (`' . implode( '`,`', array_keys( $map_field_value ) ) . '`,`create_date`)';
		$insert_val = array();
		foreach ( $map_field_value as $val ) {
			$insert_val[] = $wpdb->prepare( '%s', $val );
		}
		$insert_val[] = $wpdb->prepare( '%s', $this->date_time_factory->create_date( 'Y-m-d h:i:s' ) );
		$sql         .= ' VALUES (' . implode( ',', $insert_val ) . ')';
		$wpdb->query( $sql );
	}

	/**
	 * Updates URL.
	 * This function updates only defined field.
	 * 
	 * @param string                   $id  ID.
	 * @param Static_Press_Model_Url[] $url URL.
	 */
	public function update_url( $id, $url ) {
		$this->update( $id, $this->url_table, $url->to_array() );
	}

	/**
	 * Updates.
	 * 
	 * @param string $id              ID.
	 * @param string $table_name      Table name.
	 * @param array  $map_field_value Array. Key: Field name, Value: Value for update.
	 */
	private function update( $id, $table_name, $map_field_value ) {
		global $wpdb;
		$sql        = "UPDATE {$table_name}";
		$update_sql = array();
		foreach ( $map_field_value as $key => $val ) {
			$update_sql[] = $wpdb->prepare( "{$key} = %s", $val );
		}
		$sql .= ' SET ' . implode( ',', $update_sql );
		$sql .= $wpdb->prepare( ' WHERE ID=%s', $id );
		$wpdb->query( $sql );
	}

	/**
	 * Deletes URL.
	 * 
	 * @param string $url URL.
	 */
	public function delete_url( $url ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"DELETE FROM `{$this->url_table}` WHERE `url` = %s",
			$url
		);
		if ( $sql ) {
			$wpdb->query( $sql );
		}
	}
}

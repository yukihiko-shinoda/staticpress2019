<?php
/**
 * Class Static_Press_Model_Url
 *
 * @package static_press\includes
 */

namespace static_press\includes;

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
	 */
	public function judge_to_dump( $dump_directory ) {
		$this->enable = $this->classify( $dump_directory );
	}

	/**
	 * Classifies URL whether should dump or not.
	 * 
	 * @return int should not dump: 0, should dump: 1
	 */
	public function classify( $dump_directory ) {
		switch ( true ) {
			case preg_match( '#\.php$#i', $this->get_url() ):      // Seems to intend PHP file.
			case preg_match( '#\?[^=]+[=]?#i', $this->get_url() ): // Seems to intend get request with parameter.
			case preg_match( '#/wp-admin/$#i', $this->get_url() ): // Seems to intend WordPress admin home page.
				return 0;
			case null === $this->get_type():
			case self::TYPE_STATIC_FILE != $this->get_type():
				return 1;
			default:
				return $this->classify_static_file( $this->get_url(), $dump_directory );
		}
	}

	/**
	 * Classifies URL of static file.
	 * 
	 * @param string $url URL.
	 * @return int should not dump: 0, should dump: 1
	 */
	private function classify_static_file( $url, $dump_directory ) {
		$plugin_dir  = trailingslashit( str_replace( ABSPATH, '/', WP_PLUGIN_DIR ) );
		$theme_dir   = trailingslashit( str_replace( ABSPATH, '/', WP_CONTENT_DIR ) . '/themes' );
		$file_source = untrailingslashit( ABSPATH ) . $url;
		$file_dest   = untrailingslashit( $dump_directory ) . $url;
		$pattern     = '#^(/(readme|readme-[^\.]+|license)\.(txt|html?)|(' . preg_quote( $plugin_dir ) . '|' . preg_quote( $theme_dir ) . ').*/((readme|changelog|license)\.(txt|html?)|(screenshot|screenshot-[0-9]+)\.(png|jpe?g|gif)))$#i';
		if ( $file_source === $file_dest ) {
			// Seems to intend to prevent from being duplicate dump process for already dumped files.
			return 0;
		}
		if ( preg_match( $pattern, $url ) ) {
			// Seems to intend readme, license, changelog, screenshot in plugin directory or theme directory.
			return 0;
		}
		if ( ! file_exists( $file_source ) ) {
			// Seems to intend to prevent from processing non exist files.
			return 0;
		}
		if ( file_exists( $file_dest ) && filemtime( $file_source ) <= filemtime( $file_dest ) ) {
			// Seems to intend to skip non update files after last dump.
			return 0;
		}
		if ( preg_match( '#^' . preg_quote( $plugin_dir ) . '#i', $url ) ) {
			return $this->classify_static_file_plugin( $url, $plugin_dir );
		}
		if ( preg_match( '#^' . preg_quote( $theme_dir ) . '#i', $url ) ) {
			return $this->classify_static_file_theme( $url, $theme_dir );
		}
		return 1;
	}

	/**
	 * Classifies URL of plugin's static file.
	 * Original specification seems to intend to dump only active plugin.
	 * 
	 * @param string $url        URL.
	 * @param string $plugin_dir Plugin directory.
	 * @return int should not dump: 0, should dump: 1
	 */
	private function classify_static_file_plugin( $url, $plugin_dir ) {
		$active_plugins = get_option( 'active_plugins' );
		foreach ( $active_plugins as $active_plugin ) {
			$active_plugin = trailingslashit( $plugin_dir . dirname( $active_plugin ) );
			if ( trailingslashit( $plugin_dir . '.' ) == $active_plugin ) {
				// TODO What is the intension? Commited at 2013-04-23 11:50:42 5a470855fe94ef754b156cc062ab86eab452446d .
				continue;
			}
			if ( preg_match( '#^' . preg_quote( $active_plugin ) . '#i', $url ) ) {
				return 1;
			}
		}
		return 0;
	}

	/**
	 * Classifies URL of theme's static file.
	 * Original specification seems to intend to dump only current theme.
	 * 
	 * @param string $url       URL.
	 * @param string $theme_dir Theme directory.
	 * @return int should not dump: 0, should dump: 1
	 */
	private function classify_static_file_theme( $url, $theme_dir ) {
		$current_theme = trailingslashit( $theme_dir . get_stylesheet() );
		if ( preg_match( '#^' . preg_quote( $current_theme ) . '#i', $url ) ) {
			return 1;
		}
		return 0;
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

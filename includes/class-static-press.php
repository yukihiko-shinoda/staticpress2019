<?php
/**
 * Class Static_Press
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_File_Scanner' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-file-scanner.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Plugin_Information' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-plugin-information.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Repository' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-repository.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Terminator' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-terminator.php';
}
use static_press\includes\Static_Press_File_Scanner;
use static_press\includes\Static_Press_Plugin_Information;
use static_press\includes\Static_Press_Repository;
use static_press\includes\Static_Press_Terminator;

/**
 * StaticPress.
 */
class Static_Press {
	const FETCH_LIMIT        =   5;
	const FETCH_LIMIT_STATIC = 100;
	const EXPIRES            = 3600; // 60min * 60sec = 1hour

	static $instance;

	private $plugin_information;
	/**
	 * Database access instance.
	 * 
	 * @var Static_Press_Repository
	 */
	private $repository;
	/**
	 * Terminator instance.
	 * 
	 * @var Static_Press_Terminator
	 */
	private $terminator;

	private $static_url;
	private $url_table;
	private $dump_directory;
	private $remote_get_option;

	private $transient_key = 'static static';

	private $static_files_ext = array(
		'html','htm','txt','css','js','gif','png','jpg','jpeg',
		'mp3','ico','ttf','woff','woff2','otf','eot','svg','svgz','xml',
		'gz','zip', 'pdf', 'swf', 'xsl', 'mov', 'mp4', 'wmv', 'flv',
		'webm', 'ogg', 'oga', 'ogv', 'ogx', 'spx', 'opus',
		);

	/**
	 * Constructor.
	 * 
	 * @param string                  $plugin_basename   Plugin base name.
	 * @param string                  $static_url        Static URL.
	 * @param string                  $dump_directory    Directory to dump static files.
	 * @param array                   $remote_get_option Remote get options.
	 * @param Static_Press_terminator $terminator        Terminator.
	 */
	public function __construct( $plugin_basename, $static_url = '/', $dump_directory = '', $remote_get_option = array(), $terminator = null ) {
		self::$instance        = $this;
		$this->plugin_basename = $plugin_basename;
		$this->url_table       = self::url_table();
		$this->static_url      = $this->init_static_url( $static_url );
		$this->dump_directory  = $this->init_dump_directory( $dump_directory );
		$this->make_subdirectories( $this->dump_directory );
		$this->remote_get_option  = $remote_get_option;
		$this->plugin_information = new Static_Press_Plugin_Information();
		$this->repository         = new Static_Press_Repository();
		$this->terminator         = $terminator ? $terminator : new Static_Press_Terminator();

		$this->repository->create_table();

		add_action( 'wp_ajax_static_press_init', array( $this, 'ajax_init' ) );
		add_action( 'wp_ajax_static_press_fetch', array( $this, 'ajax_fetch' ) );
		add_action( 'wp_ajax_static_press_finalyze', array( $this, 'ajax_finalyze' ) );
	}

	/**
	 * Returns database table name for URL list.
	 */
	public static function url_table() {
		global $wpdb;
		return $wpdb->prefix . 'urls';
	}

	/**
	 * Initializes static URL.
	 * Static URL surely become absolute URL start with (http or https)://.
	 * 
	 * @param  string $static_url The URL of web site to deploy dumped static files.
	 * @return string             The URL of web site to deploy dumped static files.
	 */
	private function init_static_url( $static_url ) {
		if ( preg_match( '#^https?://#i', $static_url ) ) {
			return $static_url;
		}
		$parsed = parse_url( $this->get_site_url() );
		$scheme =
			isset( $parsed['scheme'] )
			? $parsed['scheme']
			: 'http';
		$host   =
			isset( $parsed['host'] )
			? $parsed['host']
			: ( defined( 'DOMAIN_CURRENT_SITE' ) ? DOMAIN_CURRENT_SITE : $_SERVER['HTTP_HOST'] );
		return "{$scheme}://{$host}/";
	}

	/**
	 * Initializes dump directory.
	 * 
	 * @param  string $dump_directory Path to directory to dump static files.
	 * @return string                 Path to directory to dump static files.
	 */
	private function init_dump_directory( $dump_directory ) {
		$dump_directory = ! empty( $dump_directory ) ? $dump_directory : ABSPATH;
		return untrailingslashit( $dump_directory ) . preg_replace( '#^https?://[^/]+/#i', '/', trailingslashit( $this->static_url ) );
	}

	/**
	 * Activate StaticPress.
	 * This function is called by WordPress when activate this plugin.
	 */
	public function activate() {
		$this->repository->ensure_table_exists();
	}

	/**
	 * Deactivate StaticPress.
	 * This function is called by WordPress when deactivate this plugin.
	 */
	public function deactivate() {
		$this->repository->ensure_table_not_exists();
	}

	/**
	 * Dumps JSON responce.
	 * 
	 * @param array $content Content.
	 */
	private function json_output( $content ) {
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		echo json_encode( $content );
		$this->terminator->terminate();
	}

	public function ajax_init(){
		global $wpdb;

		if (!defined('WP_DEBUG_DISPLAY'))
			define('WP_DEBUG_DISPLAY', false);

		if (!is_user_logged_in())
			wp_die('Forbidden');

		$urls = $this->insert_all_url();
		$sql = $wpdb->prepare(
			"select type, count(*) as count from {$this->url_table} where `last_upload` < %s and enable = 1 group by type",
			$this->fetch_start_time()
			);
		$all_urls = $wpdb->get_results($sql);
		$result =
			!is_wp_error($all_urls)
			? array('result' => true, 'urls_count' => $all_urls)
			: array('result' => false);

		$this->json_output(apply_filters('StaticPress::ajax_init', $result));
	}

	public function ajax_fetch(){
		if (!is_user_logged_in())
			wp_die('Forbidden');

		if (!defined('WP_DEBUG_DISPLAY'))
			define('WP_DEBUG_DISPLAY', false);

		$url = $this->fetch_url();
		if (!$url) {
			$result = array('result' => false, 'final' => true);
			$this->json_output(apply_filters('StaticPress::ajax_fetch', $result, $url));
		}

		$result = array();
		$static_file = $this->create_static_file($url->url, $url->type, true, true);
		$file_count = 1;
		$result[$url->ID] = array(
			'ID' => $url->ID,
			'page' => 1,
			'type' => $url->type,
			'url' => $url->url,
			'static' => $static_file,
			);
		if ($url->pages > 1) {
			for ($page = 2; $page <= $url->pages; $page++) {
				$page_url = untrailingslashit(trim($url->url));
				$static_file = false;
				switch($url->type){
				case 'term_archive':
				case 'author_archive':
				case 'other_page':
					$page_url = sprintf('%s/page/%d', $page_url, $page);
					$static_file = $this->create_static_file($page_url, 'other_page', false, true);
					break;
				case 'single':
					$page_url = sprintf('%s/%d', $page_url, $page);
					$static_file = $this->create_static_file($page_url, 'other_page', false, true);
					break;
				}
				if (!$static_file)
					break;
				$file_count++;
				$result["{$url->ID}-{$page}"] = array(
					'ID' => $url->ID,
					'page' => $page,
					'type' => $url->type,
					'url' => $page_url,
					'static' => $static_file,
					);
			}
		}

		while ($url = $this->fetch_url()) {
			$limit = ($url->type == 'static_file' ? self::FETCH_LIMIT_STATIC : self::FETCH_LIMIT);
			$static_file = $this->create_static_file($url->url, $url->type, true, true);
			$file_count++;
			$result[$url->ID] = array(
				'ID' => $url->ID,
				'page' => 1,
				'type' => $url->type,
				'url' => $url->url,
				'static' => $static_file,
				);
			if ($file_count >= $limit)
				break;
		}

		$result = array('result' => true, 'files' => $result, 'final' => ($url === false));
		$this->json_output(apply_filters('StaticPress::ajax_fetch', $result, $url));
	}

	public function ajax_finalyze(){
		if (!defined('WP_DEBUG_DISPLAY'))
			define('WP_DEBUG_DISPLAY', false);

		if (!is_user_logged_in())
			wp_die('Forbidden');

		$static_file = $this->create_static_file($this->get_site_url().'404.html');
		$this->fetch_finalyze();

		$result = array('result' => true);
		$this->json_output(apply_filters('StaticPress::ajax_finalyze', $result));
	}

	/**
	 * Replaces URL.
	 * 
	 * @param  string $url URL.
	 * @return string      Replaced URL.
	 */
	public function replace_url( $url ) {
		$site_url            = trailingslashit( $this->get_site_url() );
		$url                 = trim( str_replace( $site_url, '/', $url ) );
		$static_files_filter = apply_filters( 'StaticPress::static_files_filter', $this->static_files_ext );
		if ( ! preg_match( '#[^/]+\.' . implode( '|', array_merge( $static_files_filter, array( 'php' ) ) ) . '$#i', $url ) ) {
			$url = trailingslashit( $url );
		}
		unset( $static_files_filter );
		return $url;
	}

	public function static_url($permalink) {
		return urldecode(
			preg_match('/\.[^\.]+?$/i', $permalink) 
			? $permalink
			: trailingslashit(trim($permalink)) . 'index.html');
	}

	/**
	 * Gets site URL.
	 * 
	 * @return string Site URL.
	 */
	private function get_site_url() {
		global $current_blog;
		return trailingslashit(
			isset( $current_blog )
			? get_home_url( $current_blog->blog_id )
			: get_home_url()
		);
	}

	/**
	 * Gets transient key.
	 * 
	 * @return string
	 */
	private function get_transient_key() {
		$current_user = function_exists( 'wp_get_current_user' ) ? wp_get_current_user() : '';
		if ( isset( $current_user->ID ) && $current_user->ID ) {
			return "{$this->transient_key} - {$current_user->ID}";
		}
		else {
			return $this->transient_key;
		}
	}

	/**
	 * Fetches start time.
	 * 
	 * @return string
	 */
	private function fetch_start_time() {
		$transient_key = $this->get_transient_key();
		$param = get_transient( $transient_key );
		if ( ! is_array( $param ) ) {
			$param = array();
		}
		if ( isset( $param['fetch_start_time'] ) ) {
			return $param['fetch_start_time'];
		} else {
			$start_time = date( 'Y-m-d h:i:s', time() );
			$param['fetch_start_time'] = $start_time;
			set_transient( $transient_key, $param, self::EXPIRES );
			return $start_time;
		}
	}

	private function fetch_last_id($next_id = false) {
		$transient_key = $this->get_transient_key();
		$param = (array)get_transient($transient_key);
		if (!is_array($param))
			$param = array();
		$last_id = isset($param['fetch_last_id']) ? intval($param['fetch_last_id']) : 0;
		if ($next_id) {
			$last_id = $next_id;
			$param['fetch_last_id'] = $next_id;
			set_transient($transient_key, $param, self::EXPIRES);
		}
		return $last_id;
	}

	private function fetch_finalyze() {
		$transient_key = $this->get_transient_key();
		if (get_transient($transient_key))
			delete_transient($transient_key);
	}

	private function fetch_url() {
		global $wpdb;

		$sql = $wpdb->prepare(
			"select ID, type, url, pages from {$this->url_table} where `last_upload` < %s and ID > %d and enable = 1 order by ID limit 1",
			$this->fetch_start_time(),
			$this->fetch_last_id()
			);
		$result = $wpdb->get_row($sql);
		if (!is_null($result) && !is_wp_error($result) && $result->ID) {
			$next_id = $this->fetch_last_id($result->ID);
			return $result;
		} else {
			$this->fetch_finalyze();
			return false;
		}
	}

	private function dir_sep(){
		return defined('DIRECTORY_SEPARATOR') ? DIRECTORY_SEPARATOR : '/';
	}

	/**
	 * Makes subdirectries.
	 * 
	 * @param string $file File.
	 */
	private function make_subdirectories( $file ) {
		$dir_sep = $subdir = $this->dir_sep();
		$directories = explode( $dir_sep, dirname( $file ) );
		foreach ( $directories as $dir ) {
			if ( empty( $dir ) )
				continue;
			$subdir .= trailingslashit( $dir );
			if ( ! file_exists( $subdir ) ) {
				mkdir( $subdir, 0755 );
			}
		}
	}

	/**
	 * Creates static file.
	 * 
	 * @param  string $url        URL.
	 * @param  string $file_type  File type.
	 * @param  bool   $create_404 Whether create even if HTTP status code is 404 or not.
	 * @param  bool   $crawling   Whether crawl HTML body to check other URL or not.
	 * @return string             Destination of file.
	 */
	private function create_static_file( $url, $file_type = 'other_page', $create_404 = true, $crawling = false ) {
		$url       = apply_filters( 'StaticPress::get_url', $url );
		$file_dest = untrailingslashit( $this->dump_directory ) . $this->static_url( $url );
		$dir_sep   = defined( 'DIRECTORY_SEPARATOR' ) ? DIRECTORY_SEPARATOR : '/';
		if ( $dir_sep !== '/' ) {
			$file_dest = str_replace( '/', $dir_sep, $file_dest );
		}

		$http_code    = 200;
		$blog_charset = get_option( 'blog_charset' );
		switch ( $file_type ) {
			case 'front_page':
			case 'single':
			case 'term_archive':
			case 'author_archive':
			case 'seo_files':
			case 'other_page':
				// get remote file.
				if ( ( $content = $this->remote_get( $url ) ) && isset( $content['body'] ) ) {
					if ( $blog_charset === 'UTF-8' ) {
						$content['body'] = $this->clean_utf8( $content['body'] );
					}
					$http_code = intval( $content['code'] );
					switch ( $http_code ) {
						case 200:
							if ( $crawling ) {
								$this->other_url( $content['body'], $url, $http_code );
							}
						case 404:
							if ( $create_404 || $http_code == 200 ) {
								$content = apply_filters( 'StaticPress::put_content', $content['body'], $http_code );
								$this->make_subdirectories( $file_dest );
								file_put_contents( $file_dest, $content );
								$file_date = date( 'Y-m-d h:i:s', filemtime( $file_dest ) );
							}
					}
				}
				break;
			case 'static_file':
				// get static file.
				$file_source = untrailingslashit( ABSPATH ) . $url;
				if ( $dir_sep !== '/' ) {
					$file_source = str_replace( '/', $dir_sep, $file_source );
				}
				if ( ! is_file( $file_source ) || ! file_exists( $file_source ) ) {
					$this->delete_url( array( $url ) );
					return false;
				}
				if ( $file_source != $file_dest && ( ! file_exists( $file_dest ) || filemtime( $file_source ) > filemtime( $file_dest ) ) ) {
					$file_date = date( 'Y-m-d h:i:s', filemtime( $file_source ) );
					$this->make_subdirectories( $file_dest );
					copy( $file_source, $file_dest );
				}
				break;
		}
		do_action( 'StaticPress::file_put', $file_dest, untrailingslashit( $this->static_url ) . $this->static_url( $url ) );

		if ( file_exists( $file_dest ) ) {
			$this->update_url(
				array(
					array(
						'type' => $file_type,
						'url' => $url,
						'file_name' => $file_dest,
						'file_date' => $file_date,
						'last_statuscode' => $http_code,
						'last_upload' => date( 'Y-m-d h:i:s', time() ),
					),
				)
			);
		} else {
			$file_dest = false;
			$this->update_url(
				array(
					array(
						'type' => $file_type,
						'url' => $url,
						'file_name' => '',
						'last_statuscode' => 404,
						'last_upload' => date( 'Y-m-d h:i:s', time() ),
					),
				)
			);
		}

		return $file_dest;
	}

	private function remote_get($url){
		if (!preg_match('#^https://#i', $url))
			$url = untrailingslashit($this->get_site_url()) . (preg_match('#^/#i', $url) ? $url : "/{$url}");
		$response = wp_remote_get($url, $this->remote_get_option);
		if (is_wp_error($response))
			return false;
		return array(
			'code' => $response['response']['code'],
			'body' => $this->remove_link_tag($response['body'], intval($response['response']['code'])),
			);
	}

	public function remove_link_tag($content, $http_code = 200) {
		$content = preg_replace(
			'#^[ \t]*<link [^>]*rel=[\'"](pingback|EditURI|shortlink|wlwmanifest)[\'"][^>]+/?>\n#ism',
			'',
			$content);
		$content = preg_replace(
			'#^[ \t]*<link [^>]*rel=[\'"]alternate[\'"] [^>]*type=[\'"]application/rss\+xml[\'"][^>]+/?>\n#ism',
			'',
			$content);
		return $content;
	}

	public function	add_last_modified($content, $http_code = 200) {
		if (intval($http_code) === 200) {
			$type = preg_match('#<!DOCTYPE html>#i', $content) ? 'html' : 'xhtml';
			switch ( $type ) {
			case 'html':
				$last_modified = sprintf('<meta http-equiv="Last-Modified" content="%s GMT">', gmdate("D, d M Y H:i:s"));
				break;
			case 'xhtml':
			default:
				$last_modified = sprintf('<meta http-equiv="Last-Modified" content="%s GMT" />', gmdate("D, d M Y H:i:s"));
				break;
			}
			$content = preg_replace('#(<head>|<head [^>]+>)#ism', '$1'."\n".$last_modified, $content);
		}
		return $content;
	}

	/**
	 * Rewrites generator tag.
	 * 
	 * @param  string $content   Content.
	 * @param  int    $http_code HTTP status code.
	 * @return string
	 */
	public function	rewrite_generator_tag( $content, $http_code = 200 ) {
		$content = preg_replace(
			'#(<meta [^>]*name=[\'"]generator[\'"] [^>]*content=[\'"])([^\'"]*)([\'"][^>]*/?>)#ism',
			'$1$2 with ' . ( ( string ) $this->plugin_information ) . '$3',
			$content
		);
		return $content;
	}

	public function replace_relative_URI($content, $http_code = 200) {
		$site_url = trailingslashit($this->get_site_url());
		$parsed = parse_url($site_url);
		$home_url = $parsed['scheme'] . '://' . $parsed['host'];
		if (isset($parsed['port']))
			$home_url .= ':'.$parsed['port'];

		$pattern  = array(
			'# (href|src|action)="(/[^"]*)"#ism',
			"# (href|src|action)='(/[^']*)'#ism",
		);
		$content = preg_replace($pattern, ' $1="'.$home_url.'$2"', $content);

		$content = str_replace($site_url, trailingslashit($this->static_url), $content);

		$parsed = parse_url($this->static_url);
		$static_url = $parsed['scheme'] . '://' . $parsed['host'];
		if (isset($parsed['port']))
			$static_url .= ':'.$parsed['port'];
		$pattern  = array(
			'# (href|src|action)="'.preg_quote($static_url).'([^"]*)"#ism',
			"# (href|src|action)='".preg_quote($static_url)."([^']*)'#ism",
		);
		$content  = preg_replace($pattern, ' $1="$2"', $content);

		if ( $home_url !== $static_url ) {
			$pattern  = array(
				'# (href|src|action)="'.preg_quote($home_url).'([^"]*)"#ism',
				"# (href|src|action)='".preg_quote($home_url)."([^']*)'#ism",
			);
			$content  = preg_replace($pattern, ' $1="$2"', $content);
		}

		$pattern = array(
			'meta [^>]*property=[\'"]og:[^\'"]*[\'"] [^>]*content=',
			'link [^>]*rel=[\'"]canonical[\'"] [^>]*href=',
			'link [^>]*rel=[\'"]shortlink[\'"] [^>]*href=',
			'data-href=',
			'data-url=',
			);
		$pattern  = '#<('.implode('|', $pattern).')[\'"](/[^\'"]*)[\'"]([^>]*)>#uism';
		$content = preg_replace($pattern, '<$1"'.$static_url.'$2"$3>', $content);

		$content = str_replace(addcslashes($site_url, '/'), addcslashes(trailingslashit($this->static_url), '/'), $content);

		return $content;
	}

	private function insert_all_url(){
		$urls = $this->get_urls();
		return $this->update_url($urls);
	}

	/**
	 * Updates URL.
	 * 
	 * @param  array $urls URLs.
	 * @return array
	 */
	private function update_url( $urls ) {
		foreach ( (array) $urls as $url ) {
			if ( ! isset( $url['url'] ) || ! $url['url'] ) {
				continue;
			}
			$url['enable'] = $this->classify( $url );

			$id = $this->repository->get_id( $url['url'] );
			if ( $id ) {
				$this->repository->update_url( $id, $url );
			} else {
				$this->repository->insert_url( $url );
			}

			do_action( 'StaticPress::update_url', $url );
		}
		return $urls;
	}

	/**
	 * Classifies URL whether should dump or not.
	 * 
	 * @param array $url URL.
	 * @return int should not dump: 0, should dump: 1
	 */
	private function classify( $url ) {
		if ( preg_match( '#\.php$#i', $url['url'] ) ) {
			// Seems to intend PHP file.
			return 0;
		}
		if ( preg_match( '#\?[^=]+[=]?#i', $url['url'] ) ) {
			// Seems to intend get request with parameter.
			return 0;
		}
		if ( preg_match( '#/wp-admin/$#i', $url['url'] ) ) {
			// Seems to intend WordPress admin home page.
			return 0;
		}
		if ( ! isset( $url['type'] ) || 'static_file' != $url['type'] ) {
			return 1;
		}
		return $this->classify_static_file( $url['url'] );
	}

	/**
	 * Classifies URL of static file.
	 * 
	 * @param string $url URL.
	 * @return int should not dump: 0, should dump: 1
	 */
	private function classify_static_file( $url ) {
		$plugin_dir  = trailingslashit( str_replace( ABSPATH, '/', WP_PLUGIN_DIR ) );
		$theme_dir   = trailingslashit( str_replace( ABSPATH, '/', WP_CONTENT_DIR ) . '/themes' );
		$file_source = untrailingslashit( ABSPATH ) . $url;
		$file_dest   = untrailingslashit( $this->dump_directory ) . $url;
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

	private function delete_url($urls){
		global $wpdb;

		foreach ((array)$urls as $url){
			if (!isset($url['url']) || !$url['url'])
				continue;
			$sql = $wpdb->prepare(
				"delete from `{$this->url_table}` where `url` = %s",
				$url['url']);
			if ($sql)
				$wpdb->query($sql);
			do_action('StaticPress::delete_url', $url);
		}
		return $urls;
	}

	private function get_urls() {
		global $wpdb;
		$wpdb->query( "truncate table `{$this->url_table}`" );

		return array_merge(
			$this->front_page_url(),
			$this->single_url(),
			$this->terms_url(),
			$this->author_url(),
			$this->static_files_url(),
			$this->seo_url(),
		);
	}

	/**
	 * Checks correct sitemap URL by robots.txt.
	 */
	private function seo_url() {
		$url_type = 'seo_files';
		$urls     = array();
		$analyzed = array();
		$sitemap  = '/sitemap.xml';
		$robots   = '/robots.txt';
		$urls[]   = array('type' => $url_type, 'url' => $robots, 'last_modified' => date('Y-m-d h:i:s'));
		if ( ( $txt = $this->remote_get( $robots ) ) && isset( $txt['body'] ) ) {
			$http_code = intval( $txt['code'] );
			switch ( intval( $http_code ) ) {
			case 200:
				if ( preg_match( '/sitemap:\s.*?(\/[\-_a-z0-9%]+\.xml)/i', $txt['body'], $match ) ) {
					$sitemap = $match[1];
				}
			}
		}
		$this->sitemap_analyzer( $analyzed, $urls, $sitemap, $url_type );
		return $urls;
	}

	/**
	 * Crawls sitemap XML files.
	 */
	private function sitemap_analyzer( &$analyzed, &$urls, $url, $url_type ) {
		$urls[] = array( 'type' => $url_type, 'url' => $url, 'last_modified' => date( 'Y-m-d h:i:s' ) );
		$analyzed[] = $url;
		if( ( $xml = $this->remote_get( $url ) ) && isset( $xml['body'] ) ) {
			$http_code = intval( $xml['code'] );
			switch ( intval( $http_code ) ) {
			case 200:
				if ( preg_match_all( '/<loc>(.*?)<\/loc>/i', $xml['body'], $matches ) ) {
					foreach ( $matches[1] as $link ) {
						if ( preg_match( '/\/([\-_a-z0-9%]+\.xml)$/i', $link,$matchSub ) ) {
							if ( ! in_array( $matchSub[0], $analyzed ) ) {
								$this->sitemap_analyzer( $analyzed, $urls, $matchSub[0], $url_type );
							}
						}
					}
				}
			}
		}
	}


	private function front_page_url() {
		$urls     = array();
		$site_url = $this->get_site_url();
		$urls[]   = array(
			'type' => 'front_page',
			'url' => apply_filters('StaticPress::get_url', $site_url),
			'last_modified' => date('Y-m-d h:i:s'),
			);
		return $urls;
	}

	/**
	 * Gets URLs of posts.
	 */
	private function single_url() {
		$post_types = get_post_types( array( 'public' => true ) );
		$posts = $this->repository->get_posts( $post_types );
		$urls = array();
		foreach ( $posts as $post ) {
			$post_id = $post->ID;
			$modified = $post->post_modified;
			$permalink = get_permalink( $post->ID );
			if ( $permalink === false || is_wp_error( $permalink ) ) {
				// TODO Is is_wp_error() correct? Commited at 2013-04-22 22:54:05 450c6ce5731b27fc98707d8a881844778ced4763 .
				continue;
			}
			$count = 1;
			if ( $splite = preg_split( "#<!--nextpage-->#", $post->post_content ) ) {
				$count = count( $splite );
			}
			$urls[] = array(
				'type'          => 'single',
				'url'           => apply_filters( 'StaticPress::get_url', $permalink ),
				'object_id'     => intval( $post_id ),
				'object_type'   =>  $post->post_type,
				'pages'         => $count,
				'last_modified' => $modified,
			);
		}
		return $urls;
	}

	private function get_term_info($term_id) {
		global $wpdb;

		if (!isset($this->post_types) || empty($this->post_types))
			$this->post_types = "'".implode("','",get_post_types(array('public' => true)))."'";

		$result = $wpdb->get_row($wpdb->prepare("
select MAX(P.post_modified) as last_modified, count(P.ID) as count
 from {$wpdb->posts} as P
 inner join {$wpdb->term_relationships} as tr on tr.object_id = P.ID
 inner join {$wpdb->term_taxonomy} as tt on tt.term_taxonomy_id = tr.term_taxonomy_id
 where P.post_status = %s and P.post_type in ({$this->post_types})
  and tt.term_id = %d
",
			'publish',
			intval($term_id)
			));
		if (!is_wp_error($result)) {
			$modified = $result->last_modified;
			$count = $result->count;
		} else {
			$modified = date('Y-m-d h:i:s');
			$count = 1;
		}
		$page_count = intval($count / intval(get_option('posts_per_page'))) + 1;
		return array($modified, $page_count);
	}

	/**
	 * Gets URLs of terms.
	 */
	private function terms_url( $url_type = 'term_archive' ) {
		$urls = array();
		$taxonomies = get_taxonomies( array( 'public' => true ) );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms( $taxonomy );
			if ( is_wp_error( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term ) {
				$term_id = $term->term_id;
				$termlink = get_term_link( $term->slug, $taxonomy );
				if ( is_wp_error( $termlink ) ) {
					continue;
				}
				list( $modified, $page_count ) = $this->get_term_info( $term_id );
				$urls[] = array(
					'type'          => $url_type,
					'url'           => apply_filters( 'StaticPress::get_url', $termlink ),
					'object_id'     => intval( $term_id ),
					'object_type'   => $term->taxonomy,
					'parent'        => $term->parent,
					'pages'         => $page_count,
					'last_modified' => $modified,
				);

				$termchildren = get_term_children( $term->term_id, $taxonomy );
				if ( is_wp_error( $termchildren ) ) {
					continue;
				}
				foreach ( $termchildren as $child ) {
					$term = get_term_by( 'id', $child, $taxonomy );
					$term_id = $term->term_id;
					if ( is_wp_error( $term ) ) {
						continue;
					}
					$termlink = get_term_link( $term->name, $taxonomy );
					if ( is_wp_error( $termlink ) ) {
						continue;
					}
					list( $modified, $page_count ) = $this->get_term_info( $term_id );
					$urls[] = array(
						'type'          => $url_type,
						'url'           => apply_filters( 'StaticPress::get_url', $termlink ),
						'object_id'     => intval( $term_id ),
						'object_type'   => $term->taxonomy,
						'parent'        => $term->parent,
						'pages'         => $page_count,
						'last_modified' => $modified,
					);
				}
			}
		}
		return $urls;
	}

	/**
	 * Gets URLs of authors.
	 */
	private function author_url() {
		$post_types = get_post_types( array( 'public' => true) );
		$authors = $this->repository->get_post_authors( $post_types );
		$urls = array();
		foreach ( $authors as $author ) {
			$author_id = $author->post_author;
			$page_count = intval( $author->count / intval( get_option( 'posts_per_page' ) ) ) + 1;
			$modified = $author->modified;
			$author = get_userdata( $author_id );
			if ( is_wp_error( $author ) ) {
				continue;
			}
			$authorlink = get_author_posts_url( $author->ID, $author->user_nicename );
			if ( is_wp_error( $authorlink ) ) {
				continue;
			}
			$urls[] = array(
				'type'          => 'author_archive',
				'url'           => apply_filters( 'StaticPress::get_url', $authorlink ),
				'object_id'     => intval( $author_id ),
				'pages'         => $page_count,
				'last_modified' => $modified,
			);
		}
		return $urls;
	}

	/**
	 * Gets URLs of static files.
	 */
	private function static_files_url() {
		$file_scanner = new Static_Press_File_Scanner( apply_filters( 'StaticPress::static_files_filter', $this->static_files_ext ) );
		$static_files = array_merge(
			$file_scanner->scan( trailingslashit( ABSPATH ), false ),
			$file_scanner->scan( trailingslashit( ABSPATH ) . 'wp-admin/', true ),
			$file_scanner->scan( trailingslashit( ABSPATH ) . 'wp-includes/', true ),
			$file_scanner->scan( trailingslashit( WP_CONTENT_DIR ), true ),
		);

		$urls = array();
		foreach ( $static_files as $static_file ) {
			$static_file_url = str_replace( trailingslashit( ABSPATH ), trailingslashit( $this->get_site_url() ), $static_file );
			$urls[] = array(
				'type'          => 'static_file',
				'url'           => apply_filters( 'StaticPress::get_url', $static_file_url ),
				'last_modified' => date( 'Y-m-d h:i:s', filemtime( $static_file ) ),
			);
		}
		return $urls;
	}

	/**
	 * Check whether URL exists or not.
	 * 
	 * @param  string $link URL.
	 * @return bool
	 */
	private function url_exists( $link ) {
		global $wpdb;

		$link  = apply_filters( 'StaticPress::get_url', $link );
		$count = intval( wp_cache_get( 'StaticPress::' . $link, 'static_press' ) );
		if ( $count > 0 ) {
			return true;
		}

		$sql   = $wpdb->prepare(
			"select count(*) from {$this->url_table} where `url` = %s limit 1",
			$link
		);
		$count = intval( $wpdb->get_var( $sql ) );
		wp_cache_set( 'StaticPress::' . $link, $count, 'static_press' );
		
		return $count > 0;
	}

	/**
	 * Checks other URL.
	 * 
	 * @param string $content Content.
	 * @param string $url     URL.
	 * @return array
	 */
	private function other_url( $content, $url ) {
		$urls = array();

		while ( ( $url = dirname( $url ) ) && $url != '/' ) {
			if ( ! $this->url_exists( $url ) ) {
				$urls[] = array(
					'url'           => apply_filters( 'StaticPress::get_url', $url ),
					'last_modified' => date( 'Y-m-d h:i:s' ),
				);
			}
		}

		$pattern = '#href=[\'"](' . preg_quote( $this->get_site_url() ) . '[^\'"\?\#]+)[^\'"]*[\'"]#i';
		if ( preg_match_all( $pattern, $content, $matches ) ) {
			$matches = array_unique( $matches[1] );
			foreach ( $matches as $link ) {
				if ( ! $this->url_exists( $link ) ) {
					$urls[] = array(
						'url'           => apply_filters( 'StaticPress::get_url', $link ),
						'last_modified' => date( 'Y-m-d h:i:s' ),
					);
				}
			}
		}
		unset( $matches );

		if ( count( $urls ) > 0 ) {
			$this->update_url( $urls );
		}

		return $urls;
	}

	/**
	 * Sometimes the content of a page contains invalid utf8 characters.
	 * This breaks the static publishing process.
	 * In order to prevent this, utf8 content gets cleaned before publishing.
	 * 
	 * @see https://github.com/megumiteam/staticpress/pull/13
	 * @param  string $content Content.
	 * @return string|string[]|null
	 */
	private function clean_utf8( $content ) {
		$regex = <<<'END'
		/
		  (
		    (?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
		    |   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
		    |   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
		    |   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3 
		    ){1,100}                        # ...one or more times
		  )
		| .                                 # anything else
		/x
END;
		return preg_replace( $regex, '$1', $content );
	}
}

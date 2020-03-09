<?php
/**
 * Class Static_Press
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Plugin_Information' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-plugin-information.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Remote_Getter' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-remote-getter.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Transient_Manager' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-transient-manager.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Url_Collector' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-url-collector.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Url_Updater' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-url-updater.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Repository' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-repository.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Terminator' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-terminator.php';
}
use static_press\includes\Static_Press_Plugin_Information;
use static_press\includes\Static_Press_Remote_Getter;
use static_press\includes\Static_Press_Transient_Manager;
use static_press\includes\Static_Press_Url_Collector;
use static_press\includes\Static_Press_Url_Updater;
use static_press\includes\Static_Press_Repository;
use static_press\includes\Static_Press_Terminator;

/**
 * StaticPress.
 */
class Static_Press {
	const FETCH_LIMIT        =   5;
	const FETCH_LIMIT_STATIC = 100;

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
	/**
	 * Terminator instance.
	 * 
	 * @var Static_Press_Url_Collector
	 */
	private $url_collector;
	private $static_url;
	/**
	 * Directory to dump static files.
	 * 
	 * @var string
	 */
	private $dump_directory;

	private $static_files_ext = array(
		'html','htm','txt','css','js','gif','png','jpg','jpeg',
		'mp3','ico','ttf','woff','woff2','otf','eot','svg','svgz','xml',
		'gz','zip', 'pdf', 'swf', 'xsl', 'mov', 'mp4', 'wmv', 'flv',
		'webm', 'ogg', 'oga', 'ogv', 'ogx', 'spx', 'opus',
		);

	/**
	 * Constructor.
	 * 
	 * @param string                     $plugin_basename   Plugin base name.
	 * @param string                     $static_url        Static URL.
	 * @param string                     $dump_directory    Directory to dump static files.
	 * @param array                      $remote_get_option Remote get options.
	 * @param Static_Press_terminator    $terminator        Terminator.
	 * @param Static_Press_Remote_Getter $remote_getter     Remote getter.
	 */
	public function __construct( $plugin_basename, $static_url = '/', $dump_directory = '', $remote_get_option = array(), $terminator = null, $remote_getter = null ) {
		self::$instance        = $this;
		$this->plugin_basename = $plugin_basename;
		$this->static_url      = $this->init_static_url( $static_url );
		$this->dump_directory  = $this->init_dump_directory( $dump_directory );
		$this->make_subdirectories( $this->dump_directory );
		$this->repository         = new Static_Press_Repository();
		$this->plugin_information = new Static_Press_Plugin_Information();
		$this->terminator         = $terminator ? $terminator : new Static_Press_Terminator();
		$this->url_collector      = new Static_Press_Url_Collector( $this->static_files_ext, $remote_getter ? $remote_getter : new Static_Press_Remote_Getter( $remote_get_option ) );

		$this->repository->create_table();

		add_action( 'wp_ajax_static_press_init', array( $this, 'ajax_init' ) );
		add_action( 'wp_ajax_static_press_fetch', array( $this, 'ajax_fetch' ) );
		add_action( 'wp_ajax_static_press_finalyze', array( $this, 'ajax_finalyze' ) );
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

	/**
	 * List all URL into database table and render JSON responce.
	 */
	public function ajax_init() {
		if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) {
			define( 'WP_DEBUG_DISPLAY', false );
		}
		if ( ! is_user_logged_in() ) {
			wp_die( 'Forbidden' );
		}

		$this->insert_all_url();
		$all_urls = $this->repository->count_url_per_type( $this->fetch_start_time() );
		$result   =
			! is_wp_error( $all_urls )
			? array(
				'result'     => true,
				'urls_count' => $all_urls,
			)
			: array( 'result' => false );

		$this->json_output( apply_filters( 'StaticPress::ajax_init', $result ) );
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
		return Static_Press_Url_Collector::get_site_url();
	}

	/**
	 * Fetches start time.
	 * 
	 * @return string
	 */
	private function fetch_start_time() {
		$transient_manager = new Static_Press_Transient_Manager();
		$param             = $transient_manager->get_transient();
		if ( isset( $param['fetch_start_time'] ) ) {
			return $param['fetch_start_time'];
		} else {
			$start_time = date( 'Y-m-d h:i:s', time() );
			$param['fetch_start_time'] = $start_time;
			$transient_manager->set_transient( $param );
			return $start_time;
		}
	}

	/**
	 * Fetches last ID.
	 * 
	 * @param  int|bool $next_id ID to set next.
	 * @return int Cached ID when $next_id is 0 or false, $next_id when $next_id is not 0 nor false.
	 */
	private function fetch_last_id( $next_id = false ) {
		$transient_manager = new Static_Press_Transient_Manager();
		$param             = $transient_manager->get_transient();
		$last_id           = isset( $param['fetch_last_id'] ) ? intval( $param['fetch_last_id'] ) : 0;
		if ( $next_id ) {
			$last_id                = $next_id;
			$param['fetch_last_id'] = $next_id;
			$transient_manager->set_transient( $param );
		}
		return $last_id;
	}

	/**
	 * Deletes transient.
	 */
	private function fetch_finalyze() {
		$transient_manager = new Static_Press_Transient_Manager();
		$transient_manager->delete_transient();
	}

	/**
	 * Fetches URL.
	 * 
	 * @return array|bool List of fetched URL when exist in database table, false when not exist in database table.
	 */
	private function fetch_url() {
		$result = $this->repository->get_next_url(
			$this->fetch_start_time(),
			$this->fetch_last_id()
		);
		if ( ! is_null( $result ) && ! is_wp_error( $result ) && $result->ID ) {
			$this->fetch_last_id( $result->ID );
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

	private function remote_get( $url ) {
		return $this->url_collector->remote_get( $url );
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
		return preg_replace(
			'#(<meta [^>]*name=[\'"]generator[\'"] [^>]*content=[\'"])([^\'"]*)([\'"][^>]*/?>)#ism',
			'$1$2 with ' . ( (string) $this->plugin_information ) . '$3',
			$content
		);
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

	/**
	 * Inserts all URLs.
	 */
	private function insert_all_url() {
		$urls = $this->get_urls();
		$this->update_url( $urls );
	}

	/**
	 * Updates URL.
	 * 
	 * @param  array $urls URLs.
	 */
	private function update_url( $urls ) {
		$url_updater = new Static_Press_Url_Updater( $this->repository, $this->dump_directory );
		$url_updater->update( $urls );
	}

	/**
	 * Deletes URL.
	 * 
	 * @param array $urls URLs.
	 */
	private function delete_url( $urls ) {
		foreach ( (array) $urls as $url ) {
			if ( ! isset( $url['url'] ) || ! $url['url'] ) {
				continue;
			}
			$this->repository->delete_url( $url['url'] );
			do_action( 'StaticPress::delete_url', $url );
		}
		return $urls;
	}

	/**
	 * Gets URLs.
	 * 
	 * @return array URLs.
	 */
	private function get_urls() {
		$this->repository->trancate_table();
		return $this->url_collector->collect();
	}

	/**
	 * Check whether URL exists or not.
	 * 
	 * @param  string $url URL.
	 * @return bool
	 */
	private function url_exists( $url ) {
		$url  = apply_filters( 'StaticPress::get_url', $url );
		$count = intval( wp_cache_get( 'StaticPress::' . $url, 'static_press' ) );
		if ( $count > 0 ) {
			return true;
		}

		$count = $this->repository->count_url($url);
		wp_cache_set( 'StaticPress::' . $url, $count, 'static_press' );
		
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

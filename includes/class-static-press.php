<?php
/**
 * Class Static_Press
 *
 * @package static_press\includes
 */

namespace static_press\includes;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/content_filters/class-static-press-content-filter.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/content_filters/class-static-press-content-filter-replace-relative-uri.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/controllers/class-static-press-ajax-fetch.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/controllers/class-static-press-ajax-finalyze.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/controllers/class-static-press-ajax-init.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/infrastructure/class-static-press-document-root-getter.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/infrastructure/class-static-press-file-system-operator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/repositories/class-static-press-repository.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-date-time-factory.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-remote-getter.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-site-dependency.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-terminator.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-url-filter.php';
use static_press\includes\content_filters\Static_Press_Content_Filter;
use static_press\includes\content_filters\Static_Press_Content_Filter_Replace_Relative_Uri;
use static_press\includes\controllers\Static_Press_Ajax_Fetch;
use static_press\includes\controllers\Static_Press_Ajax_Finalyze;
use static_press\includes\controllers\Static_Press_Ajax_Init;
use static_press\includes\infrastructure\Static_Press_Document_Root_Getter;
use static_press\includes\infrastructure\Static_Press_File_System_Operator;
use static_press\includes\repositories\Static_Press_Repository;
use static_press\includes\Static_Press_Date_Time_Factory;
use static_press\includes\Static_Press_Remote_Getter;
use static_press\includes\Static_Press_Site_Dependency;
use static_press\includes\Static_Press_Terminator;
use static_press\includes\Static_Press_Url_Filter;
/**
 * StaticPress.
 */
class Static_Press {
	/**
	 * Absolute URL of static site.
	 * 
	 * @var string
	 */
	private $static_site_url;
	/**
	 * Directory to dump static files.
	 * 
	 * @var string
	 */
	private $dump_directory;
	/**
	 * Date time factory instance.
	 * 
	 * @var Static_Press_Date_Time_Factory
	 */
	private $date_time_factory;
	/**
	 * Database access instance.
	 * 
	 * @var Static_Press_Repository
	 */
	private $repository;
	/**
	 * Directory to dump static files.
	 * 
	 * @var Static_Press_Content_Filter
	 */
	private $content_filter;
	/**
	 * Remote getter.
	 * 
	 * @var Static_Press_Remote_Getter
	 */
	private $remote_getter;
	/**
	 * Url filter.
	 * 
	 * @var Static_Press_Url_Filter
	 */
	private $url_filter;
	/**
	 * Document root getter.
	 * 
	 * @var Static_Press_Document_Root_Getter
	 */
	private $document_root_getter;

	/**
	 * Constructor.
	 * 
	 * @param string                            $url_static_home      Static site URL.
	 * @param string                            $dump_directory       Directory to dump static files.
	 * @param array                             $remote_get_option    Remote get options.
	 * @param Static_Press_Date_Time_Factory    $date_time_factory    Date time factory.
	 * @param Static_Press_Remote_Getter        $remote_getter        Remote getter.
	 * @param Static_Press_Document_Root_Getter $document_root_getter Document root getter.
	 */
	public function __construct(
		$url_static_home = '/',
		$dump_directory = '',
		$remote_get_option = array(),
		$date_time_factory = null,
		$remote_getter = null,
		$document_root_getter = null
	) {
		$this->static_site_url = $this->init_static_site_url( $url_static_home );
		$this->dump_directory  = $this->init_dump_directory( $dump_directory );
		Static_Press_File_System_Operator::make_subdirectories( $this->dump_directory );
		$this->date_time_factory    = $date_time_factory ? $date_time_factory : new Static_Press_Date_Time_Factory();
		$this->repository           = new Static_Press_Repository( $this->date_time_factory );
		$this->content_filter       = new Static_Press_Content_Filter( $this->date_time_factory );
		$this->remote_getter        = $remote_getter ? $remote_getter : new Static_Press_Remote_Getter( $remote_get_option );
		$this->document_root_getter = $document_root_getter ? $document_root_getter : new Static_Press_Document_Root_Getter();
		$this->url_filter           = new Static_Press_Url_Filter();

		$this->repository->create_table();

		add_action( 'wp_ajax_static_press_init', array( $this, 'ajax_init' ) );
		add_action( 'wp_ajax_static_press_fetch', array( $this, 'ajax_fetch' ) );
		add_action( 'wp_ajax_static_press_finalyze', array( $this, 'ajax_finalyze' ) );
	}

	/**
	 * Initializes static site URL.
	 * When argument doesn't start with http(s), this function fallback to dynamic site URL (the site StaticPress itself running).
	 * Static URL surely become absolute URL start with (http or https)://.
	 * 
	 * @param  string $url_static_home The URL of web site to deploy dumped static files.
	 * @return string             The URL of web site to deploy dumped static files.
	 */
	private function init_static_site_url( $url_static_home ) {
		if ( preg_match( '#^https?://#i', $url_static_home ) ) {
			return $url_static_home;
		}
		$parsed = parse_url( Static_Press_Site_Dependency::get_site_url() );
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
		return untrailingslashit( $dump_directory ) . preg_replace( '#^https?://[^/]+/#i', '/', trailingslashit( $this->static_site_url ) );
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
	 * List all URL into database table and render JSON responce.
	 * 
	 * @param Static_Press_terminator $terminator Terminator.
	 */
	public function ajax_init( $terminator = null ) {
		$ajax_processor = new Static_Press_Ajax_Init(
			$this->static_site_url,
			$this->dump_directory,
			$this->repository,
			$this->remote_getter,
			$terminator,
			$this->date_time_factory,
			$this->document_root_getter
		);
		$ajax_processor->execute();
	}

	/**
	 * Fetches URL from database and crate static files.
	 * 
	 * @param Static_Press_terminator $terminator Terminator.
	 * @throws \LogicException Fetched URL has Inbalid file type.
	 */
	public function ajax_fetch( $terminator = null ) {
		$ajax_processor = new Static_Press_Ajax_Fetch(
			$this->static_site_url,
			$this->dump_directory,
			$this->repository,
			$this->remote_getter,
			$terminator,
			$this->date_time_factory,
			$this->document_root_getter
		);
		$ajax_processor->execute();
	}

	/**
	 * Creates 404 error page html static file, 
	 * 
	 * @param Static_Press_terminator $terminator Terminator.
	 */
	public function ajax_finalyze( $terminator = null ) {
		$ajax_processor = new Static_Press_Ajax_Finalyze(
			$this->static_site_url,
			$this->dump_directory,
			$this->repository,
			$this->remote_getter,
			$terminator,
			$this->date_time_factory,
			$this->document_root_getter
		);
		$ajax_processor->execute();
	}

	/**
	 * Replaces URL.
	 * 
	 * @param  string $url URL.
	 * @return string      Replaced URL.
	 */
	public function replace_url( $url ) {
		return $this->url_filter->replace_url( $url );
	}

	/**
	 * Removes some kinds of link tag.
	 * 
	 * @param string $content   Content.
	 * @param int    $http_code HTTP responce code.
	 * @return string Tag removed content.
	 */
	public function remove_link_tag( $content, $http_code = 200 ) {
		return Static_Press_Content_Filter::remove_link_tag( $content, $http_code );
	}

	/**
	 * Adds meta tag for last modified.
	 * 
	 * @param string $content   Content.
	 * @param int    $http_code HTTP responce code.
	 * @return string Tag removed content.
	 */
	public function add_last_modified( $content, $http_code = 200 ) {
		return $this->content_filter->add_last_modified( $content, $http_code );
	}

	/**
	 * Rewrites generator tag.
	 * 
	 * @param  string $content   Content.
	 * @param  int    $http_code HTTP status code.
	 * @return string
	 */
	public function rewrite_generator_tag( $content, $http_code = 200 ) {
		return $this->content_filter->rewrite_generator_tag( $content, $http_code );
	}

	/**
	 * Replaces relative URI.
	 * 
	 * @param  string $content   Content.
	 * @param  int    $http_code HTTP status code.
	 * @return string Replaced content.
	 */
	public function replace_relative_uri( $content, $http_code = 200 ) {
		$content_filter = new Static_Press_Content_Filter_Replace_Relative_Uri( $this->static_site_url );
		return $content_filter->filter( $content );
	}
}

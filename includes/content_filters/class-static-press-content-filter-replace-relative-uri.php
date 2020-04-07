<?php
/**
 * Class Static_Press_Content_Filter_Replace_Relative_Uri
 *
 * @package static_press\includes\content_filters
 */

namespace static_press\includes\content_filters;

if ( ! class_exists( 'static_press\includes\Static_Press_Site_Dependency' ) ) {
	require STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-site-dependency.php';
}
use static_press\includes\Static_Press_Site_Dependency;
/**
 * Content filter.
 */
class Static_Press_Content_Filter_Replace_Relative_Uri {
	/**
	 * Absolute URL of dynamic site
	 * 
	 * @var string
	 */
	private $url_dynamic;
	/**
	 * Absolute URL of dynamic site home
	 * 
	 * @var string
	 */
	private $url_dynamic_home;
	/**
	 * Absolute URL of static site
	 * 
	 * @var string
	 */
	private $url_static;
	/**
	 * Absolute URL of static site home
	 * 
	 * @var string
	 */
	private $url_static_home;

	/**
	 * COnstructor.
	 * 
	 * @param string $url_static Absolute URL of static site.
	 */
	public function __construct( $url_static ) {
		$this->url_dynamic      = trailingslashit( Static_Press_Site_Dependency::get_site_url() );
		$this->url_dynamic_home = $this->get_home_url( $this->url_dynamic );
		$this->url_static       = $url_static;
		$this->url_static_home  = $this->get_home_url( $this->url_static );
	}

	/**
	 * Filters.
	 * 
	 * @param string $content Content.
	 */
	public function filter( $content ) {
		// Replaces relative URI to absolute URI of dynamic site.
		$pattern = array(
			'# (href|src|action)="(/[^/][^"]*)"#ism',
			"# (href|src|action)='(/[^/][^']*)'#ism",
		);
		$content = preg_replace( $pattern, ' $1="' . $this->url_dynamic_home . '$2"', $content );
		$content = $this->replace_static_site_url( $this->url_dynamic, $content );
		$content = $this->replace_static_site_home_url( $this->url_static_home, $content );
		if ( $this->url_dynamic_home !== $this->url_static_home ) {
			$content = $this->replace_dinamic_site_url( $this->url_dynamic_home, $content );
		}
		$content = $this->replace_extra_url( $this->url_static_home, $content );
		return $this->replace_backslashed( $this->url_dynamic, $content );
	}

	/**
	 * Gets home URL.
	 * 
	 * @param string $url Absolute URL.
	 * @return string Absolute home URL.
	 */
	private function get_home_url( $url ) {
		$parsed   = parse_url( $url );
		$url_home = $parsed['scheme'] . '://' . $parsed['host'];
		if ( isset( $parsed['port'] ) ) {
			$url_home .= ':' . $parsed['port'];
		}
		return $url_home;
	}

	/**
	 * Replaces absolute URL of dynamic site home to absolute URL of static site home.
	 * 
	 * @param string $url_dynamic Absolute URL of dynamic site.
	 * @param string $content     Content.
	 * @return string Content.
	 */
	private function replace_static_site_url( $url_dynamic, $content ) {
		return str_replace( $url_dynamic, trailingslashit( $this->url_static ), $content );
	}

	/**
	 * Replaces absolute URL of static site home to relative URL.
	 * 
	 * @param string $url_static_home Absolute URL of dynamic site.
	 * @param string $content         Content.
	 * @return string Content.
	 */
	private function replace_static_site_home_url( $url_static_home, $content ) {
		$pattern = array(
			'# (href|src|action)="' . preg_quote( $url_static_home ) . '([^"]*)"#ism',
			"# (href|src|action)='" . preg_quote( $url_static_home ) . "([^']*)'#ism",
		);
		return preg_replace( $pattern, ' $1="$2"', $content );
	}

	/**
	 * Replaces absolute URL of dynamic site home to relative URL.
	 * 
	 * @param string $url_dynamic_home Absolute URL of dynamic site.
	 * @param string $content          Content.
	 * @return string Content.
	 */
	private function replace_dinamic_site_url( $url_dynamic_home, $content ) {
		$pattern = array(
			'# (href|src|action)="' . preg_quote( $url_dynamic_home ) . '([^"]*)"#ism',
			"# (href|src|action)='" . preg_quote( $url_dynamic_home ) . "([^']*)'#ism",
		);
		return preg_replace( $pattern, ' $1="$2"', $content );
	}

	/**
	 * Replaces URLs of some extra attrebutes to absolute URL of static site.
	 * This function assumes that URLs URLs of some extra attrebutes are relative.
	 * 
	 * @param string $url_static_home Absolute URL of dynamic site.
	 * @param string $content         Content.
	 * @return string Content.
	 */
	private function replace_extra_url( $url_static_home, $content ) {
		$pattern = array(
			'meta [^>]*property=[\'"]og:[^\'"]*[\'"] [^>]*content=',
			'link [^>]*rel=[\'"]canonical[\'"] [^>]*href=',
			'link [^>]*rel=[\'"]shortlink[\'"] [^>]*href=',
			'data-href=',
			'data-url=',
		);
		$pattern = '#<(' . implode( '|', $pattern ) . ')[\'"](/[^\'"]*)[\'"]([^>]*)>#uism';
		return preg_replace( $pattern, '<$1"' . $url_static_home . '$2"$3>', $content );
	}

	/**
	 * Replaces backslashed absolute URL of dynamic site to backslashed absolute URL of static site.
	 * 
	 * @param string $url_dynamic Absolute URL of dynamic site.
	 * @param string $content Content.
	 * @return string Content.
	 */
	private function replace_backslashed( $url_dynamic, $content ) {
		return str_replace( addcslashes( $url_dynamic, '/' ), addcslashes( trailingslashit( $this->url_static ), '/' ), $content );
	}
}

<?php
/**
 * Class Static_Press_Content_Filter_Replace_Relative_Uri
 *
 * @package static_press\includes\content_filters
 */

namespace static_press\includes\content_filters;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-site-dependency.php';
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
		$this->url_dynamic      = untrailingslashit( Static_Press_Site_Dependency::get_site_url() );
		$this->url_dynamic_home = $this->get_home_url( $this->url_dynamic );
		$this->url_static       = untrailingslashit( $url_static );
		$this->url_static_home  = $this->get_home_url( $this->url_static );
	}

	/**
	 * Filters.
	 * 
	 * @param string $content Content.
	 */
	public function filter( $content ) {
		$content = $this->replace_relative_with_dynamic_site_home( $content );
		$content = $this->replace_dynamic_site_with_static_site( $content );
		$content = $this->replace_static_site_home_with_relative( $content );
		if ( $this->url_dynamic_home !== $this->url_static_home ) {
			$content = $this->replace_dinamic_site_home_with_relative( $content );
		}
		$content = $this->replace_extra_realative_with_static_home( $content );
		$content = $this->replace_backslashed( $content );
		return $this->replace_url_encoded( $content );
	}

	/**
	 * Gets home URL. Not end with '/'.
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
	 * Replaces relative URL to absolute URL of dynamic site home.
	 * 
	 * @param string $content Content.
	 * @return string Content.
	 */
	private function replace_relative_with_dynamic_site_home( $content ) {
		$pattern = array(
			'# (href|src|srcset|action)="(/[^/][^"]*)"#ism',
			"# (href|src|srcset|action)='(/[^/][^']*)'#ism",
		);
		return preg_replace( $pattern, ' $1="' . $this->url_dynamic_home . '$2"', $content );
	}

	/**
	 * Replaces absolute URL of dynamic site to absolute URL of static site.
	 * 
	 * @param string $content Content.
	 * @return string Content.
	 */
	private function replace_dynamic_site_with_static_site( $content ) {
		return str_replace( $this->url_dynamic, $this->url_static, $content );
	}

	/**
	 * Replaces absolute URL of static site home to relative URL.
	 * 
	 * @param string $content Content.
	 * @return string Content.
	 */
	private function replace_static_site_home_with_relative( $content ) {
		$pattern = array(
			'# (href|src|srcset|action)="' . preg_quote( $this->url_static_home ) . '([^"]*)"#ism',
			"# (href|src|srcset|action)='" . preg_quote( $this->url_static_home ) . "([^']*)'#ism",
		);
		return preg_replace( $pattern, ' $1="$2"', $content );
	}

	/**
	 * Replaces absolute URL of dynamic site home to relative URL.
	 * 
	 * @param string $content Content.
	 * @return string Content.
	 */
	private function replace_dinamic_site_home_with_relative( $content ) {
		$pattern = array(
			'# (href|src|srcset|action)="' . preg_quote( $this->url_dynamic_home ) . '([^"]*)"#ism',
			"# (href|src|srcset|action)='" . preg_quote( $this->url_dynamic_home ) . "([^']*)'#ism",
		);
		return preg_replace( $pattern, ' $1="$2"', $content );
	}

	/**
	 * Replaces URLs of some extra attrebutes to absolute URL of static site.
	 * This function assumes that URLs of some extra attrebutes are relative.
	 * 
	 * @param string $content Content.
	 * @return string Content.
	 */
	private function replace_extra_realative_with_static_home( $content ) {
		$pattern = array(
			'meta [^>]*property=[\'"]og:[^\'"]*[\'"] [^>]*content=',
			'link [^>]*rel=[\'"]canonical[\'"] [^>]*href=',
			'link [^>]*rel=[\'"]shortlink[\'"] [^>]*href=',
			'data-href=',
			'data-url=',
		);
		$pattern = '#<(' . implode( '|', $pattern ) . ')[\'"](/[^\'"]*)[\'"]([^>]*)>#uism';
		return preg_replace( $pattern, '<$1"' . $this->url_static_home . '$2"$3>', $content );
	}

	/**
	 * Replaces backslashed absolute URL of dynamic site to backslashed absolute URL of static site.
	 * 
	 * @param string $content Content.
	 * @return string Content.
	 */
	private function replace_backslashed( $content ) {
		return str_replace( addcslashes( $this->url_dynamic, '/' ), addcslashes( $this->url_static, '/' ), $content );
	}

	/**
	 * Replaces URL encoded absolute URL of dynamic site to URL encoded absolute URL of static site.
	 * 
	 * @param string $content Content.
	 * @return string Content.
	 */
	private function replace_url_encoded( $content ) {
		return str_replace( urlencode( $this->url_dynamic ), urlencode( $this->url_static ), $content );
	}
}

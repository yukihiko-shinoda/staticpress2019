<?php
/**
 * Class Static_Press_Content_Filter
 *
 * @package static_press\includes\content_filters
 */

namespace static_press\includes\content_filters;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-plugin-information.php';
use static_press\includes\Static_Press_Plugin_Information;
/**
 * Content filter.
 */
class Static_Press_Content_Filter {
	const DOC_TYPE_HTML  = 'html';
	const DOC_TYPE_XHTML = 'xhtml';
	/**
	 * Plugin information instance.
	 * 
	 * @var Static_Press_Plugin_Information
	 */
	private $plugin_information;
	/**
	 * Date time factory instance.
	 * 
	 * @var Static_Press_Date_Time_Factory
	 */
	private $date_time_factory;

	/**
	 * Constructor.
	 * 
	 * @param Static_Press_Date_Time_Factory $date_time_factory  Date time factory.
	 */
	public function __construct( $date_time_factory ) {
		$this->plugin_information = new Static_Press_Plugin_Information();
		$this->date_time_factory  = $date_time_factory;
	}

	/**
	 * Removes some kinds of link tag.
	 * 
	 * @param string $content   Content.
	 * @param int    $http_code HTTP responce code.
	 * @return string Tag removed content.
	 */
	public static function remove_link_tag( $content, $http_code = 200 ) {
		$content = preg_replace(
			'#^[ \t]*<link [^>]*rel=[\'"](pingback|EditURI|shortlink|wlwmanifest)[\'"][^>]+/?>\n#ism',
			'',
			$content
		);
		$content = preg_replace(
			'#^[ \t]*<link [^>]*rel=[\'"]alternate[\'"] [^>]*type=[\'"]application/rss\+xml[\'"][^>]+/?>\n#ism',
			'',
			$content
		);
		return $content;
	}

	/**
	 * Adds <meta> tag for set last modified into top of HTML <head> tag.
	 * 
	 * @param string $content   Content.
	 * @param int    $http_code HTTP responce code.
	 * @return string Last modified added content.
	 */
	public function add_last_modified( $content, $http_code = 200 ) {
		if ( intval( $http_code ) !== 200 ) {
			return $content;
		}
		$type = preg_match( '#<!DOCTYPE html>#i', $content ) ? self::DOC_TYPE_HTML : self::DOC_TYPE_XHTML;
		switch ( $type ) {
			case self::DOC_TYPE_HTML:
				$last_modified = sprintf( '<meta http-equiv="Last-Modified" content="%s GMT">', $this->date_time_factory->create_gmdate( 'D, d M Y H:i:s' ) );
				break;
			case self::DOC_TYPE_XHTML:
			default:
				$last_modified = sprintf( '<meta http-equiv="Last-Modified" content="%s GMT" />', $this->date_time_factory->create_gmdate( 'D, d M Y H:i:s' ) );
				break;
		}
		return preg_replace( '#(<head>|<head [^>]+>)#ism', '$1' . "\n" . $last_modified, $content );
	}

	/**
	 * Rewrites generator tag.
	 * This function seems to intend to add this plugin name and version into generator tag.
	 * 
	 * @param string $content   Content.
	 * @param int    $http_code HTTP status code.
	 * @return string generator rewroute content.
	 */
	public function rewrite_generator_tag( $content, $http_code = 200 ) {
		return preg_replace(
			'#(<meta [^>]*name=[\'"]generator[\'"] [^>]*content=[\'"])([^\'"]*)([\'"][^>]*/?>)#ism',
			'$1$2 with ' . ( (string) $this->plugin_information ) . '$3',
			$content
		);
	}
}

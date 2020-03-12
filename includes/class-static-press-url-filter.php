<?php
/**
 * Class Static_Press_Url_Filter
 *
 * @package static_press\includes
 */

namespace static_press\includes;

/**
 * Content filter.
 */
class Static_Press_Url_Filter {
	/**
	 * Replaces URL.
	 * 
	 * @param  string $url                         URL.
	 * @param  string $array_extension_static_file List of extension of static file.
	 * @return string Replaced URL.
	 */
	public static function replace_url( $url, $array_extension_static_file ) {
		$url_dynamic         = trailingslashit( Static_Press_Url_Collector::get_site_url() );
		$url                 = trim( str_replace( $url_dynamic, '/', $url ) );
		$static_files_filter = apply_filters( 'StaticPress::static_files_filter', $array_extension_static_file );
		if ( ! preg_match( '#[^/]+\.' . implode( '|', array_merge( $static_files_filter, array( 'php' ) ) ) . '$#i', $url ) ) {
			$url = trailingslashit( $url );
		}
		unset( $static_files_filter );
		return $url;
	}
}

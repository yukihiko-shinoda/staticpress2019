<?php
/**
 * Class Static_Press_Site_Dependency
 *
 * @package static_press\includes
 */

namespace static_press\includes;

/**
 * Site dependency.
 */
class Static_Press_Site_Dependency {
	/**
	 * Gets site home URL.
	 * Case when multisite, get appropriate blog's home URL.
	 * 
	 * @return string Site home URL.
	 */
	public static function get_site_url() {
		global $current_blog;
		return trailingslashit(
			isset( $current_blog )
			? get_home_url( get_current_blog_id() )
			: get_home_url()
		);
	}
}

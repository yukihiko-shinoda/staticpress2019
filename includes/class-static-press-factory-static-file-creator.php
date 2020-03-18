<?php
/**
 * Static_Press_Factory_Static_File_Creator
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Static_File_Creator_Local' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-static-file-creator-local.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Static_File_Creator_Remote' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-static-file-creator-remote.php';
}
use static_press\includes\Static_Press_Static_File_Creator_Local;
use static_press\includes\Static_Press_Static_File_Creator_Remote;

/**
 * Class Static_Press_Factory_Static_File_Creator
 */
class Static_Press_Factory_Static_File_Creator {
	/**
	 * Creates static file creator.
	 * 
	 * @param string                         $file_type         File type.
	 * @param string                         $dump_directory    Dump direcory.
	 * @param string                         $static_site_url   Static site URL.
	 * @param Static_Press_Repository        $repository        Repository.
	 * @param Static_Press_Date_Time_Factory $date_time_factory Date time factory.
	 * @param Static_Press_Url_Collector     $url_collector     URL collector.
	 * @return Static_Press_Static_File_Creator Static file creator.
	 */
	public static function create( $file_type, $dump_directory, $static_site_url, $repository, $date_time_factory, $url_collector ) {
		switch ( $file_type ) {
			case 'front_page':
			case 'single':
			case 'term_archive':
			case 'author_archive':
			case 'seo_files':
			case 'other_page':
				return new Static_Press_Static_File_Creator_Remote(
					$file_type,
					$dump_directory,
					$static_site_url,
					$repository,
					$date_time_factory,
					new Static_Press_Response_Processor_200_Crawl( $dump_directory, $repository, $date_time_factory ),
					new Static_Press_Response_Processor_404_Dump(),
					$url_collector
				);
			case Static_Press_Model_Url::TYPE_STATIC_FILE:
				return new Static_Press_Static_File_Creator_local( $file_type, $dump_directory, $static_site_url, $repository, $date_time_factory );
		}
	}
}

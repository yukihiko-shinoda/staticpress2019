<?php
/**
 * Static_Press_Factory_Static_File_Creator
 *
 * @package static_press\includes\factories
 */

namespace static_press\includes\factories;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/models/class-static-press-model-url.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/response_processors/class-static-press-response-processor-200-crawl.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/response_processors/class-static-press-response-processor-404-dump.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/static_file_creators/class-static-press-static-file-creator-local.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/static_file_creators/class-static-press-static-file-creator-remote.php';
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\response_processors\Static_Press_Response_Processor_200_Crawl;
use static_press\includes\response_processors\Static_Press_Response_Processor_404_Dump;
use static_press\includes\static_file_creators\Static_Press_Static_File_Creator_Local;
use static_press\includes\static_file_creators\Static_Press_Static_File_Creator_Remote;
/**
 * Class Static_Press_Factory_Static_File_Creator
 */
class Static_Press_Factory_Static_File_Creator {
	/**
	 * Creates static file creator.
	 * 
	 * @param string                            $file_type            File type.
	 * @param string                            $dump_directory       Dump direcory.
	 * @param string                            $static_site_url      Static site URL.
	 * @param Static_Press_Repository           $repository           Repository.
	 * @param Static_Press_Date_Time_Factory    $date_time_factory    Date time factory.
	 * @param Static_Press_Url_Collector        $url_collector        URL collector.
	 * @param Static_Press_Document_Root_Getter $document_root_getter Document root getter.
	 * @return Static_Press_Static_File_Creator Static file creator.
	 * @throws \LogicException Inbalid file type.
	 */
	public static function create( $file_type, $dump_directory, $static_site_url, $repository, $date_time_factory, $url_collector, $document_root_getter = null ) {
		switch ( $file_type ) {
			case Static_Press_Model_Url::TYPE_FRONT_PAGE:
			case Static_Press_Model_Url::TYPE_SINGLE:
			case Static_Press_Model_Url::TYPE_TERM_ARCHIVE:
			case Static_Press_Model_Url::TYPE_AUTHOR_ARCHIVE:
			case Static_Press_Model_Url::TYPE_SEO_FILES:
			case Static_Press_Model_Url::TYPE_OTHER_PAGE:
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
			case Static_Press_Model_Url::TYPE_CONTENT_FILE:
				return new Static_Press_Static_File_Creator_local( $file_type, $dump_directory, $static_site_url, $repository, $date_time_factory, $document_root_getter );
			default:
				throw new \LogicException( "Invalid file type. File type = {$file_type}" );
		}
	}
}

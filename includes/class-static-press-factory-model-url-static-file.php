<?php
/**
 * Class Static_Press_Factory_Model_Url_Static_File
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Document_Root_Getter' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-document-root-getter.php';
}
use static_press\includes\Static_Press_Document_Root_Getter;
/**
 * Path converter.
 */
class Static_Press_Factory_Model_Url_Static_File {
	/**
	 * Document root.
	 * 
	 * @var string
	 */
	private $document_root;

	/**
	 * Constructor.
	 * 
	 * @param Static_Press_Document_Root_Getter $document_root_getter Document root getter.
	 */
	public function __construct( $document_root_getter = null ) {
		$document_root_getter = $document_root_getter ? $document_root_getter : new Static_Press_Document_Root_Getter();
		$this->document_root  = trailingslashit( $document_root_getter->get() );
	}

	/**
	 * Creates.
	 * 
	 * @param string $file_type   File type.
	 * @param string $static_file Static file.
	 */
	public function create( $file_type, $static_file ) {
		return new Static_Press_Model_Url_Static_File( $file_type, $this->document_root, $static_file );
	}

	/**
	 * Converts to relative URL.
	 * 
	 * @param string $absolute_file_path Absolute file path.
	 */
	private function convert_to_relative_url( $absolute_file_path ) {
		$relative_url = str_replace(
			$this->document_root,
			trailingslashit( Static_Press_Site_Dependency::get_site_url() ),
			$absolute_file_path
		);
		return apply_filters( 'StaticPress::get_url', $relative_url );
	}
}

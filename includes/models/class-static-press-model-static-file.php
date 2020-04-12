<?php
/**
 * Class Static_Press_Model_Static_File
 *
 * @package static_press\includes\models
 */

namespace static_press\includes\models;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/exceptions/class-static-press-business-logic-exception.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/models/class-static-press-model-url-failed.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'includes/models/class-static-press-model-url-succeed.php';
use static_press\includes\exceptions\Static_Press_Business_Logic_Exception;
use static_press\includes\models\Static_Press_Model_Url_Failed;
use static_press\includes\models\Static_Press_Model_Url_Succeed;
/**
 * Plugin information.
 */
class Static_Press_Model_Static_File {
	/**
	 * List of extension of static file.
	 * 
	 * @var string[]
	 */
	// Reason: This project no longer support PHP 5.5 nor lower.
	const ARRAY_EXTENSION = array( //phpcs:ignore
		'html',
		'htm',
		'txt',
		'css',
		'js',
		'gif',
		'png',
		'jpg',
		'jpeg',
		'mp3',
		'ico',
		'ttf',
		'woff',
		'woff2',
		'otf',
		'eot',
		'svg',
		'svgz',
		'xml',
		'gz',
		'zip',
		'pdf',
		'swf',
		'xsl',
		'mov',
		'mp4',
		'wmv',
		'flv',
		'webm',
		'ogg',
		'oga',
		'ogv',
		'ogx',
		'spx',
		'opus',
	);
	/**
	 * HTTP status code.
	 * 
	 * @var int
	 */
	public $http_code;
	/**
	 * URL.
	 * 
	 * @var string
	 */
	public $url;
	/**
	 * Directory seperator.
	 * 
	 * @var string
	 */
	public $dir_sep;
	/**
	 * Destination of file.
	 * 
	 * @var string
	 */
	public $file_dest;
	/**
	 * File date.
	 * 
	 * @var string
	 */
	public $file_date;

	/**
	 * Constructor.
	 * 
	 * @param string $url            URL.
	 * @param string $dump_directory Directory to dump.
	 */
	public function __construct( $url, $dump_directory ) {
		$this->http_code = 200;
		$this->url       = apply_filters( 'StaticPress::get_url', $url );
		$this->dir_sep   = defined( 'DIRECTORY_SEPARATOR' ) ? DIRECTORY_SEPARATOR : '/';
		$this->file_dest = untrailingslashit( $dump_directory ) . $this->static_url( $this->url );
		if ( '/' !== $this->dir_sep ) {
			$this->file_dest = str_replace( '/', $this->dir_sep, $this->file_dest );
		}
	}

	/**
	 * Executes file_put action.
	 * 
	 * @param string $static_site_url Absolute URL of static site.
	 */
	public function do_file_put_action( $static_site_url ) {
		do_action( 'StaticPress::file_put', $this->file_dest, untrailingslashit( $static_site_url ) . $this->static_url( $this->url ) );
	}

	/**
	 * Returns index.html based on permalink when permalink doesn't end with extension.
	 * Otherwise, returns argument.
	 * 
	 * @param string $permalink Permalink.
	 * @return string Static URL.
	 */
	public static function static_url( $permalink ) {
		return urldecode(
			preg_match( '/\.[^\.]+?$/i', $permalink ) 
			? $permalink
			: trailingslashit( trim( $permalink ) ) . 'index.html'
		);
	}

	/**
	 * Checks file existance and creates array type URL.
	 * 
	 * @param string $file_type         File type.
	 * @param string $date_time_factory Date time factory.
	 * @return Static_Press_Model_Url URL model.
	 */
	public function check_file_existance_and_create_array_url( $file_type, $date_time_factory ) {
		try {
			return new Static_Press_Model_Url_Succeed( $file_type, $this->url, $this->check_file_existance(), $this->file_date, $this->http_code, $date_time_factory );
		} catch ( Static_Press_Business_Logic_Exception $exception ) {
			return new Static_Press_Model_Url_Failed( $file_type, $this->url, $date_time_factory );
		}
	}

	/**
	 * Checks file existance.
	 * 
	 * @return string Local file path to static file.
	 * @throws Static_Press_Business_Logic_Exception Case when static file does not exist.
	 */
	public function check_file_existance() {
		if ( ! file_exists( $this->file_dest ) ) {
			throw new Static_Press_Business_Logic_Exception();
		}
		return $this->file_dest;
	}

	/**
	 * Gets filtered array of extension.
	 * 
	 * @return array Filtered array of extension.
	 */
	public static function get_filtered_array_extension() {
		return apply_filters( 'StaticPress::static_files_filter', self::ARRAY_EXTENSION );
	}
}

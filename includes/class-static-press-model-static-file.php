<?php
/**
 * Class Static_Press_Model_Static_File
 *
 * @package static_press\includes
 */

namespace static_press\includes;

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
	 * @param string $file_type File type.
	 * @return array URL model (array).
	 */
	public function check_file_existance_and_create_array_url( $file_type ) {
		if ( file_exists( $this->file_dest ) ) {
			return array(
				'type'            => $file_type,
				'url'             => $this->url,
				'file_name'       => $this->file_dest,
				'file_date'       => $this->file_date,
				'last_statuscode' => $this->http_code,
				'last_upload'     => date( 'Y-m-d h:i:s', time() ),
			);
		} else {
			$this->file_dest = false;
			return array(
				'type'            => $file_type,
				'url'             => $this->url,
				'file_name'       => '',
				'last_statuscode' => 404,
				'last_upload'     => date( 'Y-m-d h:i:s', time() ),
			);
		}
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

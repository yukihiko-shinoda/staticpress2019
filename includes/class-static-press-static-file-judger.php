<?php
/**
 * Class Static_Press_Static_FIle_Judger
 *
 * @package static_press\includes
 */

namespace static_press\includes;

/**
 * Model URL.
 */
class Static_Press_Static_FIle_Judger {
	/**
	 * Directory of plugin.
	 * 
	 * @var string
	 */
	private $directory_plugin;
	/**
	 * Directory of theme.
	 * 
	 * @var string
	 */
	private $directory_theme;
	/**
	 * Directory to dump.
	 * 
	 * @var string
	 */
	private $directory_dump;
	/**
	 * Pattern.
	 * 
	 * @var string
	 */
	private $pattern;

	/**
	 * Constructor.
	 * 
	 * @param string $dump_directory Directory to dump.
	 */
	public function __construct( $dump_directory ) {
		$this->directory_plugin = trailingslashit( str_replace( ABSPATH, '/', WP_PLUGIN_DIR ) );
		$this->directory_theme  = trailingslashit( str_replace( ABSPATH, '/', WP_CONTENT_DIR ) . '/themes' );
		$this->directory_dump   = untrailingslashit( $dump_directory );
		$this->pattern          = '#^(/(readme|readme-[^\.]+|license)\.(txt|html?)|(' . preg_quote( $this->directory_plugin ) . '|' . preg_quote( $this->directory_theme ) . ').*/((readme|changelog|license)\.(txt|html?)|(screenshot|screenshot-[0-9]+)\.(png|jpe?g|gif)))$#i';
	}

	/**
	 * Classifies URL of static file.
	 * 
	 * @param Static_Press_Model_Url $model_url URL.
	 * @return int should not dump: 0, should dump: 1
	 */
	public function classify( $model_url ) {
		$url              = $model_url->get_url();
		$directory_source = $model_url->get_directory_source();
		$file_source      = $directory_source . $url;
		$file_dest        = $this->directory_dump . $url;
		switch ( true ) {
			case $file_source === $file_dest:        // Seems to intend to prevent from being duplicate dump process for already dumped files.
			case preg_match( $this->pattern, $url ): // Seems to intend readme, license, changelog, screenshot in plugin directory or theme directory.
			case ! file_exists( $file_source ):      // Seems to intend to prevent from processing non exist files.
			case file_exists( $file_dest ) && filemtime( $file_source ) <= filemtime( $file_dest ): // Seems to intend to skip non update files after last dump.
				return 0;
			case preg_match( '#^' . preg_quote( $this->directory_plugin ) . '#i', $url ):
				return $this->classify_static_file_plugin( $url );
			case preg_match( '#^' . preg_quote( $this->directory_theme ) . '#i', $url ):
				return $this->classify_static_file_theme( $url );
			default:
				return 1;
		}
	}

	/**
	 * Classifies URL of plugin's static file.
	 * Original specification seems to intend to dump only active plugin.
	 * 
	 * @param string $url        URL.
	 * @return int should not dump: 0, should dump: 1
	 */
	private function classify_static_file_plugin( $url ) {
		$active_plugins = get_option( 'active_plugins' );
		foreach ( $active_plugins as $active_plugin ) {
			$active_plugin = trailingslashit( $this->directory_plugin . dirname( $active_plugin ) );
			if ( trailingslashit( $this->directory_plugin . '.' ) == $active_plugin ) {
				// TODO What is the intension? Commited at 2013-04-23 11:50:42 5a470855fe94ef754b156cc062ab86eab452446d .
				continue;
			}
			if ( preg_match( '#^' . preg_quote( $active_plugin ) . '#i', $url ) ) {
				return 1;
			}
		}
		return 0;
	}

	/**
	 * Classifies URL of theme's static file.
	 * Original specification seems to intend to dump only current theme.
	 * 
	 * @param string $url       URL.
	 * @return int should not dump: 0, should dump: 1
	 */
	private function classify_static_file_theme( $url ) {
		$current_theme = trailingslashit( $this->directory_theme . get_stylesheet() );
		if ( preg_match( '#^' . preg_quote( $current_theme ) . '#i', $url ) ) {
			return 1;
		}
		return 0;
	}
}

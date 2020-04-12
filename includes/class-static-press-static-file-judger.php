<?php
/**
 * Class Static_Press_Static_FIle_Judger
 *
 * @package static_press\includes
 */

namespace static_press\includes;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/infrastructure/class-static-press-document-root-getter.php';
use static_press\includes\infrastructure\Static_Press_Document_Root_Getter;
/**
 * Model URL.
 */
class Static_Press_Static_FIle_Judger {
	/**
	 * Directory to dump.
	 * 
	 * @var string
	 */
	private $directory_dump;
	/**
	 * Document root.
	 * 
	 * @var string
	 */
	private $document_root;
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
	 * Pattern.
	 * 
	 * @var string
	 */
	private $pattern;
	/**
	 * Regex for detecting parent theme.
	 * 
	 * @var string
	 */
	private $regex_theme_parent;
	/**
	 * Regex for detecting child theme.
	 * 
	 * @var string
	 */
	private $regex_theme_child;

	/**
	 * Constructor.
	 * 
	 * @param string                            $dump_directory       Directory to dump.
	 * @param Static_Press_Document_Root_Getter $document_root_getter Document root getter.
	 */
	public function __construct( $dump_directory, $document_root_getter = null ) {
		$this->directory_dump       = untrailingslashit( $dump_directory );
		$document_root_getter       = $document_root_getter ? $document_root_getter : new Static_Press_Document_Root_Getter();
		$this->document_root        = $document_root_getter->get();
		$this->directory_plugin     = trailingslashit( str_replace( $this->document_root, '', WP_PLUGIN_DIR ) );
		$this->directory_theme      = trailingslashit( str_replace( $this->document_root, '', WP_CONTENT_DIR ) . '/themes' );
		$relative_path_to_wordpress = trailingslashit( str_replace( $this->document_root, '', ABSPATH ) );
		$this->pattern              = '#^(' . $relative_path_to_wordpress . '(readme|readme-[^\.]+|license)\.(txt|html?)|(' . preg_quote( $this->directory_plugin ) . '|' . preg_quote( $this->directory_theme ) . ').*/((readme|changelog|license)\.(txt|html?)|(screenshot|screenshot-[0-9]+)\.(png|jpe?g|gif)))$#i';
		$this->regex_theme_parent   = '#^' . preg_quote( str_replace( $this->document_root, '', get_template_directory() ) ) . '#i';
		$this->regex_theme_child    = '#^' . preg_quote( str_replace( $this->document_root, '', get_stylesheet_directory() ) ) . '#i';
	}

	/**
	 * Classifies URL of static file.
	 * 
	 * @param Static_Press_Model_Url $model_url URL.
	 * @return int should not dump: 0, should dump: 1
	 */
	public function classify( $model_url ) {
		$url         = $model_url->get_url();
		$file_source = $this->document_root . $url;
		$file_dest   = $this->directory_dump . $url;
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
		switch ( true ) {
			case preg_match( $this->regex_theme_parent, $url ):
			case preg_match( $this->regex_theme_child, $url ):
				return 1;
			default:
				return 0;
		}
	}
}

<?php
/**
 * Class Static_Press_Url_Updater
 *
 * @package static_press\includes
 */

namespace static_press\includes;

/**
 * URL Updater.
 */
class Static_Press_Url_Updater {
	/**
	 * Repository.
	 * 
	 * @var Static_Press_Repository
	 */
	private $repository;
	/**
	 * Directory to dump static files.
	 * 
	 * @var string
	 */
	private $dump_directory;

	/**
	 * Constructor.
	 * 
	 * @param Static_Press_Repository $repository Repository.
	 * @param Static_Press_Repository $dump_directory Directory to dump static files.
	 */
	public function __construct( $repository, $dump_directory ) {
		$this->repository     = $repository;
		$this->dump_directory = $dump_directory;
	}
	/**
	 * Updates URL.
	 * 
	 * @param  array $urls URLs.
	 */
	public function update( $urls ) {
		foreach ( (array) $urls as $url ) {
			if ( ! isset( $url['url'] ) || ! $url['url'] ) {
				continue;
			}
			$url['enable'] = $this->classify( $url );

			$id = $this->repository->get_id( $url['url'] );
			if ( $id ) {
				$this->repository->update_url( $id, $url );
			} else {
				$this->repository->insert_url( $url );
			}

			do_action( 'StaticPress::update_url', $url );
		}
	}

	/**
	 * Classifies URL whether should dump or not.
	 * 
	 * @param array $url URL.
	 * @return int should not dump: 0, should dump: 1
	 */
	private function classify( $url ) {
		if ( preg_match( '#\.php$#i', $url['url'] ) ) {
			// Seems to intend PHP file.
			return 0;
		}
		if ( preg_match( '#\?[^=]+[=]?#i', $url['url'] ) ) {
			// Seems to intend get request with parameter.
			return 0;
		}
		if ( preg_match( '#/wp-admin/$#i', $url['url'] ) ) {
			// Seems to intend WordPress admin home page.
			return 0;
		}
		if ( ! isset( $url['type'] ) || 'static_file' != $url['type'] ) {
			return 1;
		}
		return $this->classify_static_file( $url['url'] );
	}

	/**
	 * Classifies URL of static file.
	 * 
	 * @param string $url URL.
	 * @return int should not dump: 0, should dump: 1
	 */
	private function classify_static_file( $url ) {
		$plugin_dir  = trailingslashit( str_replace( ABSPATH, '/', WP_PLUGIN_DIR ) );
		$theme_dir   = trailingslashit( str_replace( ABSPATH, '/', WP_CONTENT_DIR ) . '/themes' );
		$file_source = untrailingslashit( ABSPATH ) . $url;
		$file_dest   = untrailingslashit( $this->dump_directory ) . $url;
		$pattern     = '#^(/(readme|readme-[^\.]+|license)\.(txt|html?)|(' . preg_quote( $plugin_dir ) . '|' . preg_quote( $theme_dir ) . ').*/((readme|changelog|license)\.(txt|html?)|(screenshot|screenshot-[0-9]+)\.(png|jpe?g|gif)))$#i';
		if ( $file_source === $file_dest ) {
			// Seems to intend to prevent from being duplicate dump process for already dumped files.
			return 0;
		}
		if ( preg_match( $pattern, $url ) ) {
			// Seems to intend readme, license, changelog, screenshot in plugin directory or theme directory.
			return 0;
		}
		if ( ! file_exists( $file_source ) ) {
			// Seems to intend to prevent from processing non exist files.
			return 0;
		}
		if ( file_exists( $file_dest ) && filemtime( $file_source ) <= filemtime( $file_dest ) ) {
			// Seems to intend to skip non update files after last dump.
			return 0;
		}
		if ( preg_match( '#^' . preg_quote( $plugin_dir ) . '#i', $url ) ) {
			return $this->classify_static_file_plugin( $url, $plugin_dir );
		}
		if ( preg_match( '#^' . preg_quote( $theme_dir ) . '#i', $url ) ) {
			return $this->classify_static_file_theme( $url, $theme_dir );
		}
		return 1;
	}

	/**
	 * Classifies URL of plugin's static file.
	 * Original specification seems to intend to dump only active plugin.
	 * 
	 * @param string $url        URL.
	 * @param string $plugin_dir Plugin directory.
	 * @return int should not dump: 0, should dump: 1
	 */
	private function classify_static_file_plugin( $url, $plugin_dir ) {
		$active_plugins = get_option( 'active_plugins' );
		foreach ( $active_plugins as $active_plugin ) {
			$active_plugin = trailingslashit( $plugin_dir . dirname( $active_plugin ) );
			if ( trailingslashit( $plugin_dir . '.' ) == $active_plugin ) {
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
	 * @param string $theme_dir Theme directory.
	 * @return int should not dump: 0, should dump: 1
	 */
	private function classify_static_file_theme( $url, $theme_dir ) {
		$current_theme = trailingslashit( $theme_dir . get_stylesheet() );
		if ( preg_match( '#^' . preg_quote( $current_theme ) . '#i', $url ) ) {
			return 1;
		}
		return 0;
	}
}

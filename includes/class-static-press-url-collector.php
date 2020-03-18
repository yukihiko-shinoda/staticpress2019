<?php
/**
 * Class Static_Press_Url_Collector
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Content_Filter' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-content-filter.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_File_Scanner' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-file-scanner.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Model_Url' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-model-url.php';
}
if ( ! class_exists( 'static_press\includes\Static_Press_Site_Dependency' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-site-dependency.php';
}
use static_press\includes\Static_Press_Content_Filter;
use static_press\includes\Static_Press_File_Scanner;
use static_press\includes\Static_Press_Model_Url;
use static_press\includes\Static_Press_Site_Dependency;

/**
 * URL Collector.
 */
class Static_Press_Url_Collector {
	/**
	 * Remote get options.
	 * 
	 * @var Static_Press_Remote_Getter
	 */
	private $remote_getter;
	/**
	 * Date time factory.
	 * 
	 * @var Static_Press_Date_Time_Factory
	 */
	private $date_time_factory;
	/**
	 * Constructor.
	 * 
	 * @param Static_Press_Remote_Getter     $remote_getter     Remote getter.
	 * @param Static_Press_Date_Time_Factory $date_time_factory Date time factory.
	 */
	public function __construct( $remote_getter, $date_time_factory = null ) {
		$this->remote_getter     = $remote_getter;
		$this->date_time_factory = $date_time_factory ? $date_time_factory : new Static_Press_Date_Time_Factory();
	}

	/**
	 * Gets site URL.
	 * 
	 * @return string Site URL.
	 */
	public static function get_site_url() {
		return Static_Press_Site_Dependency::get_site_url();
	}

	/**
	 * Collects URLs.
	 * 
	 * @return array Collected URLs.
	 */
	public function collect() {
		return array_merge(
			$this->front_page_url(),
			self::single_url(),
			$this->terms_url(),
			self::author_url(),
			$this->static_files_url(),
			$this->seo_url()
		);
	}

	/**
	 * Gets front page URL.
	 * 
	 * @return array Front page URL.
	 */
	private function front_page_url() {
		$urls     = array();
		$site_url = self::get_site_url();
		$urls[]   = array(
			'type'          => 'front_page',
			'url'           => apply_filters( 'StaticPress::get_url', $site_url ),
			'last_modified' => $this->date_time_factory->create_date( 'Y-m-d h:i:s' ),
		);
		return $urls;
	}

	/**
	 * Gets URLs of posts.
	 */
	private static function single_url() {
		$post_types = get_post_types( array( 'public' => true ) );
		$repository = new Static_Press_Repository();
		$posts      = $repository->get_posts( $post_types );
		$urls       = array();
		foreach ( $posts as $post ) {
			$post_id   = $post->ID;
			$modified  = $post->post_modified;
			$permalink = get_permalink( $post->ID );
			if ( false === $permalink || is_wp_error( $permalink ) ) {
				// TODO Is is_wp_error() correct? Commited at 2013-04-22 22:54:05 450c6ce5731b27fc98707d8a881844778ced4763 .
				continue;
			}
			$count  = 1;
			$splite = preg_split( '#<!--nextpage-->#', $post->post_content );
			if ( $splite ) {
				$count = count( $splite );
			}
			$urls[] = array(
				'type'          => 'single',
				'url'           => apply_filters( 'StaticPress::get_url', $permalink ),
				'object_id'     => intval( $post_id ),
				'object_type'   => $post->post_type,
				'pages'         => $count,
				'last_modified' => $modified,
			);
		}
		return $urls;
	}

	/**
	 * Gets URLs of terms.
	 * 
	 * @param string $url_type URL type.
	 */
	private function terms_url( $url_type = 'term_archive' ) {
		$repository = new Static_Press_Repository();
		$urls       = array();
		$taxonomies = get_taxonomies( array( 'public' => true ) );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms( $taxonomy );
			if ( is_wp_error( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term ) {
				$term_id  = $term->term_id;
				$termlink = get_term_link( $term->slug, $taxonomy );
				if ( is_wp_error( $termlink ) ) {
					continue;
				}
				list( $modified, $page_count ) = $this->get_term_info( $term_id, $repository );
				$urls[]                        = array(
					'type'          => $url_type,
					'url'           => apply_filters( 'StaticPress::get_url', $termlink ),
					'object_id'     => intval( $term_id ),
					'object_type'   => $term->taxonomy,
					'parent'        => $term->parent,
					'pages'         => $page_count,
					'last_modified' => $modified,
				);

				$termchildren = get_term_children( $term->term_id, $taxonomy );
				if ( is_wp_error( $termchildren ) ) {
					continue;
				}
				foreach ( $termchildren as $child ) {
					$term    = get_term_by( 'id', $child, $taxonomy );
					$term_id = $term->term_id;
					if ( is_wp_error( $term ) ) {
						continue;
					}
					$termlink = get_term_link( $term->name, $taxonomy );
					if ( is_wp_error( $termlink ) ) {
						continue;
					}
					list( $modified, $page_count ) = $this->get_term_info( $term_id, $repository );
					$urls[]                        = array(
						'type'          => $url_type,
						'url'           => apply_filters( 'StaticPress::get_url', $termlink ),
						'object_id'     => intval( $term_id ),
						'object_type'   => $term->taxonomy,
						'parent'        => $term->parent,
						'pages'         => $page_count,
						'last_modified' => $modified,
					);
				}
			}
		}
		return $urls;
	}

	/**
	 * Gets term information.
	 * 
	 * @param int                     $term_id    Term ID.
	 * @param Static_Press_Repository $repository Repository.
	 */
	private function get_term_info( $term_id, $repository ) {
		$result = $repository->get_term_info( $term_id, get_post_types( array( 'public' => true ) ) );
		if ( ! is_wp_error( $result ) ) {
			$modified = $result->last_modified;
			$count    = $result->count;
		} else {
			$modified = date( 'Y-m-d h:i:s' );
			$count    = 1;
		}
		$page_count = intval( $count / intval( get_option( 'posts_per_page' ) ) ) + 1;
		return array( $modified, $page_count );
	}

	/**
	 * Gets URLs of authors.
	 */
	private static function author_url() {
		$post_types = get_post_types( array( 'public' => true ) );
		$repository = new Static_Press_Repository();
		$authors    = $repository->get_post_authors( $post_types );
		$urls       = array();
		foreach ( $authors as $author ) {
			$author_id  = $author->post_author;
			$page_count = intval( $author->count / intval( get_option( 'posts_per_page' ) ) ) + 1;
			$modified   = $author->modified;
			$author     = get_userdata( $author_id );
			if ( is_wp_error( $author ) ) {
				continue;
			}
			$authorlink = get_author_posts_url( $author->ID, $author->user_nicename );
			if ( is_wp_error( $authorlink ) ) {
				continue;
			}
			$urls[] = array(
				'type'          => 'author_archive',
				'url'           => apply_filters( 'StaticPress::get_url', $authorlink ),
				'object_id'     => intval( $author_id ),
				'pages'         => $page_count,
				'last_modified' => $modified,
			);
		}
		return $urls;
	}

	/**
	 * Gets URLs of static files.
	 */
	private function static_files_url() {
		$file_scanner = new Static_Press_File_Scanner( Static_Press_Model_Static_File::get_filtered_array_extension() );
		$static_files = array_merge(
			$file_scanner->scan( trailingslashit( ABSPATH ), false ),
			$file_scanner->scan( trailingslashit( ABSPATH ) . 'wp-admin/', true ),
			$file_scanner->scan( trailingslashit( ABSPATH ) . 'wp-includes/', true ),
			$file_scanner->scan( trailingslashit( WP_CONTENT_DIR ), true )
		);

		$urls = array();
		foreach ( $static_files as $static_file ) {
			$static_file_url = str_replace( trailingslashit( ABSPATH ), trailingslashit( $this->get_site_url() ), $static_file );
			$urls[]          = array(
				'type'          => Static_Press_Model_Url::TYPE_STATIC_FILE,
				'url'           => apply_filters( 'StaticPress::get_url', $static_file_url ),
				'last_modified' => date( 'Y-m-d h:i:s', filemtime( $static_file ) ),
			);
		}
		return $urls;
	}

	/**
	 * Checks correct sitemap URL by robots.txt.
	 */
	private function seo_url() {
		$url_type = 'seo_files';
		$urls     = array();
		$analyzed = array();
		$sitemap  = '/sitemap.xml';
		$robots   = '/robots.txt';
		$urls[]   = array(
			'type'          => $url_type,
			'url'           => $robots,
			'last_modified' => date( 'Y-m-d h:i:s' ),
		);
		$txt      = $this->remote_get( $robots );
		if ( $txt && isset( $txt['body'] ) ) {
			$http_code = intval( $txt['code'] );
			switch ( intval( $http_code ) ) {
				case 200:
					if ( preg_match( '/sitemap:\s.*?(\/[\-_a-z0-9%]+\.xml)/i', $txt['body'], $match ) ) {
						$sitemap = $match[1];
					}
			}
		}
		$this->sitemap_analyzer( $analyzed, $urls, $sitemap, $url_type );
		return $urls;
	}

	/**
	 * Crawls sitemap XML files.
	 * 
	 * @param array  $analyzed Analyzed.
	 * @param array  $urls     URLs.
	 * @param string $url      URL.
	 * @param string $url_type URL type.
	 */
	private function sitemap_analyzer( &$analyzed, &$urls, $url, $url_type ) {
		$urls[]     = array(
			'type'          => $url_type,
			'url'           => $url,
			'last_modified' => date( 'Y-m-d h:i:s' ),
		);
		$analyzed[] = $url;
		$xml        = $this->remote_get( $url );
		if ( $xml && isset( $xml['body'] ) ) {
			$http_code = intval( $xml['code'] );
			switch ( intval( $http_code ) ) {
				case 200:
					if ( preg_match_all( '/<loc>(.*?)<\/loc>/i', $xml['body'], $matches ) ) {
						foreach ( $matches[1] as $link ) {
							if ( preg_match( '/\/([\-_a-z0-9%]+\.xml)$/i', $link, $match_sub ) ) {
								if ( ! in_array( $match_sub[0], $analyzed ) ) {
									$this->sitemap_analyzer( $analyzed, $urls, $match_sub[0], $url_type );
								}
							}
						}
					}
			}
		}
	}

	/**
	 * Gets remote content via HTTP / HTTPS access.
	 * 
	 * @param string $url URL.
	 */
	public function remote_get( $url ) {
		if ( ! preg_match( '#^https://#i', $url ) ) {
			$url = untrailingslashit( self::get_site_url() ) . ( preg_match( '#^/#i', $url ) ? $url : "/{$url}" );
		}
		$response = $this->remote_getter->remote_get( $url );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		return array(
			'code' => $response['response']['code'],
			'body' => Static_Press_Content_Filter::remove_link_tag( $response['body'], intval( $response['response']['code'] ) ),
		);
	}
}

<?php
/**
 * Class Static_Press_Adapter_Transient
 *
 * @package static_press\includes
 */

namespace static_press\includes;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-input-validator.php';
use InputValidator;
use WP_Error;
/**
 * Adapter for Transient.
 */
class Static_Press_Adapter_Option {
	const OPTION_STATIC_URL     = 'StaticPress::static url';
	const OPTION_STATIC_DIR     = 'StaticPress::static dir';
	const OPTION_STATIC_BASIC   = 'StaticPress::basic auth';
	const OPTION_STATIC_TIMEOUT = 'StaticPress::timeout';
	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	private $nonce_action;
	/**
	 * Nonce name.
	 *
	 * @var string
	 */
	private $nonce_name;
	/**
	 * Error.
	 *
	 * @var WP_Error
	 */
	public $error;
	/**
	 * Constructor.
	 *
	 * @param string $nonce_action Nonce action.
	 * @param string $nonce_name   Nonce Name.
	 */
	public function __construct( $nonce_action, $nonce_name ) {
		$this->nonce_action = $nonce_action;
		$this->nonce_name   = $nonce_name;
		$this->error        = new WP_Error();
	}

	/**
	 * Validates and retrieves the first error code available.
	 *
	 * @return string|int|null Null, if not posted, Empty string, if no error codes.
	 */
	public function validate() {
		$iv = new InputValidator( 'POST' );
		$iv->set_rules( $this->nonce_name, 'required' );
		$iv->set_rules( 'static_url', array( 'trim', 'esc_html', 'url', 'required' ) );
		$iv->set_rules( 'static_dir', array( 'trim', 'esc_html', 'required' ) );
		$iv->set_rules( 'basic_usr', array( 'trim', 'esc_html' ) );
		$iv->set_rules( 'basic_pwd', array( 'trim', 'esc_html' ) );
		$iv->set_rules( 'timeout', array( 'numeric', 'required' ) );

		if ( is_wp_error( $iv->input( $this->nonce_name ) ) || ! check_admin_referer( $this->nonce_action, $this->nonce_name ) ) {
			return null;
		}
		// Get posted options.
		$static_url = $iv->input( 'static_url' );
		$static_dir = $iv->input( 'static_dir' );
		$basic_usr  = $iv->input( 'basic_usr' );
		$basic_pwd  = $iv->input( 'basic_pwd' );
		$timeout    = $iv->input( 'timeout' );
		$basic_auth =
			( $basic_usr && $basic_pwd )
			? base64_encode( "{$basic_usr}:{$basic_pwd}" )
			: false;

		// Update options.
		if ( is_wp_error( $static_url ) ) {
			$this->error->add( 'error', $static_url->get_error_messages() );
		} else {
			update_option( self::OPTION_STATIC_URL, $static_url );
		}
		if ( is_wp_error( $static_dir ) ) {
			$this->error->add( 'error', $static_dir->get_error_messages() );
		} else {
			update_option( self::OPTION_STATIC_DIR, $static_dir );
		}
		update_option( self::OPTION_STATIC_BASIC, $basic_auth );
		if ( is_wp_error( $timeout ) ) {
			$this->error->add( 'error', $timeout->get_error_messages() );
		} else {
			update_option( self::OPTION_STATIC_TIMEOUT, $timeout );
		}
		return $this->error->get_error_code();
	}

	/**
	 * Gets decoded basic auth.
	 * 
	 * @return string[] Decoded basic auth.
	 */
	public function get_basic_auth_decoded() {
		return $this->basic_auth() ? explode( ':', base64_decode( $this->basic_auth() ) ) : array( '', '' );
	}

	/**
	 * Gets option: Static URL.
	 */
	public static function static_url() {
		return get_option( self::OPTION_STATIC_URL, self::get_site_url() . 'static/' );
	}

	/**
	 * Gets option: Save DIR (Document root).
	 */
	public static function static_dir() {
		return get_option( self::OPTION_STATIC_DIR, ABSPATH );
	}

	/**
	 * Gets option: BASIC Auth User and BASIC Auth Password.
	 */
	public static function basic_auth() {
		return get_option( self::OPTION_STATIC_BASIC, false );
	}

	/**
	 * Gets option: Request Timeout.
	 */
	public static function timeout() {
		return get_option( self::OPTION_STATIC_TIMEOUT, 5 );
	}

	/**
	 * Gets options of wp_remote_get().
	 */
	public static function remote_get_option() {
		$options    = array();
		$basic_auth = self::basic_auth();
		if ( $basic_auth ) {
			$options['headers'] = array( 'Authorization' => 'Basic ' . $basic_auth );
		}
		$timeout = self::timeout();
		if ( $timeout ) {
			$options['timeout'] = (int) $timeout;
		}
		return $options;
	}

	/**
	 * Gets site URL.
	 */
	public static function get_site_url() {
		global $current_blog;
		return trailingslashit(
			isset( $current_blog )
			? get_home_url( $current_blog->blog_id )
			: get_home_url()
		);
	}
}

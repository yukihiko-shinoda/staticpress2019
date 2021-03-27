<?php
/**
 * Class Static_Press_Admin
 *
 * @package static_press\includes
 */

namespace static_press\includes;

require_once STATIC_PRESS_PLUGIN_DIR . 'includes/class-static-press-adapter-option.php';
use static_press\includes\Static_Press_Adapter_Option;
/**
 * StaticPress Admin page.
 */
class Static_Press_Admin {
	const OPTION_PAGE = 'static-press';
	/**
	 * Plugin name.
	 * 
	 * @var string
	 */
	const TEXT_DOMAIN  = 'static-press';
	const DEBUG_MODE   = false;
	const ACCESS_LEVEL = 'manage_options';

	/**
	 * The result of plugin_basename( __FILE__ ) at plugin main file.
	 * 
	 * @var string
	 */
	private $plugin_basename;
	/**
	 * Transrated plugin name option.
	 * 
	 * @var string
	 */
	private $translated_plugin_name_option;
	/**
	 * URL to Settings page of StaticPress.
	 * 
	 * @var string
	 */
	private $admin_action;

	/**
	 * Constructor.
	 * 
	 * @param string $plugin_basename The result of plugin_basename( __FILE__ ) at plugin main file.
	 */
	public function __construct( $plugin_basename ) {
		$this->plugin_basename = $plugin_basename;
		$this->admin_action    = admin_url( '/admin.php' ) . '?page=' . self::OPTION_PAGE . '-options';

		$this->translated_plugin_name_option = __( 'StaticPress2019 Options', 'static-press' );

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_filter( 'plugin_action_links', array( $this, 'plugin_setting_links' ), 10, 2 );

		add_action( 'admin_head', array( $this, 'add_admin_head' ), 99 );
	}

	/**
	 * Gets option: Static URL.
	 */
	public static function static_url() {
		return Static_Press_Adapter_Option::static_url();
	}

	/**
	 * Gets option: Save DIR (Document root).
	 */
	public static function static_dir() {
		return Static_Press_Adapter_Option::static_dir();
	}

	/**
	 * Gets option: BASIC Auth User and BASIC Auth Password.
	 */
	public static function basic_auth() {
		return Static_Press_Adapter_Option::basic_auth();
	}

	/**
	 * Gets option: Request Timeout.
	 */
	public static function timeout() {
		return Static_Press_Adapter_Option::timeout();
	}

	/**
	 * Gets options of wp_remote_get().
	 */
	public static function remote_get_option() {
		return Static_Press_Adapter_Option::remote_get_option();
	}

	/**
	 * Gets site URL.
	 */
	public static function get_site_url() {
		return Static_Press_Adapter_Option::get_site_url();
	}

	/**
	 * Adds Admin Menu.
	 */
	public function admin_menu() {
		$translated_plugin_name = __( 'StaticPress2019', 'static-press' );
		$hook                   = add_menu_page(
			$translated_plugin_name,
			$translated_plugin_name,
			self::ACCESS_LEVEL,
			self::OPTION_PAGE,
			array( $this, 'static_press_page' ),
			plugins_url( 'images/staticpress.png', dirname( __FILE__ ) )
		);
		add_action( 'admin_print_scripts-' . $hook, array( $this, 'do_admin_scripts' ) );

		$hook = add_submenu_page(
			self::OPTION_PAGE,
			$this->translated_plugin_name_option,
			$this->translated_plugin_name_option,
			self::ACCESS_LEVEL,
			self::OPTION_PAGE . '-options',
			array( $this, 'options_page' )
		);

		do_action( 'StaticPress::admin_menu', self::OPTION_PAGE );
	}

	/**
	 * Does admin scripts.
	 */
	public function do_admin_scripts() {
		do_action( 'StaticPress::admin_scripts' );
	}

	/**
	 * Add style sheet for StaticPress in <head> tag.
	 */
	public function add_admin_head() {
		?>

<style type="text/css" id="<?php echo self::OPTION_PAGE; ?>-menu-css">
#toplevel_page_<?php echo self::OPTION_PAGE; ?> .wp-menu-image {
	background: url( <?php echo plugins_url( 'images/menuicon-splite.png', dirname( __FILE__ ) ); ?> ) 0 90% no-repeat !important;
}
#toplevel_page_<?php echo self::OPTION_PAGE; ?>.current .wp-menu-image,
#toplevel_page_<?php echo self::OPTION_PAGE; ?>.wp-has-current-submenu .wp-menu-image,
#toplevel_page_<?php echo self::OPTION_PAGE; ?>:hover .wp-menu-image {
	background-position: top left !important;
}
#icon-static-press {background-image: url(<?php echo plugins_url( 'images/rebuild32.png', dirname( __FILE__ ) ); ?>);}
#icon-static-press-options {background-image: url(<?php echo plugins_url( 'images/options32.png', dirname( __FILE__ ) ); ?>);}
</style>
		<?php
		do_action( 'StaticPress::admin_head' );
	}

	/**
	 * Renders StaticPress Options page.
	 */
	public function options_page() {
		$nonce_action = 'update_options';
		$nonce_name   = '_wpnonce_update_options';

		$adapter_option = new Static_Press_Adapter_Option( $nonce_action, $nonce_name );
		$validate = $adapter_option->validate();
		if ( $validate ) {
			$errors = $adapter_option->error->get_error_messages( 'error' );
			echo '<div id="message" class="error"><p><strong>';
			foreach ( $errors as $error ) {
				$err_message = $error[0];
				echo "$err_message" . '<br />';
			}
			echo '</strong></p></div>';
		} elseif ( null !== $validate ) {
			printf(
				'<div id="message" class="updated fade"><p><strong>%s</strong></p></div>' . "\n",
				__( 'Done!', 'static-press' )
			);
		}

		do_action( 'StaticPress::options_save' );

		list( $basic_usr, $basic_pwd ) = $adapter_option->get_basic_auth_decoded();
		?>
		<div class="wrap" id="<?php echo self::OPTION_PAGE; ?>-options">
		<h2><?php echo esc_html( $this->translated_plugin_name_option ); ?></h2>
		<form method="post" action="<?php echo $this->admin_action; ?>">
		<?php echo wp_nonce_field( $nonce_action, $nonce_name, true, false ) . "\n"; ?>
		<table class="wp-list-table fixed"><tbody>
		<?php $this->input_field( 'static_url', __( 'Static URL', 'static-press' ), $adapter_option->static_url() ); ?>
		<?php $this->input_field( 'static_dir', __( 'Save DIR (Document root)', 'static-press' ), $adapter_option->static_dir() ); ?>
		<?php $this->input_field( 'basic_usr', __( '(OPTION) BASIC Auth User', 'static-press' ), $basic_usr ); ?>
		<?php $this->input_field( 'basic_pwd', __( '(OPTION) BASIC Auth Password', 'static-press' ), $basic_pwd, 'password' ); ?>
		<?php $this->input_field( 'timeout', __( '(OPTION) Request Timeout', 'static-press' ), $adapter_option->timeout() ); ?>
		</tbody></table>
		<?php submit_button(); ?>
		</form>
		</div>
		<?php
		do_action( 'StaticPress::options_page' );
	}

	/**
	 * Renders input field.
	 * 
	 * @param string $field The name property of input tag.
	 * @param string $label Display name of input.
	 * @param any    $val   Default value.
	 * @param string $type  The type property of input tag.
	 */
	private function input_field( $field, $label, $val, $type = 'text' ) {
		$label       = sprintf( '<th><label for="%1$s">%2$s</label></th>' . "\n", $field, $label );
		$input_field = sprintf( '<td><input type="%3$s" name="%1$s" value="%2$s" id="%1$s" size=100 /></td>' . "\n", $field, esc_attr( $val ), $type );
		echo "<tr>\n{$label}{$input_field}</tr>\n";
	}

	/**
	 * Renders StaticPress main page.
	 */
	public function static_press_page() {
		$title = __( 'Rebuild', 'static-press' );
		?>
		<div class="wrap" style="margin=top:2em;" id="<?php echo self::OPTION_PAGE; ?>">
		<h2><?php echo esc_html( $title ); ?></h2>
		<?php submit_button( $title, 'primary', 'rebuild' ); ?>
		<div id="rebuild-result"></div>
		<div>
			<p hidden id="path-ajax-loader-gif"><?php echo plugins_url( 'images/ajax-loader.gif', dirname( __FILE__ ) ); ?></p>
			<p hidden id="text-initialize"><?php echo __( 'Initialyze...', 'static-press' ); ?></p>
			<p hidden id="text-urls"><?php echo __( 'URLS', 'static-press' ); ?></p>
			<p hidden id="text-fetch-start"><?php echo __( 'Fetch Start...', 'static-press' ); ?></p>
			<p hidden id="text-error"><?php echo __( 'Error!', 'static-press' ); ?></p>
			<p hidden id="text-end"><?php echo __( 'End', 'static-press' ); ?></p>
			<p hidden id="admin-ajax-php"><?php echo admin_url( 'admin-ajax.php' ); ?></p>
			<p hidden id="debug-mode"><?php echo self::DEBUG_MODE; ?></p>
		</div>
		</div>
		<?php
		wp_enqueue_script( 'jQuery', false, array(), false, true );
		add_action( 'admin_footer', array( $this, 'admin_footer' ) );

		do_action( 'StaticPress::static_press_page' );
	}

	/**
	 * Renders footer.
	 */
	public function admin_footer() {
		?>
<script type="text/javascript" src="<?php echo plugins_url( 'js/static-press-rebuild.js', dirname( __FILE__ ) ); ?>"></script>
		<?php
		do_action( 'StaticPress::admin_footer' );
	}

	/**
	 * Add [Settings] link in [Plugins] page in admin area.
	 * 
	 * @param array  $plugin_actions Plugin actions.
	 * @param string $plugin_file    Plugin file.
	 */
	public function plugin_setting_links( $plugin_actions, $plugin_file ) {
		if ( $plugin_file === $this->plugin_basename ) {
			$settings_link = '<a href="' . $this->admin_action . '">' . __( 'Settings', 'static-press' ) . '</a>';
			array_unshift( $plugin_actions, $settings_link );  // Before other links.
		}
		$plugin_actions = apply_filters( 'StaticPress::plugin_setting_links', $plugin_actions );

		return $plugin_actions;
	}
}

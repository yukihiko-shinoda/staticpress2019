<?php
/**
 * Class Static_Press_Plugin_Information
 *
 * @package static_press\includes
 */

namespace static_press\includes;

/**
 * Plugin information.
 */
class Static_Press_Plugin_Information {
	/**
	 * Plugin name.
	 * 
	 * @var string
	 */
	private $plugin_name;
	/**
	 * Plugin version
	 * 
	 * @var string
	 */
	private $plugin_version;
	/**
	 * Initializes.
	 */
	public function __construct() {
		$data                 = get_file_data(
			dirname( dirname( __FILE__ ) ) . '/plugin.php',
			array(
				'pluginname' => 'Plugin Name',
				'version'    => 'Version',
			)
		);
		$this->plugin_name    = isset( $data['pluginname'] ) ? $data['pluginname'] : 'StaticPress';
		$this->plugin_version = isset( $data['version'] ) ? $data['version'] : '';
	}

	/**
	 * Converts to string.
	 */
	public function __toString() {
		return $this->plugin_name . ( ! empty( $this->plugin_version ) ? ' ver.' . $this->plugin_version : '' );
	}
}

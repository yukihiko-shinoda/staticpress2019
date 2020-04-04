<?php
/**
 * Class Plugin_Switcher
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

/**
 * URL Collector.
 */
class Plugin_Switcher {
	/**
	 * Activates plugin.
	 * 
	 * @throws \LogicException Case when failed to activate plugin.
	 */
	public static function activate_plugin() {
		$result = activate_plugin( 'akismet/akismet.php' );
		if ( null !== $result ) {
			var_dump( $result );
			throw new \LogicException( 'Failed to activate plugin!' );
		}
	}

	/**
	 * Deactivates plugin.
	 * 
	 * @throws \LogicException Case when failed to deactivate plugin.
	 */
	public static function deactivate_plugin() {
		$result = deactivate_plugins( array( 'akismet/akismet.php' ) );
		if ( null !== $result ) {
			var_dump( $result );
			throw new \LogicException( 'Failed to deactivate plugin!' );
		}
	}
}

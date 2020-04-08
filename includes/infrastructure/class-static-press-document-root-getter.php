<?php
/**
 * Class Static_Press_Document_Root_Getter
 *
 * @package static_press\includes\infrastructure
 */

namespace static_press\includes\infrastructure;

/**
 * Document root getter.
 */
class Static_Press_Document_Root_Getter {
	/**
	 * Gets.
	 */
	public function get() {
		return $this->filter_input_fix( INPUT_SERVER, 'DOCUMENT_ROOT' );
	}

	/**
	 * Attention! This function can't test by PHPUnit since test environment is not working on web server such as Apache.
	 *  
	 * @see https://wordpress.stackexchange.com/questions/110540/is-it-safe-to-use-serverrequest-uri/110541#110541
	 * @see https://stackoverflow.com/questions/25232975/php-filter-inputinput-server-request-method-returns-null/36205923#36205923
	 * @param integer    $type          Type.
	 * @param string     $variable_name Variable name.
	 * @param integer    $filter        Filter.
	 * @param mixed|null $options       Options.
	 */
	public function filter_input_fix( $type, $variable_name, $filter = FILTER_DEFAULT, $options = null ) {
		$check_types = array(
			INPUT_GET,
			INPUT_POST,
			INPUT_COOKIE,
		);
	
		if ( null === $options ) {
			// No idea if this should be here or not
			// Maybe someone could let me know if this should be removed?
			$options = FILTER_NULL_ON_FAILURE;
		}
	
		if ( in_array( $type, $check_types ) || filter_has_var( $type, $variable_name ) ) {
			return filter_input( $type, $variable_name, $filter, $options );
		} elseif ( INPUT_SERVER == $type && isset( $_SERVER[ $variable_name ] ) ) {
			return filter_var( $_SERVER[ $variable_name ], $filter, $options );
		} elseif ( INPUT_ENV == $type && isset( $_ENV[ $variable_name ] ) ) {
			return filter_var( $_ENV[ $variable_name ], $filter, $options );
		} else {
			return null;
		}
	}
}

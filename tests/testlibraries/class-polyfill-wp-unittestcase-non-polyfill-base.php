<?php
/**
 * Class Polyfill_WP_UnitTestCase
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

//phpcs:ignore Generic.Classes.DuplicateClassName.Found -- To polyfill
/**
 * Polyfill WP unit test case base.
 */
abstract class Polyfill_WP_UnitTestCase_Base extends \WP_UnitTestCase {
	/**
	 * Get property value using reflection.
	 *
	 * @param object $object Object to get property from.
	 * @param string $property_name Property name.
	 * @return mixed Property value.
	 * @throws \ReflectionException When reflection fails.
	 */
	protected function getPropertyValue( $object, $property_name ) {
		$reflection = new \ReflectionClass( $object );
		$property   = $reflection->getProperty( $property_name );
		$property->setAccessible( true );
		return $property->getValue( $object );
	}
}

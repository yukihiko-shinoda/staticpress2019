<?php
/**
 * Ajax_Finalyze_Invoker
 *
 * @package static_press\tests\testlibraries\ajax_invokers
 */

namespace static_press\tests\testlibraries\ajax_invokers;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/ajax_invokers/class-ajax-invoker.php';
require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-mock-creator.php';
use static_press\tests\testlibraries\ajax_invokers\Ajax_Invoker;
/**
 * Class Ajax_Finalyze_Invoker
 */
class Ajax_Finalyze_Invoker extends Ajax_Invoker {
	/**
	 * Invokes ajax function.
	 */
	protected function invoke_ajax() {
		$this->static_press->ajax_finalyze( $this->terminator_mock );
	}
}

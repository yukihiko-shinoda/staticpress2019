<?php
/**
 * Class Static_Press_Factory_Static_File_Creator_Test
 *
 * @package static_press\tests\includes\factories
 */

namespace static_press\tests\includes\factories;

require_once STATIC_PRESS_PLUGIN_DIR . 'tests/testlibraries/creators/class-mock-creator.php';
use static_press\includes\factories\Static_Press_Factory_Static_File_Creator;
use static_press\includes\models\Static_Press_Model_Url;
use static_press\includes\repositories\Static_Press_Repository;
use static_press\includes\static_file_creators\Static_Press_Static_File_Creator_Local;
use static_press\includes\static_file_creators\Static_Press_Static_File_Creator_Remote;
use static_press\includes\Static_Press_Url_Collector;
use static_press\tests\testlibraries\creators\Mock_Creator;
/**
 * StaticPress test case.
 */
class Static_Press_Factory_Static_File_Creator_Test extends \WP_UnitTestCase {
	/**
	 * Test step for create().
	 * 
	 * @dataProvider provider_create
	 * @param string $file_type File type.
	 * @param string $expect_class Expect class name.
	 */
	public function test_create( $file_type, $expect_class ) {
		$static_file_creator = Static_Press_Factory_Static_File_Creator::create(
			$file_type,
			'',
			'',
			new Static_Press_Repository(),
			Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' ),
			new Static_Press_Url_Collector( Mock_Creator::create_remote_getter_mock() )
		);
		$this->assertInstanceOf( $expect_class, $static_file_creator );
	}

	/**
	 * Function create() should return appropriate instance.
	 */
	public function provider_create() {
		return array(
			// Reason: This project no longer support PHP 5.5 nor lower.
			array( Static_Press_Model_Url::TYPE_FRONT_PAGE, Static_Press_Static_File_Creator_Remote::class ), // phpcs:ignore
			array( Static_Press_Model_Url::TYPE_SINGLE, Static_Press_Static_File_Creator_Remote::class ), // phpcs:ignore
			array( Static_Press_Model_Url::TYPE_TERM_ARCHIVE, Static_Press_Static_File_Creator_Remote::class ), // phpcs:ignore
			array( Static_Press_Model_Url::TYPE_AUTHOR_ARCHIVE, Static_Press_Static_File_Creator_Remote::class ), // phpcs:ignore
			array( Static_Press_Model_Url::TYPE_SEO_FILES, Static_Press_Static_File_Creator_Remote::class ), // phpcs:ignore
			array( Static_Press_Model_Url::TYPE_OTHER_PAGE, Static_Press_Static_File_Creator_Remote::class ), // phpcs:ignore
			array( Static_Press_Model_Url::TYPE_STATIC_FILE, Static_Press_Static_File_Creator_Local::class ), // phpcs:ignore
			array( Static_Press_Model_Url::TYPE_CONTENT_FILE, Static_Press_Static_File_Creator_Local::class ), // phpcs:ignore
		);
	}
}

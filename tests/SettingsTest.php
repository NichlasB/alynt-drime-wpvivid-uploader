<?php
/**
 * Settings tests.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'sanitize_key' )->alias(
			function ( $value ) {
				return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $value ) );
			}
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_update_can_confirm_persisted_settings() {
		$options  = array();
		$settings = $this->settings_with_options( $options );

		$saved = $settings->update(
			array(
				'api_token' => 'new-token',
			)
		);

		$this->assertTrue( $settings->is_persisted( $saved ) );
	}

	public function test_update_can_detect_failed_persistence() {
		$options  = array();
		$settings = $this->settings_with_options( $options, false );

		$saved = $settings->update(
			array(
				'api_token' => 'new-token',
			)
		);

		$this->assertFalse( $settings->is_persisted( $saved ) );
	}

	/**
	 * Creates settings with mocked option storage.
	 *
	 * @param array<string,mixed> $options Option storage.
	 * @param bool                $persist Whether update_option should persist.
	 * @return Alynt_Drime_WPvivid_Uploader_Settings
	 */
	private function settings_with_options( array &$options, $persist = true ) {
		Functions\when( 'get_option' )->alias(
			function ( $name, $default = array() ) use ( &$options ) {
				return array_key_exists( $name, $options ) ? $options[ $name ] : $default;
			}
		);

		Functions\when( 'update_option' )->alias(
			function ( $name, $value ) use ( &$options, $persist ) {
				if ( $persist ) {
					$options[ $name ] = $value;
				}

				return $persist;
			}
		);

		return new Alynt_Drime_WPvivid_Uploader_Settings();
	}
}

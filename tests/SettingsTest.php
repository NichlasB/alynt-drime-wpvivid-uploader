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
		Functions\when( 'sanitize_email' )->alias(
			function ( $value ) {
				return trim( (string) $value );
			}
		);
		Functions\when( 'is_email' )->alias(
			function ( $value ) {
				return false !== filter_var( $value, FILTER_VALIDATE_EMAIL );
			}
		);
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

	public function test_multipart_chunk_size_is_clamped_to_supported_range() {
		$options  = array();
		$settings = $this->settings_with_options( $options );

		$low = $settings->update(
			array(
				'multipart_chunk_size_mb' => 1,
			)
		);

		$high = $settings->update(
			array(
				'multipart_chunk_size_mb' => 999,
			)
		);

		$this->assertSame( 5, $low['multipart_chunk_size_mb'] );
		$this->assertSame( 64, $high['multipart_chunk_size_mb'] );
	}

	public function test_remote_retention_days_are_clamped_to_supported_range() {
		$options  = array();
		$settings = $this->settings_with_options( $options );

		$low = $settings->update(
			array(
				'remote_retention_enabled' => '1',
				'remote_retention_days'    => -5,
			)
		);

		$high = $settings->update(
			array(
				'remote_retention_days' => 999,
			)
		);

		$this->assertTrue( $low['remote_retention_enabled'] );
		$this->assertSame( 1, $low['remote_retention_days'] );
		$this->assertSame( 365, $high['remote_retention_days'] );
	}

	public function test_failure_email_recipients_are_normalized() {
		$options  = array();
		$settings = $this->settings_with_options( $options );

		$saved = $settings->update(
			array(
				'failure_email_enabled'    => '1',
				'failure_email_recipients' => "admin@example.test, bad-address\nops@example.test\nadmin@example.test",
			)
		);

		$this->assertTrue( $saved['failure_email_enabled'] );
		$this->assertSame( "admin@example.test\nops@example.test", $saved['failure_email_recipients'] );
	}

	public function test_server_cron_expected_is_sanitized_as_boolean() {
		$options  = array();
		$settings = $this->settings_with_options( $options );

		$enabled = $settings->update(
			array(
				'server_cron_expected' => '1',
			)
		);

		$disabled = $settings->update( array() );

		$this->assertTrue( $enabled['server_cron_expected'] );
		$this->assertFalse( $disabled['server_cron_expected'] );
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

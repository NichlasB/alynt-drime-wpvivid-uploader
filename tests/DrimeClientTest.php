<?php
/**
 * Drime client tests.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class DrimeClientTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_upload_part_rejects_unsafe_signed_url() {
		Functions\expect( 'wp_http_validate_url' )
			->once()
			->with( 'http://127.0.0.1/internal' )
			->andReturn( false );

		Functions\expect( 'wp_safe_remote_request' )->never();

		$client = new Alynt_Drime_WPvivid_Uploader_Drime_Client( new Alynt_Drime_WPvivid_Uploader_Settings() );
		$result = $client->upload_part( 'http://127.0.0.1/internal', 'backup-bytes' );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'alynt_drime_unsafe_signed_url', $result->get_error_code() );
	}

	public function test_get_available_name_accepts_live_available_response_key() {
		Functions\when( 'get_option' )->alias(
			function ( $name, $default = array() ) {
				if ( Alynt_Drime_WPvivid_Uploader_Settings::OPTION_NAME !== $name ) {
					return $default;
				}

				return array(
					'api_token'     => 'test-token',
					'workspace_id'  => 0,
					'relative_path' => '/Alynt WPvivid Test Backups/plugin-tester.local',
				);
			}
		);

		Functions\expect( 'wp_json_encode' )->once()->with(
			array(
				'name'         => 'backup.zip',
				'workspaceId'  => 0,
				'relativePath' => '/Alynt WPvivid Test Backups/plugin-tester.local',
			)
		)->andReturn( '{"name":"backup.zip"}' );

		Functions\expect( 'wp_remote_request' )->once()->andReturn( array( 'body' => '{"available":"backup (1).zip","status":"success"}' ) );
		Functions\expect( 'wp_remote_retrieve_response_code' )->once()->andReturn( 200 );
		Functions\expect( 'wp_remote_retrieve_body' )->once()->andReturn( '{"available":"backup (1).zip","status":"success"}' );

		$client = new Alynt_Drime_WPvivid_Uploader_Drime_Client( new Alynt_Drime_WPvivid_Uploader_Settings() );

		$this->assertSame( 'backup (1).zip', $client->get_available_name( 'backup.zip' ) );
	}

	public function test_create_multipart_upload_rejects_missing_upload_id() {
		Functions\when( 'get_option' )->alias(
			function ( $name, $default = array() ) {
				if ( Alynt_Drime_WPvivid_Uploader_Settings::OPTION_NAME !== $name ) {
					return $default;
				}

				return array(
					'api_token'        => 'test-token',
					'workspace_id'     => 0,
					'relative_path'    => '',
					'parent_folder_id' => '',
				);
			}
		);

		Functions\expect( 'wp_json_encode' )->once()->andReturn( '{"filename":"backup.zip"}' );
		Functions\expect( 'wp_remote_request' )->once()->andReturn( array( 'body' => '{"key":"multipart-key","status":"success"}' ) );
		Functions\expect( 'wp_remote_retrieve_response_code' )->once()->andReturn( 200 );
		Functions\expect( 'wp_remote_retrieve_body' )->once()->andReturn( '{"key":"multipart-key","status":"success"}' );

		$client = new Alynt_Drime_WPvivid_Uploader_Drime_Client( new Alynt_Drime_WPvivid_Uploader_Settings() );
		$result = $client->create_multipart_upload( 'backup.zip', 1234, 'zip' );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'alynt_drime_malformed_response', $result->get_error_code() );
	}

	public function test_rate_limit_response_returns_api_error_with_status() {
		Functions\when( 'get_option' )->alias(
			function ( $name, $default = array() ) {
				if ( Alynt_Drime_WPvivid_Uploader_Settings::OPTION_NAME !== $name ) {
					return $default;
				}

				return array(
					'api_token'    => 'test-token',
					'workspace_id' => 0,
				);
			}
		);

		Functions\expect( 'wp_remote_request' )->once()->andReturn( array( 'body' => '{"message":"Too many requests."}' ) );
		Functions\expect( 'wp_remote_retrieve_response_code' )->once()->andReturn( 429 );
		Functions\expect( 'wp_remote_retrieve_body' )->once()->andReturn( '{"message":"Too many requests."}' );

		$client = new Alynt_Drime_WPvivid_Uploader_Drime_Client( new Alynt_Drime_WPvivid_Uploader_Settings() );
		$result = $client->test_connection();

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'alynt_drime_api_error', $result->get_error_code() );
		$this->assertSame( 'Too many requests.', $result->get_error_message() );
		$this->assertSame( array( 'status' => 429 ), $result->get_error_data() );
	}
}

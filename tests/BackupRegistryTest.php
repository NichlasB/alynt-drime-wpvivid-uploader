<?php
/**
 * Backup registry tests.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class BackupRegistryTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( 'sanitize_text_field' )->alias(
			function ( $value ) {
				return is_scalar( $value ) ? trim( (string) $value ) : '';
			}
		);
		Functions\when( 'sanitize_key' )->alias(
			function ( $key ) {
				return strtolower( preg_replace( '/[^a-zA-Z0-9_\\-]/', '', (string) $key ) );
			}
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_mark_failed_preserves_safe_requeue_context() {
		$options  = array(
			Alynt_Drime_WPvivid_Uploader_Backup_Registry::FAILED_OPTION => array(),
		);
		$registry = $this->registry_with_options( $options );

		$this->assertTrue(
			$registry->mark_failed(
				'sig-one',
				'Gateway timeout.',
				array(
					'signature' => 'sig-one',
					'path'      => '/var/www/site/wp-content/wpvividbackups/backup.zip',
					'name'      => 'backup.zip',
					'attempts'  => 3,
					'wpvivid'   => array(
						'backup_id'     => 'abc123',
						'set_signature' => 'set-one',
						'set_files'     => array( 'one.zip', 'two.zip' ),
						'from_list'     => true,
					),
				)
			)
		);

		$record = $options[ Alynt_Drime_WPvivid_Uploader_Backup_Registry::FAILED_OPTION ]['sig-one'];

		$this->assertSame( 'Gateway timeout.', $record['message'] );
		$this->assertSame( 'backup.zip', $record['name'] );
		$this->assertSame( 3, $record['attempts'] );
		$this->assertSame( 'abc123', $record['wpvivid']['backup_id'] );
		$this->assertSame( array( 'one.zip', 'two.zip' ), $record['wpvivid']['set_files'] );
	}

	/**
	 * Creates a registry with mocked option storage.
	 *
	 * @param array<string,mixed> $options Options.
	 * @return Alynt_Drime_WPvivid_Uploader_Backup_Registry
	 */
	private function registry_with_options( array &$options ) {
		Functions\when( 'get_option' )->alias(
			function ( $name, $default = array() ) use ( &$options ) {
				return array_key_exists( $name, $options ) ? $options[ $name ] : $default;
			}
		);

		Functions\when( 'update_option' )->alias(
			function ( $name, $value ) use ( &$options ) {
				$options[ $name ] = $value;
				return true;
			}
		);

		return new Alynt_Drime_WPvivid_Uploader_Backup_Registry();
	}
}

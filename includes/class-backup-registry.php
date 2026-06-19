<?php
/**
 * Uploaded backup registry.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracks uploaded and failed local files.
 */
class Alynt_Drime_WPvivid_Uploader_Backup_Registry {
	const UPLOADED_OPTION = 'alynt_drime_wpvivid_uploaded_files';
	const FAILED_OPTION   = 'alynt_drime_wpvivid_failed_uploads';

	/**
	 * Checks whether a signature is uploaded.
	 *
	 * @param string $signature Signature.
	 * @return bool
	 */
	public function is_uploaded( $signature ) {
		$uploaded = $this->get_uploaded();

		return isset( $uploaded[ $signature ] );
	}

	/**
	 * Marks a file uploaded.
	 *
	 * @param string              $signature Signature.
	 * @param array<string,mixed> $record Record.
	 * @return void
	 */
	public function mark_uploaded( $signature, array $record ) {
		$uploaded               = $this->get_uploaded();
		$uploaded[ $signature ] = array_merge(
			$record,
			array(
				'uploaded_at' => time(),
			)
		);

		update_option( self::UPLOADED_OPTION, $uploaded, false );
		$this->clear_failed( $signature );
	}

	/**
	 * Marks a file failed.
	 *
	 * @param string $signature Signature.
	 * @param string $message Failure message.
	 * @return void
	 */
	public function mark_failed( $signature, $message ) {
		$failed               = $this->get_failed();
		$failed[ $signature ] = array(
			'message'   => sanitize_text_field( $message ),
			'failed_at' => time(),
		);

		update_option( self::FAILED_OPTION, $failed, false );
	}

	/**
	 * Clears a failed record.
	 *
	 * @param string $signature Signature.
	 * @return void
	 */
	public function clear_failed( $signature ) {
		$failed = $this->get_failed();
		unset( $failed[ $signature ] );
		update_option( self::FAILED_OPTION, $failed, false );
	}

	/**
	 * Returns uploaded records.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_uploaded() {
		$uploaded = get_option( self::UPLOADED_OPTION, array() );

		return is_array( $uploaded ) ? $uploaded : array();
	}

	/**
	 * Returns failed records.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_failed() {
		$failed = get_option( self::FAILED_OPTION, array() );

		return is_array( $failed ) ? $failed : array();
	}
}

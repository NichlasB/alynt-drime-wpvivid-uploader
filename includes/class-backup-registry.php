<?php
/**
 * Uploaded backup registry.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 * @since   0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracks uploaded and failed local files.
 *
 * @since 0.1.0
 */
class Alynt_Drime_WPvivid_Uploader_Backup_Registry {
	use Alynt_Drime_WPvivid_Uploader_Option_Storage;

	const UPLOADED_OPTION       = 'alynt_drime_wpvivid_uploaded_files';
	const FAILED_OPTION         = 'alynt_drime_wpvivid_failed_uploads';
	const DRIME_LOCATION_OPTION = 'alynt_drime_wpvivid_drime_locations';

	/**
	 * Checks whether a signature is uploaded.
	 *
	 * @param string $signature Signature.
	 * @return bool
	 *
	 * @since 0.1.0
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
	 * @return bool
	 *
	 * @since 0.1.0
	 */
	public function mark_uploaded( $signature, array $record ) {
		$uploaded               = $this->get_uploaded();
		$uploaded[ $signature ] = array_merge(
			$record,
			array(
				'uploaded_at' => time(),
			)
		);

		if ( ! $this->persist_array_option( self::UPLOADED_OPTION, $uploaded ) ) {
			return false;
		}

		return $this->clear_failed( $signature );
	}

	/**
	 * Marks a file failed.
	 *
	 * @param string $signature Signature.
	 * @param string $message Failure message.
	 * @return bool
	 *
	 * @since 0.1.0
	 */
	public function mark_failed( $signature, $message ) {
		$failed               = $this->get_failed();
		$failed[ $signature ] = array(
			'message'   => sanitize_text_field( $message ),
			'failed_at' => time(),
		);

		return $this->persist_array_option( self::FAILED_OPTION, $failed );
	}

	/**
	 * Clears a failed record.
	 *
	 * @param string $signature Signature.
	 * @return bool
	 *
	 * @since 0.1.0
	 */
	public function clear_failed( $signature ) {
		$failed = $this->get_failed();
		unset( $failed[ $signature ] );

		return $this->persist_array_option( self::FAILED_OPTION, $failed );
	}

	/**
	 * Remembers a resolved Drime parent folder for a relative path.
	 *
	 * @param int    $workspace_id Workspace ID.
	 * @param string $relative_path Relative path.
	 * @param int    $parent_id Parent folder ID.
	 * @return bool
	 *
	 * @since 0.1.0
	 */
	public function remember_drime_location( $workspace_id, $relative_path, $parent_id ) {
		$workspace_id  = absint( $workspace_id );
		$relative_path = $this->normalize_relative_path( $relative_path );
		$parent_id     = absint( $parent_id );

		if ( '' === $relative_path || $parent_id <= 0 ) {
			return true;
		}

		$locations = $this->get_drime_locations();
		$locations[ $this->location_key( $workspace_id, $relative_path ) ] = array(
			'workspace_id'  => $workspace_id,
			'relative_path' => $relative_path,
			'parent_id'     => $parent_id,
			'updated_at'    => time(),
		);

		return $this->persist_array_option( self::DRIME_LOCATION_OPTION, $locations );
	}

	/**
	 * Gets a remembered Drime parent folder for a relative path.
	 *
	 * @param int    $workspace_id Workspace ID.
	 * @param string $relative_path Relative path.
	 * @return int
	 *
	 * @since 0.1.0
	 */
	public function get_drime_parent_id( $workspace_id, $relative_path ) {
		$workspace_id  = absint( $workspace_id );
		$relative_path = $this->normalize_relative_path( $relative_path );

		if ( '' === $relative_path ) {
			return 0;
		}

		$locations = $this->get_drime_locations();
		$key       = $this->location_key( $workspace_id, $relative_path );

		if ( empty( $locations[ $key ] ) || ! is_array( $locations[ $key ] ) ) {
			return 0;
		}

		return empty( $locations[ $key ]['parent_id'] ) ? 0 : absint( $locations[ $key ]['parent_id'] );
	}

	/**
	 * Returns uploaded records.
	 *
	 * @return array<string,array<string,mixed>>
	 *
	 * @since 0.1.0
	 */
	public function get_uploaded() {
		return $this->get_array_option( self::UPLOADED_OPTION );
	}

	/**
	 * Returns failed records.
	 *
	 * @return array<string,array<string,mixed>>
	 *
	 * @since 0.1.0
	 */
	public function get_failed() {
		return $this->get_array_option( self::FAILED_OPTION );
	}

	/**
	 * Returns Drime relative path location records.
	 *
	 * @return array<string,array<string,mixed>>
	 *
	 * @since 0.1.0
	 */
	public function get_drime_locations() {
		return $this->get_array_option( self::DRIME_LOCATION_OPTION );
	}

	/**
	 * Normalizes a Drime relative path.
	 *
	 * @param string $relative_path Relative path.
	 * @return string
	 */
	private function normalize_relative_path( $relative_path ) {
		$relative_path = trim( str_replace( '\\', '/', (string) $relative_path ) );
		$relative_path = '/' . trim( $relative_path, '/' );

		return '/' === $relative_path ? '' : $relative_path;
	}

	/**
	 * Builds the option key for a Drime relative path.
	 *
	 * @param int    $workspace_id Workspace ID.
	 * @param string $relative_path Relative path.
	 * @return string
	 */
	private function location_key( $workspace_id, $relative_path ) {
		return hash( 'sha256', absint( $workspace_id ) . '|' . $relative_path );
	}
}

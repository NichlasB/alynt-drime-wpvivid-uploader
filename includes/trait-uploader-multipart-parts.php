<?php
/**
 * Uploader multipart part transfer helpers.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 * @since   0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Uploader multipart part transfer helpers.
 *
 * @since 0.1.0
 */
trait Alynt_Drime_WPvivid_Uploader_Uploader_Multipart_Parts {
	/**
	 * Uploads missing multipart parts.
	 *
	 * @param string                                       $path Path.
	 * @param string                                       $remote_name Remote name.
	 * @param array<string,mixed>                          $item Queue item.
	 * @param array{key:string,upload_id:string,total:int} $session Multipart session.
	 * @param array<int,array<string,mixed>>               $parts Completed parts.
	 * @return array<int,array<string,mixed>>|WP_Error
	 */
	private function upload_multipart_parts( $path, $remote_name, array $item, array $session, array $parts ) {
		$handle = fopen( $path, 'rb' );
		if ( false === $handle ) {
			return new WP_Error( 'alynt_drime_file_open_failed', __( 'Could not open the backup file for upload.', 'alynt-drime-wpvivid-uploader' ) );
		}

		for ( $part_number = 1; $part_number <= $session['total']; $part_number++ ) {
			if ( isset( $parts[ $part_number ] ) ) {
				if ( ! $this->store_active_upload_state( $path, $remote_name, $session['key'], $session['upload_id'], (string) $item['signature'], $parts, $session['total'] ) ) {
					fclose( $handle );
					return $this->state_persistence_error();
				}

				continue;
			}

			$part = $this->upload_multipart_part( $handle, $session['key'], $session['upload_id'], $part_number );
			if ( is_wp_error( $part ) ) {
				fclose( $handle );
				return $part;
			}

			$parts[ $part_number ] = $part;
			if ( ! $this->store_active_upload_state( $path, $remote_name, $session['key'], $session['upload_id'], (string) $item['signature'], $parts, $session['total'] ) ) {
				fclose( $handle );
				return $this->state_persistence_error();
			}
		}

		fclose( $handle );
		ksort( $parts );

		return $parts;
	}

	/**
	 * Uploads one multipart part.
	 *
	 * @param resource $handle File handle.
	 * @param string   $key Multipart key.
	 * @param string   $upload_id Multipart upload ID.
	 * @param int      $part_number Part number.
	 * @return array{PartNumber:int,ETag:string}|WP_Error
	 */
	private function upload_multipart_part( $handle, $key, $upload_id, $part_number ) {
		$sign_response = $this->client->sign_part_urls( $key, $upload_id, array( $part_number ) );
		if ( is_wp_error( $sign_response ) ) {
			return $sign_response;
		}

		$url = $this->extract_signed_url( $sign_response, $part_number );
		if ( is_wp_error( $url ) ) {
			return $url;
		}

		$data = $this->read_multipart_part( $handle, $part_number );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$etag = $this->client->upload_part( $url, $data );
		if ( is_wp_error( $etag ) ) {
			return $etag;
		}

		return array(
			'PartNumber' => $part_number,
			'ETag'       => $etag,
		);
	}

	/**
	 * Reads one multipart part from disk.
	 *
	 * @param resource $handle File handle.
	 * @param int      $part_number Part number.
	 * @return string|WP_Error
	 */
	private function read_multipart_part( $handle, $part_number ) {
		if ( 0 !== fseek( $handle, ( $part_number - 1 ) * Alynt_Drime_WPvivid_Uploader_Drime_Client::MULTIPART_SIZE ) ) {
			return new WP_Error( 'alynt_drime_file_seek_failed', __( 'Could not seek to the next backup chunk.', 'alynt-drime-wpvivid-uploader' ) );
		}

		$data = fread( $handle, Alynt_Drime_WPvivid_Uploader_Drime_Client::MULTIPART_SIZE );

		return false === $data ? new WP_Error( 'alynt_drime_file_read_failed', __( 'Could not read the next backup chunk.', 'alynt-drime-wpvivid-uploader' ) ) : $data;
	}
}

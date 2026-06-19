<?php
/**
 * Upload worker.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processes queued uploads one at a time.
 */
class Alynt_Drime_WPvivid_Uploader_Uploader {
	/**
	 * Settings.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Settings
	 */
	private $settings;

	/**
	 * Drime client.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Drime_Client
	 */
	private $client;

	/**
	 * Queue.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Queue
	 */
	private $queue;

	/**
	 * Registry.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Backup_Registry
	 */
	private $registry;

	/**
	 * Logger.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param Alynt_Drime_WPvivid_Uploader_Settings        $settings Settings.
	 * @param Alynt_Drime_WPvivid_Uploader_Drime_Client    $client Client.
	 * @param Alynt_Drime_WPvivid_Uploader_Queue           $queue Queue.
	 * @param Alynt_Drime_WPvivid_Uploader_Backup_Registry $registry Registry.
	 * @param Alynt_Drime_WPvivid_Uploader_Logger          $logger Logger.
	 */
	public function __construct( Alynt_Drime_WPvivid_Uploader_Settings $settings, Alynt_Drime_WPvivid_Uploader_Drime_Client $client, Alynt_Drime_WPvivid_Uploader_Queue $queue, Alynt_Drime_WPvivid_Uploader_Backup_Registry $registry, Alynt_Drime_WPvivid_Uploader_Logger $logger ) {
		$this->settings = $settings;
		$this->client   = $client;
		$this->queue    = $queue;
		$this->registry = $registry;
		$this->logger   = $logger;
	}

	/**
	 * Uploads the next queued item.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function upload_next() {
		$item = $this->queue->next();
		if ( null === $item ) {
			return new WP_Error( 'alynt_drime_queue_empty', __( 'There are no queued backups to upload.', 'alynt-drime-wpvivid-uploader' ) );
		}

		$result = $this->upload_item( $item );

		if ( is_wp_error( $result ) ) {
			$this->queue->increment_attempts( (string) $item['signature'] );
			$this->registry->mark_failed( (string) $item['signature'], $result->get_error_message() );
			$this->logger->event( 'upload', 'error', 'upload_failed', 'Upload failed.', array( 'file' => basename( (string) $item['path'] ), 'reason' => $result->get_error_message() ) );
			return $result;
		}

		$this->registry->mark_uploaded( (string) $item['signature'], $result );
		$this->queue->remove( (string) $item['signature'] );
		$this->queue->set_active( null );
		$this->logger->event( 'upload', 'info', 'upload_completed', 'Upload completed.', array( 'file' => basename( (string) $item['path'] ) ) );

		return $result;
	}

	/**
	 * Uploads one queued item.
	 *
	 * @param array<string,mixed> $item Item.
	 * @return array<string,mixed>|WP_Error
	 */
	private function upload_item( array $item ) {
		$path = isset( $item['path'] ) ? (string) $item['path'] : '';
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			return new WP_Error( 'alynt_drime_file_missing', __( 'The queued backup file is no longer readable.', 'alynt-drime-wpvivid-uploader' ) );
		}

		$settings    = $this->settings->get();
		$size        = filesize( $path );
		$remote_name = basename( $path );

		if ( false === $size || $size <= 0 ) {
			return new WP_Error( 'alynt_drime_empty_file', __( 'The queued backup file is empty.', 'alynt-drime-wpvivid-uploader' ) );
		}

		$remote_name = $this->resolve_duplicate_mode( $remote_name, (int) $size, $settings );
		if ( is_wp_error( $remote_name ) ) {
			return $remote_name;
		}

		if ( false === $remote_name ) {
			return new WP_Error( 'alynt_drime_duplicate_skipped', __( 'A file with this name already exists in Drime, so the upload was skipped.', 'alynt-drime-wpvivid-uploader' ) );
		}

		if ( $size < Alynt_Drime_WPvivid_Uploader_Drime_Client::MULTIPART_SIZE ) {
			$response = $this->client->simple_upload( $path, $remote_name );
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			return array(
				'path'        => $path,
				'remote_name' => $remote_name,
				'size'        => $size,
				'drime'       => $response,
			);
		}

		return $this->multipart_upload( $path, $remote_name, (int) $size );
	}

	/**
	 * Handles duplicate mode.
	 *
	 * @param string              $remote_name Remote name.
	 * @param int                 $size Size.
	 * @param array<string,mixed> $settings Settings.
	 * @return string|false|WP_Error
	 */
	private function resolve_duplicate_mode( $remote_name, $size, array $settings ) {
		$relative_path = '' !== $settings['relative_path'] ? $settings['relative_path'] : '/';
		$validation    = $this->client->validate_upload(
			array(
				array(
					'name'         => $remote_name,
					'size'         => $size,
					'relativePath' => $relative_path,
				),
			)
		);

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$duplicates = isset( $validation['duplicates'] ) && is_array( $validation['duplicates'] ) ? $validation['duplicates'] : array();
		if ( empty( $duplicates ) ) {
			return $remote_name;
		}

		if ( 'skip' === $settings['duplicate_mode'] ) {
			return false;
		}

		return $this->client->get_available_name( $remote_name );
	}

	/**
	 * Uploads a file through Drime multipart flow.
	 *
	 * @param string $path Path.
	 * @param string $remote_name Remote name.
	 * @param int    $size Size.
	 * @return array<string,mixed>|WP_Error
	 */
	private function multipart_upload( $path, $remote_name, $size ) {
		$extension = pathinfo( $remote_name, PATHINFO_EXTENSION );
		$extension = '' === $extension ? 'zip' : strtolower( $extension );
		$this->logger->event( 'upload', 'info', 'multipart_started', 'Multipart upload started.', array( 'file' => $remote_name, 'size' => $size ) );
		$created   = $this->client->create_multipart_upload( $remote_name, $size, $extension );

		if ( is_wp_error( $created ) ) {
			return $created;
		}

		if ( empty( $created['key'] ) || empty( $created['uploadId'] ) ) {
			return new WP_Error( 'alynt_drime_bad_multipart_create', __( 'Drime did not return a multipart upload ID.', 'alynt-drime-wpvivid-uploader' ) );
		}

		$key        = (string) $created['key'];
		$upload_id  = (string) $created['uploadId'];
		$total      = (int) ceil( $size / Alynt_Drime_WPvivid_Uploader_Drime_Client::MULTIPART_SIZE );
		$parts      = array();
		$handle     = fopen( $path, 'rb' );

		if ( false === $handle ) {
			return new WP_Error( 'alynt_drime_file_open_failed', __( 'Could not open the backup file for upload.', 'alynt-drime-wpvivid-uploader' ) );
		}

		for ( $part_number = 1; $part_number <= $total; $part_number++ ) {
			$sign_response = $this->client->sign_part_urls( $key, $upload_id, array( $part_number ) );
			if ( is_wp_error( $sign_response ) ) {
				fclose( $handle );
				return $sign_response;
			}

			$url = $this->extract_signed_url( $sign_response, $part_number );
			if ( is_wp_error( $url ) ) {
				fclose( $handle );
				return $url;
			}

			$data = fread( $handle, Alynt_Drime_WPvivid_Uploader_Drime_Client::MULTIPART_SIZE );
			if ( false === $data ) {
				fclose( $handle );
				return new WP_Error( 'alynt_drime_file_read_failed', __( 'Could not read the next backup chunk.', 'alynt-drime-wpvivid-uploader' ) );
			}

			$etag = $this->client->upload_part( $url, $data );
			if ( is_wp_error( $etag ) ) {
				fclose( $handle );
				return $etag;
			}

			$parts[] = array(
				'PartNumber' => $part_number,
				'ETag'       => $etag,
			);

			$this->queue->set_active(
				array(
					'local_file'      => $path,
					'remote_name'     => $remote_name,
					'key'             => $key,
					'upload_id'       => $upload_id,
					'completed_parts' => count( $parts ),
					'total_parts'     => $total,
					'updated_at'      => time(),
				)
			);
		}

		fclose( $handle );

		$completed = $this->client->complete_multipart_upload( $key, $upload_id, $parts );
		if ( is_wp_error( $completed ) ) {
			return $completed;
		}

		$this->logger->event( 'upload', 'info', 'multipart_completed', 'Multipart upload completed.', array( 'file' => $remote_name, 'parts' => count( $parts ) ) );

		$entry = $this->client->create_s3_entry( $key, $remote_name, $size, $extension );
		if ( is_wp_error( $entry ) ) {
			return $entry;
		}

		return array(
			'path'        => $path,
			'remote_name' => $remote_name,
			'size'        => $size,
			'key'         => $key,
			'drime'       => $entry,
		);
	}

	/**
	 * Extracts a signed URL for a part.
	 *
	 * @param array<string,mixed> $sign_response Sign response.
	 * @param int                 $part_number Part number.
	 * @return string|WP_Error
	 */
	private function extract_signed_url( array $sign_response, $part_number ) {
		if ( empty( $sign_response['urls'] ) || ! is_array( $sign_response['urls'] ) ) {
			return new WP_Error( 'alynt_drime_missing_signed_url', __( 'Drime did not return a signed upload URL.', 'alynt-drime-wpvivid-uploader' ) );
		}

		foreach ( $sign_response['urls'] as $signed ) {
			if ( is_array( $signed ) && isset( $signed['partNumber'], $signed['url'] ) && (int) $signed['partNumber'] === (int) $part_number ) {
				return (string) $signed['url'];
			}
		}

		return new WP_Error( 'alynt_drime_missing_part_url', __( 'Drime did not return the expected part URL.', 'alynt-drime-wpvivid-uploader' ) );
	}
}

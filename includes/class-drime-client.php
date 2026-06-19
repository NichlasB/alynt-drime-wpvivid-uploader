<?php
/**
 * Drime API client.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Isolates Drime API calls.
 */
class Alynt_Drime_WPvivid_Uploader_Drime_Client {
	const BASE_URL        = 'https://app.drime.cloud/api/v1';
	const MULTIPART_SIZE  = 5242880;

	/**
	 * Settings.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Settings
	 */
	private $settings;

	/**
	 * Logger.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Logger|null
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param Alynt_Drime_WPvivid_Uploader_Settings $settings Settings.
	 */
	public function __construct( Alynt_Drime_WPvivid_Uploader_Settings $settings, ?Alynt_Drime_WPvivid_Uploader_Logger $logger = null ) {
		$this->settings = $settings;
		$this->logger   = $logger;
	}

	/**
	 * Tests the configured token.
	 *
	 * @return true|WP_Error
	 */
	public function test_connection() {
		$settings = $this->settings->get();

		return $this->request( 'GET', '/drive/file-entries?workspaceId=' . absint( $settings['workspace_id'] ) . '&perPage=1' );
	}

	/**
	 * Validates duplicates.
	 *
	 * @param array<int,array<string,mixed>> $files Files.
	 * @return array<string,mixed>|WP_Error
	 */
	public function validate_upload( array $files ) {
		$settings = $this->settings->get();

		return $this->request(
			'POST',
			'/uploads/validate?workspaceId=' . absint( $settings['workspace_id'] ),
			array(
				'files' => $files,
			)
		);
	}

	/**
	 * Gets an available filename.
	 *
	 * @param string $name Name.
	 * @return string|WP_Error
	 */
	public function get_available_name( $name ) {
		$settings = $this->settings->get();
		$response = $this->request(
			'POST',
			'/entry/getAvailableName',
			array(
				'name'        => $name,
				'parentId'    => $this->parent_id_or_null( $settings ),
				'workspaceId' => absint( $settings['workspace_id'] ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return isset( $response['name'] ) ? (string) $response['name'] : $name;
	}

	/**
	 * Creates a folder.
	 *
	 * @param string $name Folder name.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_folder( $name ) {
		$settings = $this->settings->get();

		return $this->request(
			'POST',
			'/folders?workspaceId=' . absint( $settings['workspace_id'] ),
			array(
				'name'     => $name,
				'parentId' => $this->parent_id_or_null( $settings ),
			)
		);
	}

	/**
	 * Directly uploads a small file through Drime's /uploads endpoint.
	 *
	 * @param string $path File path.
	 * @param string $remote_name Remote display name.
	 * @return array<string,mixed>|WP_Error
	 */
	public function simple_upload( $path, $remote_name ) {
		if ( ! function_exists( 'curl_init' ) || ! function_exists( 'curl_file_create' ) ) {
			return new WP_Error( 'alynt_drime_no_curl', __( 'The PHP cURL extension is required for direct small-file uploads.', 'alynt-drime-wpvivid-uploader' ) );
		}

		$settings = $this->settings->get();
		$token    = trim( (string) $settings['api_token'] );

		if ( '' === $token ) {
			return new WP_Error( 'alynt_drime_missing_token', __( 'Add a Drime API token before uploading.', 'alynt-drime-wpvivid-uploader' ) );
		}

		$ch = curl_init( self::BASE_URL . '/uploads' );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Authorization: Bearer ' . $token ) );
		curl_setopt(
			$ch,
			CURLOPT_POSTFIELDS,
			array(
				'file'        => curl_file_create( $path, 'application/zip', $remote_name ),
				'parentId'    => $this->parent_id_or_empty( $settings ),
				'workspaceId' => (string) absint( $settings['workspace_id'] ),
			)
		);

		$raw  = curl_exec( $ch );
		$code = (int) curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
		$error = curl_error( $ch );
		curl_close( $ch );

		if ( false === $raw ) {
			return new WP_Error( 'alynt_drime_upload_failed', $error ? $error : __( 'The direct upload request failed.', 'alynt-drime-wpvivid-uploader' ) );
		}

		$decoded = json_decode( (string) $raw, true );
		if ( $code < 200 || $code >= 300 || ! is_array( $decoded ) ) {
			return new WP_Error( 'alynt_drime_upload_failed', __( 'Drime rejected the direct upload request.', 'alynt-drime-wpvivid-uploader' ) );
		}

		return $decoded;
	}

	/**
	 * Creates a multipart upload.
	 *
	 * @param string $filename Filename.
	 * @param int    $size Size.
	 * @param string $extension Extension.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_multipart_upload( $filename, $size, $extension ) {
		$settings = $this->settings->get();
		$body     = array(
			'filename'    => $filename,
			'mime'        => 'application/zip',
			'size'        => absint( $size ),
			'extension'   => $extension,
			'workspaceId' => absint( $settings['workspace_id'] ),
		);

		if ( '' !== $settings['relative_path'] ) {
			$body['relativePath'] = $settings['relative_path'];
		} else {
			$body['parentId'] = $this->parent_id_or_null( $settings );
		}

		return $this->request( 'POST', '/s3/multipart/create', $body );
	}

	/**
	 * Signs part URLs.
	 *
	 * @param string     $key Key.
	 * @param string     $upload_id Upload ID.
	 * @param array<int> $part_numbers Part numbers.
	 * @return array<string,mixed>|WP_Error
	 */
	public function sign_part_urls( $key, $upload_id, array $part_numbers ) {
		return $this->request(
			'POST',
			'/s3/multipart/batch-sign-part-urls',
			array(
				'key'         => $key,
				'uploadId'    => $upload_id,
				'partNumbers' => array_values( array_map( 'absint', $part_numbers ) ),
			)
		);
	}

	/**
	 * Uploads one part to a presigned URL.
	 *
	 * @param string $url URL.
	 * @param string $data Part body.
	 * @return string|WP_Error
	 */
	public function upload_part( $url, $data ) {
		$response = wp_remote_request(
			$url,
			array(
				'method'  => 'PUT',
				'timeout' => 180,
				'headers' => array(
					'Content-Type' => 'application/octet-stream',
				),
				'body'    => $data,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error( 'alynt_drime_part_failed', __( 'A Drime upload part failed.', 'alynt-drime-wpvivid-uploader' ) );
		}

		$etag = wp_remote_retrieve_header( $response, 'etag' );
		if ( '' === $etag ) {
			return new WP_Error( 'alynt_drime_missing_etag', __( 'Drime did not return an ETag for the uploaded part.', 'alynt-drime-wpvivid-uploader' ) );
		}

		$etag = (string) $etag;
		if ( '"' !== substr( $etag, 0, 1 ) ) {
			$etag = '"' . $etag . '"';
		}

		return $etag;
	}

	/**
	 * Completes a multipart upload.
	 *
	 * @param string              $key Key.
	 * @param string              $upload_id Upload ID.
	 * @param array<int,array>    $parts Parts.
	 * @return array<string,mixed>|WP_Error
	 */
	public function complete_multipart_upload( $key, $upload_id, array $parts ) {
		return $this->request(
			'POST',
			'/s3/multipart/complete',
			array(
				'key'      => $key,
				'uploadId' => $upload_id,
				'parts'    => $parts,
			)
		);
	}

	/**
	 * Registers an uploaded S3 object as a Drime entry.
	 *
	 * @param string $key Key.
	 * @param string $client_name Display name.
	 * @param int    $size Size.
	 * @param string $extension Extension.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_s3_entry( $key, $client_name, $size, $extension ) {
		$settings = $this->settings->get();
		$body     = array(
			'filename'        => basename( $key ),
			'size'            => absint( $size ),
			'clientName'      => $client_name,
			'clientMime'      => 'application/zip',
			'clientExtension' => $extension,
			'workspaceId'     => absint( $settings['workspace_id'] ),
		);

		if ( '' !== $settings['relative_path'] ) {
			$body['relativePath'] = $settings['relative_path'];
		} else {
			$body['parentId'] = $this->parent_id_or_null( $settings );
		}

		return $this->request( 'POST', '/s3/entries', $body );
	}

	/**
	 * Gets uploaded parts for resuming.
	 *
	 * @param string $key Key.
	 * @param string $upload_id Upload ID.
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_uploaded_parts( $key, $upload_id ) {
		return $this->request(
			'POST',
			'/s3/multipart/get-uploaded-parts',
			array(
				'key'      => $key,
				'uploadId' => $upload_id,
			)
		);
	}

	/**
	 * Sends a JSON request to Drime.
	 *
	 * @param string                    $method Method.
	 * @param string                    $path Path.
	 * @param array<string,mixed>|null  $body Body.
	 * @return array<string,mixed>|true|WP_Error
	 */
	private function request( $method, $path, $body = null ) {
		$settings = $this->settings->get();
		$token    = trim( (string) $settings['api_token'] );

		if ( '' === $token ) {
			return new WP_Error( 'alynt_drime_missing_token', __( 'Add a Drime API token before connecting.', 'alynt-drime-wpvivid-uploader' ) );
		}

		$args = array(
			'method'  => $method,
			'timeout' => 45,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
		);

		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( self::BASE_URL . $path, $args );
		if ( is_wp_error( $response ) ) {
			$this->diagnostic( 'error', 'request_failed', 'Drime request failed.', array( 'endpoint' => $path, 'reason' => $response->get_error_message() ) );
			return $response;
		}

		$code    = wp_remote_retrieve_response_code( $response );
		$raw     = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $raw, true );

		if ( $code < 200 || $code >= 300 ) {
			$message = is_array( $decoded ) && ! empty( $decoded['message'] ) ? (string) $decoded['message'] : __( 'Drime returned an error response.', 'alynt-drime-wpvivid-uploader' );
			$this->diagnostic( 'error', 'api_error', 'Drime returned an error response.', array( 'status' => $code, 'endpoint' => $path, 'message' => $message ) );
			return new WP_Error( 'alynt_drime_api_error', $message, array( 'status' => $code ) );
		}

		if ( '' === trim( $raw ) ) {
			return true;
		}

		return is_array( $decoded ) ? $decoded : true;
	}

	/**
	 * Writes a Drime client diagnostic event.
	 *
	 * @param string              $level Level.
	 * @param string              $code Event code.
	 * @param string              $message Message.
	 * @param array<string,mixed> $context Context.
	 * @return void
	 */
	private function diagnostic( $level, $code, $message, array $context = array() ) {
		if ( $this->logger instanceof Alynt_Drime_WPvivid_Uploader_Logger ) {
			$this->logger->event( 'external_api', $level, $code, $message, $context );
		}
	}

	/**
	 * Returns parent ID or null.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return int|null
	 */
	private function parent_id_or_null( array $settings ) {
		return '' === (string) $settings['parent_folder_id'] ? null : absint( $settings['parent_folder_id'] );
	}

	/**
	 * Returns parent ID or an empty string for multipart form upload.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return string
	 */
	private function parent_id_or_empty( array $settings ) {
		return '' === (string) $settings['parent_folder_id'] ? '' : (string) absint( $settings['parent_folder_id'] );
	}
}

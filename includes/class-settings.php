<?php
/**
 * Settings storage and validation.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 * @since   0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin settings.
 *
 * @since 0.1.0
 */
class Alynt_Drime_WPvivid_Uploader_Settings {
	const OPTION_NAME                     = 'alynt_drime_wpvivid_settings';
	const MIN_MULTIPART_CHUNK_SIZE_MB     = 5;
	const MAX_MULTIPART_CHUNK_SIZE_MB     = 64;
	const DEFAULT_MULTIPART_CHUNK_SIZE_MB = 32;
	const MIN_REMOTE_RETENTION_DAYS       = 1;
	const MAX_REMOTE_RETENTION_DAYS       = 365;
	const DEFAULT_REMOTE_RETENTION_DAYS   = 60;

	/**
	 * Returns default settings.
	 *
	 * @return array<string,mixed>
	 *
	 * @since 0.1.0
	 */
	public static function defaults() {
		return array(
			'api_token'                 => '',
			'workspace_id'              => 0,
			'parent_folder_id'          => '',
			'relative_path'             => '',
			'backup_path_override'      => '',
			'duplicate_mode'            => 'skip',
			'auto_scan_enabled'         => false,
			'scan_interval'             => 'fifteen_minutes',
			'min_file_age_seconds'      => 900,
			'multipart_chunk_size_mb'   => self::DEFAULT_MULTIPART_CHUNK_SIZE_MB,
			'delete_local_after_upload' => false,
			'remote_retention_enabled'  => false,
			'remote_retention_days'     => self::DEFAULT_REMOTE_RETENTION_DAYS,
			'max_retries'               => 3,
			'diagnostics_enabled'       => false,
			'diagnostics_min_level'     => 'warning',
			'diagnostics_retention'     => 100,
		);
	}

	/**
	 * Ensures the settings option exists with autoload disabled.
	 *
	 * @return void
	 *
	 * @since 0.1.0
	 */
	public static function maybe_install() {
		if ( false === get_option( self::OPTION_NAME, false ) ) {
			add_option( self::OPTION_NAME, self::defaults(), '', false );
		}
	}

	/**
	 * Returns merged settings.
	 *
	 * @return array<string,mixed>
	 *
	 * @since 0.1.0
	 */
	public function get() {
		$settings = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return array_merge( self::defaults(), $settings );
	}

	/**
	 * Updates settings after sanitization.
	 *
	 * @param array<string,mixed> $raw Raw settings.
	 * @return array<string,mixed>
	 *
	 * @since 0.1.0
	 */
	public function update( array $raw ) {
		$sanitized = $this->sanitize( $raw, $this->get() );
		update_option( self::OPTION_NAME, $sanitized, false );

		return $sanitized;
	}

	/**
	 * Returns whether a settings array matches the persisted option.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return bool
	 *
	 * @since 0.1.0
	 */
	public function is_persisted( array $settings ) {
		return $settings === $this->get();
	}

	/**
	 * Sanitizes settings.
	 *
	 * @param array<string,mixed> $raw Raw settings.
	 * @param array<string,mixed> $current Current settings.
	 * @return array<string,mixed>
	 *
	 * @since 0.1.0
	 */
	public function sanitize( array $raw, array $current ) {
		$settings = self::defaults();

		$incoming_token = isset( $raw['api_token'] ) ? trim( (string) wp_unslash( $raw['api_token'] ) ) : '';
		if ( '' === $incoming_token || '************' === $incoming_token ) {
			$settings['api_token'] = isset( $current['api_token'] ) ? (string) $current['api_token'] : '';
		} else {
			$settings['api_token'] = sanitize_text_field( $incoming_token );
		}

		$settings['workspace_id'] = isset( $raw['workspace_id'] ) ? max( 0, absint( $raw['workspace_id'] ) ) : 0;

		if ( isset( $raw['parent_folder_id'] ) ) {
			$parent_folder_id             = trim( (string) wp_unslash( $raw['parent_folder_id'] ) );
			$settings['parent_folder_id'] = '' === $parent_folder_id ? '' : (string) absint( $parent_folder_id );
		}

		$settings['relative_path'] = isset( $raw['relative_path'] ) ? $this->sanitize_relative_path( (string) wp_unslash( $raw['relative_path'] ) ) : '';

		if ( isset( $raw['backup_path_override'] ) ) {
			$settings['backup_path_override'] = sanitize_text_field( wp_unslash( $raw['backup_path_override'] ) );
		}

		$duplicate_mode             = isset( $raw['duplicate_mode'] ) ? sanitize_key( wp_unslash( $raw['duplicate_mode'] ) ) : 'skip';
		$settings['duplicate_mode'] = in_array( $duplicate_mode, array( 'skip', 'rename' ), true ) ? $duplicate_mode : 'skip';

		$settings['auto_scan_enabled']         = ! empty( $raw['auto_scan_enabled'] );
		$settings['scan_interval']             = 'fifteen_minutes';
		$settings['min_file_age_seconds']      = isset( $raw['min_file_age_seconds'] ) ? max( 60, absint( $raw['min_file_age_seconds'] ) ) : 900;
		$settings['multipart_chunk_size_mb']   = isset( $raw['multipart_chunk_size_mb'] ) ? max( self::MIN_MULTIPART_CHUNK_SIZE_MB, min( self::MAX_MULTIPART_CHUNK_SIZE_MB, absint( $raw['multipart_chunk_size_mb'] ) ) ) : self::DEFAULT_MULTIPART_CHUNK_SIZE_MB;
		$settings['delete_local_after_upload'] = ! empty( $raw['delete_local_after_upload'] );
		$settings['remote_retention_enabled']  = ! empty( $raw['remote_retention_enabled'] );
		$settings['remote_retention_days']     = isset( $raw['remote_retention_days'] ) ? $this->clamp_remote_retention_days( $raw['remote_retention_days'] ) : self::DEFAULT_REMOTE_RETENTION_DAYS;
		$settings['max_retries']               = isset( $raw['max_retries'] ) ? max( 0, min( 10, absint( $raw['max_retries'] ) ) ) : 3;

		$settings['diagnostics_enabled']   = ! empty( $raw['diagnostics_enabled'] );
		$settings['diagnostics_min_level'] = $this->sanitize_level( isset( $raw['diagnostics_min_level'] ) ? (string) wp_unslash( $raw['diagnostics_min_level'] ) : 'warning' );
		$settings['diagnostics_retention'] = isset( $raw['diagnostics_retention'] ) ? max( 25, min( 500, absint( $raw['diagnostics_retention'] ) ) ) : 100;

		return $settings;
	}

	/**
	 * Returns whether a token is configured.
	 *
	 * @return bool
	 *
	 * @since 0.1.0
	 */
	public function has_token() {
		$settings = $this->get();

		return '' !== trim( (string) $settings['api_token'] );
	}

	/**
	 * Returns severity levels in ascending order.
	 *
	 * @return array<string,int>
	 *
	 * @since 0.1.0
	 */
	public static function severity_levels() {
		return array(
			'debug'    => 100,
			'info'     => 200,
			'warning'  => 300,
			'error'    => 400,
			'critical' => 500,
		);
	}

	/**
	 * Normalizes an optional Drime relative path.
	 *
	 * @param string $path Raw path.
	 * @return string
	 */
	private function sanitize_relative_path( $path ) {
		$path = sanitize_text_field( $path );
		$path = str_replace( '\\', '/', $path );
		$path = preg_replace( '#/+#', '/', $path );
		$path = trim( (string) $path );

		if ( '' === $path ) {
			return '';
		}

		$path = '/' . trim( $path, '/' );

		return false === strpos( $path, '..' ) ? $path : '';
	}

	/**
	 * Clamps remote retention age to the supported day range.
	 *
	 * @param mixed $days Raw day count.
	 * @return int
	 */
	private function clamp_remote_retention_days( $days ) {
		return max( self::MIN_REMOTE_RETENTION_DAYS, min( self::MAX_REMOTE_RETENTION_DAYS, (int) $days ) );
	}

	/**
	 * Sanitizes a severity level.
	 *
	 * @param string $level Level.
	 * @return string
	 */
	private function sanitize_level( $level ) {
		$level = sanitize_key( $level );

		return array_key_exists( $level, self::severity_levels() ) ? $level : 'warning';
	}
}

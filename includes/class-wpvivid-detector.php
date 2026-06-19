<?php
/**
 * WPvivid backup path detection.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detects the WPvivid local backup directory defensively.
 */
class Alynt_Drime_WPvivid_Uploader_WPvivid_Detector {
	/**
	 * Returns the best known backup directory.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return string
	 */
	public function get_backup_dir( array $settings ) {
		if ( ! empty( $settings['backup_path_override'] ) ) {
			return untrailingslashit( (string) $settings['backup_path_override'] );
		}

		$option_candidates = array(
			get_option( 'wpvivid_common_setting', array() ),
			get_option( 'wpvivid_local_setting', array() ),
			get_option( 'wpvivid_backup_remote_options', array() ),
		);

		foreach ( $option_candidates as $candidate ) {
			$path = $this->find_path_in_value( $candidate );
			if ( '' !== $path && is_dir( $path ) ) {
				return untrailingslashit( $path );
			}
		}

		return untrailingslashit( WP_CONTENT_DIR . '/wpvividbackups' );
	}

	/**
	 * Recursively searches option values for a plausible backup path.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private function find_path_in_value( $value ) {
		if ( is_string( $value ) && false !== stripos( $value, 'wpvivid' ) && false !== stripos( $value, 'backup' ) ) {
			return $value;
		}

		if ( ! is_array( $value ) ) {
			return '';
		}

		foreach ( $value as $key => $child ) {
			if ( is_string( $key ) && preg_match( '/(path|dir|folder)/i', $key ) ) {
				$path = $this->find_path_in_value( $child );
				if ( '' !== $path ) {
					return $path;
				}
			}
		}

		return '';
	}
}

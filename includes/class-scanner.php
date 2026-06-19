<?php
/**
 * Backup directory scanner.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scans local WPvivid backups and returns stable files.
 */
class Alynt_Drime_WPvivid_Uploader_Scanner {
	const SNAPSHOT_OPTION = 'alynt_drime_wpvivid_file_snapshots';

	/**
	 * Settings.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Settings
	 */
	private $settings;

	/**
	 * Detector.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_WPvivid_Detector
	 */
	private $detector;

	/**
	 * Logger.
	 *
	 * @var Alynt_Drime_WPvivid_Uploader_Logger|null
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param Alynt_Drime_WPvivid_Uploader_Settings         $settings Settings.
	 * @param Alynt_Drime_WPvivid_Uploader_WPvivid_Detector $detector Detector.
	 */
	public function __construct( Alynt_Drime_WPvivid_Uploader_Settings $settings, Alynt_Drime_WPvivid_Uploader_WPvivid_Detector $detector, ?Alynt_Drime_WPvivid_Uploader_Logger $logger = null ) {
		$this->settings = $settings;
		$this->detector = $detector;
		$this->logger   = $logger;
	}

	/**
	 * Scans for stable ZIP backups.
	 *
	 * @return array{directory:string,candidates:array<int,array<string,mixed>>,errors:array<int,string>}
	 */
	public function scan() {
		$settings  = $this->settings->get();
		$directory = $this->detector->get_backup_dir( $settings );
		$result    = array(
			'directory'  => $directory,
			'candidates' => array(),
			'errors'     => array(),
		);

		if ( ! is_dir( $directory ) || ! is_readable( $directory ) ) {
			$result['errors'][] = __( 'The WPvivid backup directory is not readable.', 'alynt-drime-wpvivid-uploader' );
			$this->diagnostic( 'error', 'backup_directory_unreadable', 'The WPvivid backup directory is not readable.', array( 'directory' => $directory ) );
			return $result;
		}

		$snapshots     = $this->get_snapshots();
		$new_snapshots = array();
		$minimum_age   = max( 60, absint( $settings['min_file_age_seconds'] ) );
		$now           = time();
		$files         = glob( trailingslashit( $directory ) . '*.zip' );

		if ( ! is_array( $files ) ) {
			$this->diagnostic( 'warning', 'backup_glob_failed', 'The backup directory scan did not return a file list.', array( 'directory' => $directory ) );
			return $result;
		}

		foreach ( $files as $file ) {
			if ( ! is_file( $file ) || ! is_readable( $file ) || $this->looks_temporary( $file ) ) {
				continue;
			}

			$size  = filesize( $file );
			$mtime = filemtime( $file );
			if ( false === $size || false === $mtime || $size <= 0 ) {
				continue;
			}

			$key                   = $this->signature( $file );
			$new_snapshots[ $key ] = array(
				'size'  => $size,
				'mtime' => $mtime,
			);

			$previous = isset( $snapshots[ $key ] ) && is_array( $snapshots[ $key ] ) ? $snapshots[ $key ] : array();
			$is_stable = isset( $previous['size'] ) && (int) $previous['size'] === (int) $size && ( $now - (int) $mtime ) >= $minimum_age;

			if ( ! $is_stable ) {
				continue;
			}

			$result['candidates'][] = array(
				'signature' => $key,
				'path'      => $file,
				'name'      => basename( $file ),
				'size'      => $size,
				'mtime'     => $mtime,
			);
		}

		update_option( self::SNAPSHOT_OPTION, $new_snapshots, false );

		return $result;
	}

	/**
	 * Writes a scanner diagnostic event.
	 *
	 * @param string              $level Level.
	 * @param string              $code Event code.
	 * @param string              $message Message.
	 * @param array<string,mixed> $context Context.
	 * @return void
	 */
	private function diagnostic( $level, $code, $message, array $context = array() ) {
		if ( $this->logger instanceof Alynt_Drime_WPvivid_Uploader_Logger ) {
			$this->logger->event( 'filesystem', $level, $code, $message, $context );
		}
	}

	/**
	 * Builds a stable local signature.
	 *
	 * @param string $file File path.
	 * @return string
	 */
	public function signature( $file ) {
		return hash( 'sha256', wp_normalize_path( $file ) );
	}

	/**
	 * Returns snapshots.
	 *
	 * @return array<string,array<string,int>>
	 */
	private function get_snapshots() {
		$snapshots = get_option( self::SNAPSHOT_OPTION, array() );

		return is_array( $snapshots ) ? $snapshots : array();
	}

	/**
	 * Determines whether a filename looks incomplete.
	 *
	 * @param string $file File path.
	 * @return bool
	 */
	private function looks_temporary( $file ) {
		$name = strtolower( basename( $file ) );

		return (bool) preg_match( '/(\.tmp|\.part|temp|partial|incomplete)/', $name );
	}
}

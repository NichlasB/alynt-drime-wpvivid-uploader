<?php
/**
 * Upload queue storage.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores queued and active uploads.
 */
class Alynt_Drime_WPvivid_Uploader_Queue {
	const QUEUE_OPTION  = 'alynt_drime_wpvivid_upload_queue';
	const ACTIVE_OPTION = 'alynt_drime_wpvivid_active_upload';

	/**
	 * Adds an item if it is not already queued.
	 *
	 * @param array<string,mixed> $item Item.
	 * @return bool
	 */
	public function add( array $item ) {
		if ( empty( $item['signature'] ) ) {
			return false;
		}

		$queue = $this->all();
		if ( isset( $queue[ $item['signature'] ] ) ) {
			return false;
		}

		$item['queued_at']              = time();
		$item['attempts']               = isset( $item['attempts'] ) ? absint( $item['attempts'] ) : 0;
		$queue[ $item['signature'] ]    = $item;
		update_option( self::QUEUE_OPTION, $queue, false );

		return true;
	}

	/**
	 * Returns all queued items.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function all() {
		$queue = get_option( self::QUEUE_OPTION, array() );

		return is_array( $queue ) ? $queue : array();
	}

	/**
	 * Returns the next queued item.
	 *
	 * @return array<string,mixed>|null
	 */
	public function next() {
		$queue = $this->all();

		if ( empty( $queue ) ) {
			return null;
		}

		$item = reset( $queue );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Removes a queued item.
	 *
	 * @param string $signature Signature.
	 * @return void
	 */
	public function remove( $signature ) {
		$queue = $this->all();
		unset( $queue[ $signature ] );
		update_option( self::QUEUE_OPTION, $queue, false );
	}

	/**
	 * Increments attempts.
	 *
	 * @param string $signature Signature.
	 * @return void
	 */
	public function increment_attempts( $signature ) {
		$queue = $this->all();
		if ( isset( $queue[ $signature ] ) ) {
			$queue[ $signature ]['attempts'] = isset( $queue[ $signature ]['attempts'] ) ? absint( $queue[ $signature ]['attempts'] ) + 1 : 1;
			update_option( self::QUEUE_OPTION, $queue, false );
		}
	}

	/**
	 * Sets active upload state.
	 *
	 * @param array<string,mixed>|null $state State.
	 * @return void
	 */
	public function set_active( $state ) {
		if ( null === $state ) {
			delete_option( self::ACTIVE_OPTION );
			return;
		}

		update_option( self::ACTIVE_OPTION, $state, false );
	}

	/**
	 * Returns active upload state.
	 *
	 * @return array<string,mixed>
	 */
	public function get_active() {
		$state = get_option( self::ACTIVE_OPTION, array() );

		return is_array( $state ) ? $state : array();
	}
}

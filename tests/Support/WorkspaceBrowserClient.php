<?php
/**
 * Test double for Drime workspace browser tests.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 */

class Alynt_Drime_WPvivid_Uploader_Test_Workspace_Client extends Alynt_Drime_WPvivid_Uploader_Drime_Client {
	/**
	 * Response.
	 *
	 * @var array<string,mixed>|WP_Error
	 */
	private $response;

	/**
	 * Constructor.
	 *
	 * @param array<string,mixed>|WP_Error $response Response.
	 */
	public function __construct( $response ) {
		$this->response = $response;
	}

	/**
	 * Lists test workspaces.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function list_workspaces() {
		return $this->response;
	}
}

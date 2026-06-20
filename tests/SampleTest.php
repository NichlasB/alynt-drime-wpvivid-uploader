<?php
/**
 * Sample test to verify PHPUnit is working.
 *
 * @package Alynt_Drime_WPvivid_Uploader
 */

use Brain\Monkey;
use PHPUnit\Framework\TestCase;

class SampleTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_plugin_constants_defined() {
		$this->assertTrue( defined( 'ALYNT_DRIME_WPVIVID_UPLOADER_VERSION' ) );
	}
}

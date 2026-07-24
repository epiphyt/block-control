<?php
/**
 * Tests for the Admin class.
 *
 * @package epiphyt\Block_Control
 */
declare( strict_types = 1 );

namespace epiphyt\Block_Control\Tests\Unit;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use epiphyt\Block_Control\Admin;
use epiphyt\Block_Control\Tests\Test_Case;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass( Admin::class )]
final class AdminTest extends Test_Case {
	/**
	 * init() should register the plugin_row_meta filter.
	 */
	public function test_init_registers_plugin_row_meta_filter(): void {
		Admin::init();

		$this->assertNotFalse( Filters\has( 'plugin_row_meta', [ Admin::class, 'render_plugin_documentation_link' ] ) );
	}

	/**
	 * The documentation link should be left untouched for other plugin files.
	 */
	public function test_render_plugin_documentation_link_ignores_other_files(): void {
		$input = [ 'first' => 'existing-link' ];

		$this->assertSame( $input, Admin::render_plugin_documentation_link( $input, 'some-other-plugin/some-other-plugin.php' ) );
	}

	/**
	 * The documentation link should be appended for the plugin's own file.
	 */
	public function test_render_plugin_documentation_link_appends_link(): void {
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->justReturn( 'Documentation' );
		Functions\when( 'get_plugin_data' )->justReturn( [ 'Version' => '1.6.0' ] );

		$input = [ 'existing-link' ];
		$result = Admin::render_plugin_documentation_link( $input, 'block-control.php' );

		$this->assertCount( 2, $result );
		$this->assertSame( 'existing-link', $result[0] );
		$this->assertStringContainsString( 'version=1.6.0', $result[1] );
		$this->assertStringContainsString( '>Documentation</a>', $result[1] );
		$this->assertStringContainsString( 'target="_blank"', $result[1] );
	}
}

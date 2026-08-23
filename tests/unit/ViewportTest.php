<?php
/**
 * Tests for the Viewport class.
 *
 * @package epiphyt\Block_Control
 */
declare( strict_types = 1 );

namespace epiphyt\Block_Control\Tests\Unit;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use epiphyt\Block_Control\Tests\Test_Case;
use epiphyt\Block_Control\Viewport;

/**
 * @covers \epiphyt\Block_Control\Viewport
 */
final class ViewportTest extends Test_Case {
	/**
	 * Stub the translation function before each test.
	 */
	protected function setUp(): void {
		parent::setUp();

		Functions\when( '__' )->returnArg();
	}

	/**
	 * Stub the global settings with the given viewport settings.
	 *
	 * @param mixed $viewport The viewport settings of theme.json.
	 */
	private function mock_global_settings( $viewport ): void {
		$settings = [];

		if ( $viewport !== null ) {
			$settings['viewport'] = $viewport;
		}

		Functions\when( 'wp_get_global_settings' )->justReturn( $settings );
	}

	/**
	 * Valid media conditions.
	 *
	 * @return array<string, array{string}>
	 */
	public static function provide_valid_conditions(): array {
		return [
			'max-width' => [ '(max-width: 600px)' ],
			'min-width' => [ '(min-width: 1200px)' ],
			'range' => [ '(width <= 480px)' ],
			'range between' => [ '(480px < width <= 782px)' ],
			'media type' => [ 'screen and (max-width: 500px)' ],
			'ratio' => [ '(max-aspect-ratio: 16/9)' ],
			'comma' => [ '(max-width: 400px), (min-width: 1200px)' ],
		];
	}

	/**
	 * Invalid media conditions.
	 *
	 * @return array<string, array{string}>
	 */
	public static function provide_invalid_conditions(): array {
		return [
			'empty' => [ '' ],
			'no feature' => [ 'screen' ],
			'unbalanced parenthesis' => [ '(max-width: 600px' ],
			'closing brace' => [ 'red} body{display:none' ],
			'declaration end' => [ '(max-width: 600px);' ],
			'nested at-rule' => [ '(max-width: 600px) { } @media all' ],
			'comment' => [ '(max-width: 600px) /* comment */' ],
			'quotes' => [ '(max-width: "600px")' ],
			'backslash' => [ '(max-width: 600px) \\' ],
			'too long' => [ '(max-width: ' . \str_repeat( '6', 200 ) . 'px)' ],
		];
	}

	/**
	 * is_valid_condition() should accept usable media conditions.
	 *
	 * @dataProvider provide_valid_conditions
	 *
	 * @param string $condition The media condition to check.
	 */
	public function test_is_valid_condition_accepts_valid_conditions( string $condition ): void {
		$this->assertTrue( Viewport::is_valid_condition( $condition ) );
	}

	/**
	 * is_valid_condition() should reject anything that could break the media query.
	 *
	 * @dataProvider provide_invalid_conditions
	 *
	 * @param string $condition The media condition to check.
	 */
	public function test_is_valid_condition_rejects_invalid_conditions( string $condition ): void {
		$this->assertFalse( Viewport::is_valid_condition( $condition ) );
	}

	/**
	 * get_presets() should fall back to the WordPress defaults without any settings.
	 */
	public function test_get_presets_falls_back_to_defaults(): void {
		$this->mock_global_settings( null );

		$presets = Viewport::get_presets();

		$this->assertSame( [ 'mobile', 'tablet', 'desktop' ], \array_keys( $presets ) );
		$this->assertSame( '@media (width <= 480px)', $presets['mobile']['media_query'] );
		$this->assertSame( '@media (480px < width <= 782px)', $presets['tablet']['media_query'] );
		$this->assertSame( '@media (width > 782px)', $presets['desktop']['media_query'] );
	}

	/**
	 * get_presets() should use the breakpoints of theme.json.
	 */
	public function test_get_presets_uses_theme_json_breakpoints(): void {
		$this->mock_global_settings( [
			'mobile' => '30rem',
			'tablet' => '900px',
		] );

		$presets = Viewport::get_presets();

		$this->assertSame( '@media (width <= 30rem)', $presets['mobile']['media_query'] );
		$this->assertSame( '@media (30rem < width <= 900px)', $presets['tablet']['media_query'] );
		$this->assertSame( '@media (width > 900px)', $presets['desktop']['media_query'] );
	}

	/**
	 * get_presets() should ignore a tablet breakpoint that is not larger than the mobile one.
	 */
	public function test_get_presets_ignores_unordered_tablet_breakpoint(): void {
		$this->mock_global_settings( [
			'mobile' => '600px',
			'tablet' => '400px',
		] );

		$presets = Viewport::get_presets();

		$this->assertSame( [ 'mobile', 'desktop' ], \array_keys( $presets ) );
		$this->assertSame( '@media (width > 600px)', $presets['desktop']['media_query'] );
	}

	/**
	 * get_presets() should ignore breakpoints with an unsupported unit.
	 */
	public function test_get_presets_ignores_invalid_breakpoints(): void {
		$this->mock_global_settings( [
			'mobile' => '50%',
			'tablet' => 'calc(100px + 2em)',
		] );

		$presets = Viewport::get_presets();

		$this->assertSame( '@media (width <= 480px)', $presets['mobile']['media_query'] );
	}

	/**
	 * get_presets() should be filterable.
	 */
	public function test_get_presets_is_filterable(): void {
		$this->mock_global_settings( null );

		Filters\expectApplied( 'block_control_viewport_presets' )->once()->andReturn( [] );

		$this->assertSame( [], Viewport::get_presets() );
	}

	/**
	 * get_class_name() should use the slug of a preset.
	 */
	public function test_get_class_name_of_preset(): void {
		$this->mock_global_settings( null );

		$this->assertSame( 'block-control-hidden-mobile', Viewport::get_class_name( 'mobile' ) );
	}

	/**
	 * get_class_name() should be stable for a custom media condition.
	 */
	public function test_get_class_name_of_condition(): void {
		$this->mock_global_settings( null );

		$class_name = Viewport::get_class_name( '(min-width: 1200px)' );

		$this->assertMatchesRegularExpression( '/^block-control-hidden-[0-9a-f]{10}$/', $class_name );
		// a complete media query describes the very same condition
		$this->assertSame( $class_name, Viewport::get_class_name( '@media  (min-width: 1200px)' ) );
	}

	/**
	 * get_class_name() should return nothing for an invalid entry.
	 */
	public function test_get_class_name_of_invalid_entry(): void {
		$this->mock_global_settings( null );

		$this->assertSame( '', Viewport::get_class_name( 'red} body{display:none' ) );
	}

	/**
	 * get_media_query() should resolve a preset via the current settings.
	 */
	public function test_get_media_query_of_preset(): void {
		$this->mock_global_settings( [
			'mobile' => '500px',
		] );

		$this->assertSame( '@media (width <= 500px)', Viewport::get_media_query( 'mobile' ) );
	}

	/**
	 * get_media_query() should build a media query of a custom media condition.
	 */
	public function test_get_media_query_of_condition(): void {
		$this->mock_global_settings( null );

		$this->assertSame( '@media (min-width: 1200px)', Viewport::get_media_query( '  (min-width:   1200px) ' ) );
	}

	/**
	 * get_media_query() should return nothing for an invalid entry.
	 */
	public function test_get_media_query_of_invalid_entry(): void {
		$this->mock_global_settings( null );

		$this->assertSame( '', Viewport::get_media_query( 'unknown' ) );
	}

	/**
	 * get_custom() should only return valid media conditions.
	 */
	public function test_get_custom_sanitizes_the_option(): void {
		Functions\when( 'get_option' )->justReturn( [
			'(min-width: 1200px)',
			'red} body{display:none',
			'@media (max-width: 600px)',
			'(min-width:  1200px)',
		] );

		$this->assertSame(
			[ '(min-width: 1200px)', '(max-width: 600px)' ],
			Viewport::get_custom()
		);
	}

	/**
	 * get_custom() should cope with a broken option value.
	 */
	public function test_get_custom_of_invalid_option(): void {
		Functions\when( 'get_option' )->justReturn( 'no list' );

		$this->assertSame( [], Viewport::get_custom() );
	}

	/**
	 * set_custom() should store the sanitized media conditions.
	 */
	public function test_set_custom_stores_sanitized_conditions(): void {
		Functions\expect( 'update_option' )
			->once()
			->with( Viewport::OPTION_NAME, [ '(min-width: 1200px)' ] );

		$this->assertSame(
			[ '(min-width: 1200px)' ],
			Viewport::set_custom( [ '(min-width: 1200px)', 'broken}' ] )
		);
	}

	/**
	 * render() should keep the content untouched without any usable entry.
	 */
	public function test_render_ignores_invalid_entries(): void {
		$this->mock_global_settings( null );

		$content = '<p>Content</p>';

		$this->assertSame( $content, Viewport::render( $content, [ 'unknown', 'broken}' ] ) );
	}

	/**
	 * render() should keep empty content untouched.
	 */
	public function test_render_ignores_empty_content(): void {
		$this->assertSame( '', Viewport::render( '', [ 'mobile' ] ) );
	}

	/**
	 * uninstall() should delete the option of a single site.
	 */
	public function test_uninstall_deletes_option(): void {
		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\expect( 'delete_option' )->once()->with( Viewport::OPTION_NAME );

		Viewport::uninstall();
	}

	/**
	 * uninstall() should delete the option of every site of a network.
	 */
	public function test_uninstall_deletes_option_of_every_site(): void {
		Functions\when( 'is_multisite' )->justReturn( true );
		Functions\when( 'get_sites' )->justReturn( [ 1, 2 ] );
		Functions\expect( 'switch_to_blog' )->twice();
		Functions\expect( 'restore_current_blog' )->twice();
		Functions\expect( 'delete_option' )->twice()->with( Viewport::OPTION_NAME );

		Viewport::uninstall();
	}
}

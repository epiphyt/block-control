<?php
/**
 * Tests for the Block_Control class.
 *
 * @package epiphyt\Block_Control
 */
declare( strict_types = 1 );

namespace epiphyt\Block_Control\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use epiphyt\Block_Control\Admin;
use epiphyt\Block_Control\Block_Control;
use epiphyt\Block_Control\Tests\Test_Case;
use Mobile_Detect;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use stdClass;
use WP_Post;

#[CoversClass( Block_Control::class )]
#[UsesClass( Admin::class )]
final class BlockControlTest extends Test_Case {
	/**
	 * A fresh instance to run instance methods against.
	 *
	 * @var \epiphyt\Block_Control\Block_Control
	 */
	private $block_control;

	/**
	 * Create a fresh instance before each test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->block_control = new Block_Control();
	}

	/**
	 * Replace the Mobile_Detect instance with a mock returning the given values.
	 *
	 * @param bool $is_mobile Whether isMobile() should return true.
	 * @param bool $is_tablet Whether isTablet() should return true.
	 */
	private function mock_mobile_detect( bool $is_mobile, bool $is_tablet ): void {
		$mobile_detect = Mockery::mock( Mobile_Detect::class );
		$mobile_detect->shouldReceive( 'isMobile' )->andReturn( $is_mobile );
		$mobile_detect->shouldReceive( 'isTablet' )->andReturn( $is_tablet );

		$this->block_control->mobile_detect = $mobile_detect;
	}

	/**
	 * get_instance() should always return the same instance.
	 */
	public function test_get_instance_returns_singleton(): void {
		$this->assertSame( Block_Control::get_instance(), Block_Control::get_instance() );
	}

	/**
	 * get_instance() should lazily create an instance when none exists yet.
	 */
	public function test_get_instance_creates_instance_when_missing(): void {
		Block_Control::$instance = null;

		$this->assertInstanceOf( Block_Control::class, Block_Control::get_instance() );
	}

	/**
	 * get_ignored_post_types() should return the default list of post types.
	 */
	public function test_get_ignored_post_types_returns_defaults(): void {
		$ignored = $this->block_control->get_ignored_post_types();

		$this->assertContains( 'attachment', $ignored );
		$this->assertContains( 'revision', $ignored );
		$this->assertContains( 'wp_template', $ignored );
	}

	/**
	 * get_ignored_post_types() should be filterable.
	 */
	public function test_get_ignored_post_types_is_filterable(): void {
		Filters\expectApplied( 'block_control_ignored_post_types' )->once()->andReturn( [ 'custom' ] );

		$this->assertSame( [ 'custom' ], $this->block_control->get_ignored_post_types() );
	}

	/**
	 * get_roles() should translate every role name keyed by role slug.
	 */
	public function test_get_roles_returns_translated_roles(): void {
		global $wp_roles;
		$wp_roles = new stdClass();
		$wp_roles->roles = [
			'administrator' => [ 'name' => 'Administrator' ],
			'editor' => [ 'name' => 'Editor' ],
		];

		Functions\when( 'translate_user_role' )->returnArg();

		$this->assertSame(
			[
				'administrator' => 'Administrator',
				'editor' => 'Editor',
			],
			$this->block_control->get_roles()
		);
	}

	/**
	 * get_posts() should skip ignored post types and those hidden from REST.
	 */
	public function test_get_posts_skips_ignored_and_non_rest_post_types(): void {
		Functions\when( 'get_post_types' )->justReturn( [ 'post', 'attachment', 'secret' ] );
		Functions\when( 'get_post_type_object' )->alias( static function ( $post_type ) {
			$object = new stdClass();
			$object->show_in_rest = $post_type !== 'secret';
			$object->labels = (object) [ 'name' => ucfirst( $post_type ) ];

			return $object;
		} );
		Functions\when( 'get_posts' )->justReturn( [ 1, 2 ] );
		Functions\when( 'get_post_field' )->alias( static fn( $field, $id ) => 'Title ' . $id );

		$posts = $this->block_control->get_posts();

		$this->assertArrayHasKey( 'post', $posts );
		$this->assertArrayNotHasKey( 'attachment', $posts );
		$this->assertArrayNotHasKey( 'secret', $posts );
		$this->assertSame( 'Post', $posts['post']['title'] );
		$this->assertSame(
			[
				[ 'ID' => 1, 'post_title' => 'Title 1' ],
				[ 'ID' => 2, 'post_title' => 'Title 2' ],
			],
			$posts['post']['items']
		);
	}

	/**
	 * hide_desktop() should hide desktop content only on non-mobile devices.
	 */
	public function test_hide_desktop(): void {
		$this->mock_mobile_detect( false, false );

		$this->assertTrue( $this->block_control->hide_desktop( 'hideDesktop', true ) );
		$this->assertFalse( $this->block_control->hide_desktop( 'hideMobile', true ) );
		$this->assertFalse( $this->block_control->hide_desktop( 'hideDesktop', false ) );
	}

	/**
	 * hide_desktop() should also hide on tablets.
	 */
	public function test_hide_desktop_on_tablet(): void {
		$this->mock_mobile_detect( true, true );

		$this->assertTrue( $this->block_control->hide_desktop( 'hideDesktop', true ) );
	}

	/**
	 * hide_mobile() should hide mobile content only on phones.
	 */
	public function test_hide_mobile(): void {
		$this->mock_mobile_detect( true, false );

		$this->assertTrue( $this->block_control->hide_mobile( 'hideMobile', true ) );
		$this->assertFalse( $this->block_control->hide_mobile( 'hideDesktop', true ) );
	}

	/**
	 * hide_mobile() should not hide on tablets.
	 */
	public function test_hide_mobile_not_on_tablet(): void {
		$this->mock_mobile_detect( true, true );

		$this->assertFalse( $this->block_control->hide_mobile( 'hideMobile', true ) );
	}

	/**
	 * hide_feed() should hide only when the value is true and a feed is requested.
	 */
	public function test_hide_feed(): void {
		Functions\when( 'is_feed' )->justReturn( true );

		$this->assertTrue( Block_Control::hide_feed( true ) );
		$this->assertFalse( Block_Control::hide_feed( false ) );
	}

	/**
	 * hide_feed() should not hide outside of a feed.
	 */
	public function test_hide_feed_outside_feed(): void {
		Functions\when( 'is_feed' )->justReturn( false );

		$this->assertFalse( Block_Control::hide_feed( true ) );
	}

	/**
	 * hide_logged_in() should hide content from logged-in users.
	 */
	public function test_hide_logged_in(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );

		$this->assertTrue( $this->block_control->hide_logged_in( 'loginStatus', 'logged-out' ) );
		$this->assertFalse( $this->block_control->hide_logged_in( 'loginStatus', 'logged-in' ) );
		$this->assertFalse( $this->block_control->hide_logged_in( 'other', 'logged-out' ) );
	}

	/**
	 * hide_logged_out() should hide content from logged-out users.
	 */
	public function test_hide_logged_out(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( false );

		$this->assertTrue( $this->block_control->hide_logged_out( 'loginStatus', 'logged-in' ) );
		$this->assertFalse( $this->block_control->hide_logged_out( 'loginStatus', 'logged-out' ) );
	}

	/**
	 * hide_screen_reader() should reflect the hideScreenReader attribute.
	 */
	public function test_hide_screen_reader(): void {
		$this->assertTrue( Block_Control::hide_screen_reader( [ 'hideScreenReader' => true ] ) );
		$this->assertFalse( Block_Control::hide_screen_reader( [ 'hideScreenReader' => false ] ) );
		$this->assertFalse( Block_Control::hide_screen_reader( [] ) );
	}

	/**
	 * hide_roles() should not hide content from logged-out users.
	 */
	public function test_hide_roles_returns_false_for_logged_out(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( false );

		$this->assertFalse( $this->block_control->hide_roles( [ 'administrator' => true ] ) );
	}

	/**
	 * hide_roles() should return false for an empty value.
	 */
	public function test_hide_roles_returns_false_for_empty_value(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );

		$this->assertFalse( $this->block_control->hide_roles( [] ) );
	}

	/**
	 * hide_roles() should not hide when the user has an allowed role.
	 */
	public function test_hide_roles_keeps_allowed_role(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		$user = new stdClass();
		$user->roles = [ 'editor' ];
		Functions\when( 'wp_get_current_user' )->justReturn( $user );

		// editor is not flagged as hidden, so the content stays visible
		$this->assertFalse( $this->block_control->hide_roles( [ 'editor' => false, 'administrator' => true ] ) );
	}

	/**
	 * hide_roles() should hide when none of the user's roles are allowed.
	 */
	public function test_hide_roles_hides_disallowed_role(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		$user = new stdClass();
		$user->roles = [ 'administrator' ];
		Functions\when( 'wp_get_current_user' )->justReturn( $user );

		$this->assertTrue( $this->block_control->hide_roles( [ 'editor' => false ] ) );
	}

	/**
	 * hide_conditional_tags() should hide when a matching conditional tag is true.
	 */
	public function test_hide_conditional_tags_matches_active_tag(): void {
		Functions\when( 'is_single' )->justReturn( true );

		$this->assertTrue( $this->block_control->hide_conditional_tags( [ 'is_single' => true ] ) );
	}

	/**
	 * hide_conditional_tags() should not hide when the tag is inactive.
	 */
	public function test_hide_conditional_tags_ignores_inactive_tag(): void {
		Functions\when( 'is_single' )->justReturn( false );

		$this->assertFalse( $this->block_control->hide_conditional_tags( [ 'is_single' => true ] ) );
	}

	/**
	 * hide_conditional_tags() should ignore tags that are not flagged.
	 */
	public function test_hide_conditional_tags_ignores_unflagged_tag(): void {
		$this->assertFalse( $this->block_control->hide_conditional_tags( [ 'is_single' => false ] ) );
	}

	/**
	 * hide_conditional_tags() should support every conditional tag.
	 */
	public function test_hide_conditional_tags_supports_all_tags(): void {
		$tags = [
			'is_home',
			'is_front_page',
			'is_single',
			'is_sticky',
			'is_page',
			'is_category',
			'is_tag',
			'is_tax',
			'is_archive',
			'is_search',
			'is_404',
			'is_paged',
			'is_attachment',
			'is_singular',
		];

		foreach ( $tags as $tag ) {
			Functions\when( $tag )->justReturn( true );

			$this->assertTrue( $this->block_control->hide_conditional_tags( [ $tag => true ] ), $tag . ' should hide the content' );
		}
	}

	/**
	 * hide_numbered_pages() should return false outside of paged contexts.
	 */
	public function test_hide_numbered_pages_returns_false_outside_context(): void {
		foreach ( [ 'is_home', 'is_archive', 'is_category', 'is_tag', 'is_tax', 'is_search' ] as $tag ) {
			Functions\when( $tag )->justReturn( false );
		}

		$this->assertFalse( Block_Control::hide_numbered_pages( [ 'first' => true ] ) );
	}

	/**
	 * hide_numbered_pages() should hide the first page when requested.
	 */
	public function test_hide_numbered_pages_hides_first_page(): void {
		Functions\when( 'is_home' )->justReturn( true );

		global $wp_query;
		$wp_query = new stdClass();
		$wp_query->query_vars = [ 'paged' => 1 ];
		$wp_query->max_num_pages = 5;

		$this->assertTrue( Block_Control::hide_numbered_pages( [ 'first' => true ] ) );
	}

	/**
	 * hide_numbered_pages() should hide the last page when requested.
	 */
	public function test_hide_numbered_pages_hides_last_page(): void {
		Functions\when( 'is_home' )->justReturn( true );

		global $wp_query;
		$wp_query = new stdClass();
		$wp_query->query_vars = [ 'paged' => 5 ];
		$wp_query->max_num_pages = 5;

		$this->assertTrue( Block_Control::hide_numbered_pages( [ 'last' => true ] ) );
	}

	/**
	 * hide_numbered_pages() should hide odd and even pages when requested.
	 */
	public function test_hide_numbered_pages_hides_odd_and_even(): void {
		Functions\when( 'is_home' )->justReturn( true );

		global $wp_query;
		$wp_query = new stdClass();
		$wp_query->query_vars = [ 'paged' => 3 ];
		$wp_query->max_num_pages = 5;

		$this->assertTrue( Block_Control::hide_numbered_pages( [ 'odd' => true ] ) );
		$this->assertFalse( Block_Control::hide_numbered_pages( [ 'even' => true ] ) );

		$wp_query->query_vars = [ 'paged' => 2 ];

		$this->assertTrue( Block_Control::hide_numbered_pages( [ 'even' => true ] ) );
		$this->assertFalse( Block_Control::hide_numbered_pages( [ 'odd' => true ] ) );
	}

	/**
	 * hide_numbered_pages() should hide pages listed as custom values.
	 */
	public function test_hide_numbered_pages_hides_custom_pages(): void {
		Functions\when( 'is_home' )->justReturn( true );

		global $wp_query;
		$wp_query = new stdClass();
		$wp_query->query_vars = [ 'paged' => 4 ];
		$wp_query->max_num_pages = 5;

		$this->assertTrue( Block_Control::hide_numbered_pages( [ 'custom' => [ '4' ] ] ) );
		$this->assertFalse( Block_Control::hide_numbered_pages( [ 'custom' => [ '2' ] ] ) );
	}

	/**
	 * hide_post() should return false when there is no post.
	 */
	public function test_hide_post_returns_false_without_post(): void {
		Functions\when( 'get_post' )->justReturn( null );

		$this->assertFalse( $this->block_control->hide_post( [ 'post' => [ 1 => true ] ] ) );
	}

	/**
	 * hide_post() should hide the current post when it is flagged.
	 */
	public function test_hide_post_hides_flagged_post(): void {
		$post = new WP_Post( [ 'ID' => 5, 'post_type' => 'post' ] );
		Functions\when( 'get_post' )->justReturn( $post );

		$this->assertTrue( $this->block_control->hide_post( [ 'post' => [ 5 => true ] ] ) );
		$this->assertFalse( $this->block_control->hide_post( [ 'post' => [ 5 => false ] ] ) );
	}

	/**
	 * hide_post() should return false when the post type is not configured.
	 */
	public function test_hide_post_returns_false_for_unconfigured_type(): void {
		$post = new WP_Post( [ 'ID' => 5, 'post_type' => 'page' ] );
		Functions\when( 'get_post' )->justReturn( $post );

		$this->assertFalse( $this->block_control->hide_post( [ 'post' => [ 5 => true ] ] ) );
	}

	/**
	 * hide_post() should return false when the current post is not in the list.
	 */
	public function test_hide_post_returns_false_for_unlisted_post(): void {
		$post = new WP_Post( [ 'ID' => 5, 'post_type' => 'post' ] );
		Functions\when( 'get_post' )->justReturn( $post );

		$this->assertFalse( $this->block_control->hide_post( [ 'post' => [ 99 => true ] ] ) );
	}

	/**
	 * register_attributes() should merge the Block Control attributes.
	 */
	public function test_register_attributes_merges_attributes(): void {
		$args = $this->block_control->register_attributes( [ 'attributes' => [ 'existing' => [ 'type' => 'string' ] ] ] );

		$this->assertArrayHasKey( 'existing', $args['attributes'] );
		$this->assertArrayHasKey( 'hideDesktop', $args['attributes'] );
		$this->assertSame( 'boolean', $args['attributes']['hideDesktop']['type'] );
		$this->assertSame( 'none', $args['attributes']['loginStatus']['default'] );
		$this->assertInstanceOf( stdClass::class, $args['attributes']['hideConditionalTags']['default'] );
	}

	/**
	 * set_plugin_file() should store an existing file path.
	 */
	public function test_set_plugin_file_stores_existing_file(): void {
		$this->block_control->set_plugin_file( __FILE__ );

		$this->assertSame( __FILE__, $this->block_control->plugin_file );
	}

	/**
	 * set_plugin_file() should ignore a non-existent file path.
	 */
	public function test_set_plugin_file_ignores_missing_file(): void {
		$this->block_control->set_plugin_file( __DIR__ . '/does-not-exist.php' );

		$this->assertSame( '', $this->block_control->plugin_file );
	}

	/**
	 * strtotime() should use UTC when no timezone is configured.
	 */
	public function test_strtotime_uses_utc_by_default(): void {
		Functions\when( 'get_option' )->alias( static fn( $name, $default = false ) => $name === 'gmt_offset' ? 0 : '' );

		$this->assertSame( 1609459200, $this->block_control->strtotime( '2021-01-01 00:00:00' ) );
	}

	/**
	 * strtotime() should honor a configured timezone string.
	 */
	public function test_strtotime_uses_timezone_string(): void {
		Functions\when( 'get_option' )->alias( static fn( $name, $default = false ) => $name === 'timezone_string' ? 'Europe/Berlin' : 0 );

		$this->assertSame( 1609455600, $this->block_control->strtotime( '2021-01-01 00:00:00' ) );
	}

	/**
	 * strtotime() should build a positive offset from a bare gmt_offset.
	 */
	public function test_strtotime_uses_positive_gmt_offset(): void {
		Functions\when( 'get_option' )->alias( static fn( $name, $default = false ) => $name === 'gmt_offset' ? '2' : '' );

		$this->assertSame( 1609452000, $this->block_control->strtotime( '2021-01-01 00:00:00' ) );
	}

	/**
	 * strtotime() should keep an already-signed gmt_offset.
	 */
	public function test_strtotime_uses_negative_gmt_offset(): void {
		Functions\when( 'get_option' )->alias( static fn( $name, $default = false ) => $name === 'gmt_offset' ? '-5' : '' );

		$this->assertSame( 1609477200, $this->block_control->strtotime( '2021-01-01 00:00:00' ) );
	}

	/**
	 * toggle_blocks() should return the content untouched when there are no attributes.
	 */
	public function test_toggle_blocks_without_attributes(): void {
		$content = '<p>Visible</p>';

		$this->assertSame( $content, $this->block_control->toggle_blocks( $content, [ 'attrs' => [] ] ) );
	}

	/**
	 * toggle_blocks() should add the screen-reader-text helper class.
	 */
	public function test_toggle_blocks_adds_screen_reader_class(): void {
		$content = '<p class="block-control__screen-reader-text">Hi</p>';

		$this->assertStringContainsString(
			'class="block-control__screen-reader-text screen-reader-text"',
			$this->block_control->toggle_blocks( $content, [ 'attrs' => [] ] )
		);
	}

	/**
	 * toggle_blocks() should add the screen-reader-text helper class independently
	 * of the quotes and other classes in use.
	 */
	public function test_toggle_blocks_adds_screen_reader_class_to_any_markup(): void {
		$contents = [
			'<p class=\'block-control__screen-reader-text\'>Hi</p>' => 'class=\'block-control__screen-reader-text screen-reader-text\'',
			'<p class="intro block-control__screen-reader-text">Hi</p>' => 'class="intro block-control__screen-reader-text screen-reader-text"',
			'<p class="block-control__screen-reader-text intro">Hi</p>' => 'class="block-control__screen-reader-text intro screen-reader-text"',
		];

		foreach ( $contents as $content => $expected ) {
			$this->assertStringContainsString(
				$expected,
				$this->block_control->toggle_blocks( $content, [ 'attrs' => [] ] )
			);
		}
	}

	/**
	 * toggle_blocks() should add the screen-reader-text helper class only once,
	 * as nested blocks are filtered again as part of their parent content.
	 */
	public function test_toggle_blocks_adds_screen_reader_class_only_once(): void {
		$content = '<p class="block-control__screen-reader-text">Hi</p>';
		$result = $this->block_control->toggle_blocks( $content, [ 'attrs' => [] ] );
		$result = $this->block_control->toggle_blocks( $result, [ 'attrs' => [] ] );

		// once as part of the own class, once as the class of the theme
		$this->assertSame( 2, \substr_count( $result, 'screen-reader-text' ) );
	}

	/**
	 * toggle_blocks() should hide the block when a condition matches.
	 */
	public function test_toggle_blocks_hides_block(): void {
		Functions\when( 'is_feed' )->justReturn( true );

		$this->assertSame( '', $this->block_control->toggle_blocks( '<p>Feed</p>', [ 'attrs' => [ 'hideFeed' => true ] ] ) );
	}

	/**
	 * toggle_blocks() should add aria-hidden for screen-reader-only blocks.
	 */
	public function test_toggle_blocks_adds_aria_hidden(): void {
		$result = $this->block_control->toggle_blocks( '<p>Hi</p>', [ 'attrs' => [ 'hideScreenReader' => true ] ] );

		$this->assertSame( '<p aria-hidden="true">Hi</p>', $result );
	}

	/**
	 * toggle_blocks() should add aria-hidden to the outer element only.
	 */
	public function test_toggle_blocks_adds_aria_hidden_to_outer_element_only(): void {
		$content = '<div class="wp-block-group">' . "\n" . '<p>Hi</p>' . "\n" . '</div>';
		$result = $this->block_control->toggle_blocks( $content, [ 'attrs' => [ 'hideScreenReader' => true ] ] );

		$this->assertSame( 1, \substr_count( $result, 'aria-hidden="true"' ) );
		$this->assertStringStartsWith( '<div aria-hidden="true" class="wp-block-group">', $result );
	}

	/**
	 * toggle_blocks() should never add aria-hidden to a closing tag.
	 */
	public function test_toggle_blocks_keeps_closing_tags_intact(): void {
		$content = '<div>' . "\n" . '<p>Hi</p>' . "\n" . '</div>';
		$result = $this->block_control->toggle_blocks( $content, [ 'attrs' => [ 'hideScreenReader' => true ] ] );

		$this->assertStringContainsString( '</div>', $result );
		$this->assertStringNotContainsString( '</div aria-hidden', $result );
		$this->assertStringNotContainsString( '</p aria-hidden', $result );
	}

	/**
	 * toggle_blocks() should hide a block once its start date has passed.
	 */
	public function test_toggle_blocks_hides_after_start_date(): void {
		Functions\when( 'get_option' )->alias( static fn( $name, $default = false ) => $name === 'gmt_offset' ? 0 : '' );

		$block = [
			'attrs' => [
				'hideByDate' => true,
				'hideByDateStart' => '2000-01-01 00:00:00',
			],
		];

		$this->assertSame( '', $this->block_control->toggle_blocks( '<p>Time-limited</p>', $block ) );
	}

	/**
	 * toggle_blocks() should keep a block while its start date is in the future.
	 */
	public function test_toggle_blocks_keeps_before_start_date(): void {
		Functions\when( 'get_option' )->alias( static fn( $name, $default = false ) => $name === 'gmt_offset' ? 0 : '' );

		$content = '<p>Not yet</p>';
		$block = [
			'attrs' => [
				'hideByDate' => true,
				'hideByDateStart' => '2100-01-01 00:00:00',
			],
		];

		$this->assertSame( $content, $this->block_control->toggle_blocks( $content, $block ) );
	}

	/**
	 * toggle_blocks() should hide a block within an active start/end range.
	 */
	public function test_toggle_blocks_hides_within_date_range(): void {
		Functions\when( 'get_option' )->alias( static fn( $name, $default = false ) => $name === 'gmt_offset' ? 0 : '' );

		$block = [
			'attrs' => [
				'hideByDate' => true,
				'hideByDateStart' => '2000-01-01 00:00:00',
				'hideByDateEnd' => '2100-01-01 00:00:00',
			],
		];

		$this->assertSame( '', $this->block_control->toggle_blocks( '<p>Within range</p>', $block ) );
	}

	/**
	 * toggle_blocks() should keep a block once its end date has passed.
	 */
	public function test_toggle_blocks_keeps_after_end_date(): void {
		Functions\when( 'get_option' )->alias( static fn( $name, $default = false ) => $name === 'gmt_offset' ? 0 : '' );

		$content = '<p>Expired range</p>';
		$block = [
			'attrs' => [
				'hideByDate' => true,
				'hideByDateStart' => '2000-01-01 00:00:00',
				'hideByDateEnd' => '2001-01-01 00:00:00',
			],
		];

		$this->assertSame( $content, $this->block_control->toggle_blocks( $content, $block ) );
	}

	/**
	 * toggle_blocks() should hide a block before its end date with no start date.
	 */
	public function test_toggle_blocks_hides_before_end_date(): void {
		Functions\when( 'get_option' )->alias( static fn( $name, $default = false ) => $name === 'gmt_offset' ? 0 : '' );

		$block = [
			'attrs' => [
				'hideByDate' => true,
				'hideByDateEnd' => '2100-01-01 00:00:00',
			],
		];

		$this->assertSame( '', $this->block_control->toggle_blocks( '<p>Until end</p>', $block ) );
	}

	/**
	 * toggle_blocks() should keep a block once its end-only date has passed.
	 */
	public function test_toggle_blocks_keeps_after_end_only_date(): void {
		Functions\when( 'get_option' )->alias( static fn( $name, $default = false ) => $name === 'gmt_offset' ? 0 : '' );

		$content = '<p>Ended</p>';
		$block = [
			'attrs' => [
				'hideByDate' => true,
				'hideByDateEnd' => '2000-01-01 00:00:00',
			],
		];

		$this->assertSame( $content, $this->block_control->toggle_blocks( $content, $block ) );
	}

	/**
	 * toggle_blocks() should keep a block whose end date sits before a future start.
	 */
	public function test_toggle_blocks_keeps_when_end_before_future_start(): void {
		Functions\when( 'get_option' )->alias( static fn( $name, $default = false ) => $name === 'gmt_offset' ? 0 : '' );

		$content = '<p>Future window</p>';
		$block = [
			'attrs' => [
				'hideByDate' => true,
				'hideByDateStart' => '2100-01-01 00:00:00',
				'hideByDateEnd' => '2200-01-01 00:00:00',
			],
		];

		$this->assertSame( $content, $this->block_control->toggle_blocks( $content, $block ) );
	}

	/**
	 * editor_assets() should localize the posts and roles for the editor script.
	 */
	public function test_editor_assets_localizes_store(): void {
		Functions\when( 'get_post_types' )->justReturn( [] );
		global $wp_roles;
		$wp_roles = new stdClass();
		$wp_roles->roles = [];

		$localized = [];
		Functions\when( 'wp_localize_script' )->alias( static function ( $handle, $name, $data ) use ( &$localized ) {
			$localized = [ $handle, $name, $data ];
		} );

		$this->block_control->editor_assets();

		$this->assertSame( 'block-control-settings-editor-script', $localized[0] );
		$this->assertSame( 'blockControlStore', $localized[1] );
		$this->assertSame( [ 'posts' => [], 'roles' => [] ], $localized[2] );
	}

	/**
	 * init() should register the plugin's hooks.
	 */
	public function test_init_registers_hooks(): void {
		$this->block_control->init();

		$this->assertNotFalse( Actions\has( 'enqueue_block_editor_assets' ) );
		$this->assertNotFalse( Actions\has( 'init' ) );
		$this->assertNotFalse( Filters\has( 'register_block_type_args' ) );
		$this->assertNotFalse( Filters\has( 'render_block' ) );
		$this->assertNotFalse( Filters\has( 'plugin_row_meta' ) );
	}

	/**
	 * register_blocks() should register the block metadata collection from build/.
	 */
	public function test_register_blocks_registers_from_build(): void {
		$args = [];
		Functions\when( 'wp_register_block_types_from_metadata_collection' )->alias( static function ( $path, $manifest ) use ( &$args ) {
			$args = [ $path, $manifest ];
		} );

		Block_Control::register_blocks();

		$this->assertSame( EPI_BLOCK_CONTROL_BASE . 'build', $args[0] );
		$this->assertSame( EPI_BLOCK_CONTROL_BASE . 'build/blocks-manifest.php', $args[1] );
	}
}

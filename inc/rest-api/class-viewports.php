<?php
declare(strict_types = 1);

namespace epiphyt\Block_Control\REST_API;

use epiphyt\Block_Control\Viewport;

/**
 * The viewports REST API controller.
 * 
 * @since	2.0.0
 * 
 * @author	Epiphyt
 * @license	GPL2
 * @package	epiphyt\Block_Control
 */
final class Viewports extends \WP_REST_Controller {
	/**
	 * @var		string The namespace of the route
	 */
	protected $namespace = 'block-control/v1'; // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
	
	/**
	 * @var		string The base of the route
	 */
	protected $rest_base = 'viewports'; // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
	
	/**
	 * Register the routes.
	 */
	public function register_routes(): void {
		$args = [
			[
				'callback' => [ $this, 'get_items' ],
				'methods' => \WP_REST_Server::READABLE,
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
			],
			[
				'args' => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
				'callback' => [ $this, 'create_item' ],
				'methods' => \WP_REST_Server::CREATABLE,
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
		];
		$args['schema'] = [ $this, 'get_public_item_schema' ];
		
		\register_rest_route( $this->namespace, '/' . $this->rest_base, $args );
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function create_item( $request ) { // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
		$viewports = \array_merge( Viewport::get_custom(), (array) $request->get_param( 'viewports' ) );
		
		return \rest_ensure_response( $this->prepare_item_for_response( Viewport::set_custom( $viewports ), $request ) );
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function create_item_permissions_check( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
		return $this->check_permissions();
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function get_items( $request ) { // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
		return \rest_ensure_response( $this->prepare_item_for_response( Viewport::get_custom(), $request ) );
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function get_items_permissions_check( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
		return $this->check_permissions();
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function get_item_schema() { // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
		if ( $this->schema !== null ) {
			return $this->add_additional_fields_schema( $this->schema );
		}
		
		$this->schema = [
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'properties' => [
				'presets' => [
					'context' => [ 'edit' ],
					'description' => \__( 'The viewport presets of the current theme.', 'block-control' ),
					'readonly' => true,
					'type' => 'object',
				],
				'viewports' => [
					'context' => [ 'edit' ],
					'description' => \__( 'The custom media conditions.', 'block-control' ),
					'items' => [
						'type' => 'string',
					],
					'type' => 'array',
				],
			],
			'title' => 'block-control-viewports',
			'type' => 'object',
		];
		
		return $this->add_additional_fields_schema( $this->schema );
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function prepare_item_for_response( $item, $request ) { // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
		$data = [
			'presets' => Viewport::get_presets(),
			'viewports' => $item,
		];
		$data = $this->add_additional_fields_to_object( $data, $request );
		
		return \rest_ensure_response( $this->filter_response_by_context( $data, 'edit' ) );
	}
	
	/**
	 * Check whether the current user may read and add media queries.
	 * 
	 * Media queries are shared across all posts, but they are part of
	 * editing a block and thus available to everyone who can edit posts.
	 * 
	 * @return	bool|\WP_Error True if the request has access, WP_Error otherwise
	 */
	private function check_permissions(): bool|\WP_Error {
		if ( ! \current_user_can( 'edit_posts' ) ) {
			return new \WP_Error(
				'block_control_rest_forbidden',
				\__( 'Sorry, you are not allowed to manage media queries.', 'block-control' ),
				[
					'status' => \rest_authorization_required_code(),
				]
			);
		}
		
		return true;
	}
}

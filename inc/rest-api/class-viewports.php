<?php
namespace epiphyt\Block_Control\REST_API;

use epiphyt\Block_Control\Viewport;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * The viewports REST API controller.
 * 
 * @since	2.0.0
 * 
 * @author	Epiphyt
 * @license	GPL2
 * @package	epiphyt\Block_Control
 */
final class Viewports extends WP_REST_Controller {
	/**
	 * @var		string The namespace of the route
	 */
	protected $namespace = 'block-control/v1';
	
	/**
	 * @var		string The base of the route
	 */
	protected $rest_base = 'viewports';
	
	/**
	 * Register the routes.
	 */
	public function register_routes(): void {
		$args = [
			[
				'callback' => [ $this, 'get_items' ],
				'methods' => WP_REST_Server::READABLE,
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
			],
			[
				'args' => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				'callback' => [ $this, 'create_item' ],
				'methods' => WP_REST_Server::CREATABLE,
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
		];
		$args['schema'] = [ $this, 'get_public_item_schema' ];
		
		\register_rest_route( $this->namespace, '/' . $this->rest_base, $args );
	}
	
	/**
	 * Add media conditions to the stored ones.
	 * 
	 * @param	\WP_REST_Request	$request The request object
	 * @return	\WP_REST_Response|\WP_Error The response object
	 */
	public function create_item( $request ) {
		$viewports = \array_merge( Viewport::get_custom(), (array) $request->get_param( 'viewports' ) );
		
		return \rest_ensure_response( $this->prepare_item_for_response( Viewport::set_custom( $viewports ), $request ) );
	}
	
	/**
	 * Check whether media conditions may be added.
	 * 
	 * @param	\WP_REST_Request	$request The request object
	 * @return	bool|\WP_Error True if the request has access, WP_Error otherwise
	 */
	public function create_item_permissions_check( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return $this->check_permissions();
	}
	
	/**
	 * Get the presets and the stored media conditions.
	 * 
	 * @param	\WP_REST_Request	$request The request object
	 * @return	\WP_REST_Response|\WP_Error The response object
	 */
	public function get_items( $request ) {
		return \rest_ensure_response( $this->prepare_item_for_response( Viewport::get_custom(), $request ) );
	}
	
	/**
	 * Check whether the presets and media conditions may be read.
	 * 
	 * @param	\WP_REST_Request	$request The request object
	 * @return	bool|\WP_Error True if the request has access, WP_Error otherwise
	 */
	public function get_items_permissions_check( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return $this->check_permissions();
	}
	
	/**
	 * Get the item schema.
	 * 
	 * @return	array The item schema
	 */
	public function get_item_schema() {
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
	 * Prepare the media conditions for the response.
	 * 
	 * @param	string[]			$item The list of custom media conditions
	 * @param	\WP_REST_Request	$request The request object
	 * @return	\WP_REST_Response The response object
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = [
			'presets' => Viewport::get_presets(),
			'viewports' => $item,
		];
		$data = $this->add_additional_fields_to_object( $data, $request );
		
		return \rest_ensure_response( $this->filter_response_by_context( $data, 'edit' ) );
	}
	
	/**
	 * Check whether the current user may read and add media conditions.
	 * 
	 * Media conditions are shared across all posts, but they are part of
	 * editing a block and thus available to everyone who can edit posts.
	 * 
	 * @return	bool|\WP_Error True if the request has access, WP_Error otherwise
	 */
	private function check_permissions() {
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

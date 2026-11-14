<?php
declare(strict_types=1);

namespace MintLMS\Http\Rest\Controller;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Contract\UserLookupInterface;
use MintLMS\Http\Rest\Response\ApiResponse;

final class UserController {

	private const NAMESPACE = 'mintlms/v1';

	public function __construct(
		private UserLookupInterface $userLookup,
		private AuthorizationInterface $authorization,
	) {
	}

	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/users',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list' ),
				'permission_callback' => array( $this, 'canEnrollStudents' ),
				'args'                => array(
					'q'        => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'page'     => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'per_page' => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 100,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/users/search',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search' ),
				'permission_callback' => array( $this, 'canEnrollStudents' ),
				'args'                => array(
					'q'     => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'limit' => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 10,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	public function canEnrollStudents(): bool {
		$userId = $this->authorization->getCurrentUserId();

		return $userId > 0 && $this->authorization->canEnrollStudents( $userId );
	}

	public function list( \WP_REST_Request $request ): \WP_REST_Response {
		$page    = (int) $request->get_param( 'page' );
		$perPage = (int) $request->get_param( 'per_page' );
		$search  = (string) $request->get_param( 'q' );
		$result  = $this->userLookup->listUsers( $page > 0 ? $page : 1, $perPage > 0 ? $perPage : 100, $search );

		return ApiResponse::success(
			array(
				'users' => $result['users'],
				'total' => $result['total'],
			)
		);
	}

	public function search( \WP_REST_Request $request ): \WP_REST_Response {
		$query = (string) $request->get_param( 'q' );
		$limit = (int) $request->get_param( 'limit' );
		$users = $this->userLookup->searchUsers( $query, $limit > 0 ? $limit : 10 );

		return ApiResponse::success(
			array(
				'users' => $users,
			)
		);
	}
}

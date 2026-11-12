<?php
declare(strict_types=1);

namespace MintLMS\Http\Rest\Response;

defined( 'ABSPATH' ) || exit;

final class ApiResponse {

	/**
	 * @param array<string, mixed>|list<mixed>|null $data
	 */
	public static function success( array|null $data = null, int $status = 200 ): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
				'error'   => null,
			),
			$status
		);
	}

	/**
	 * @param array<string, mixed> $details
	 */
	public static function error( string $code, string $message, int $status = 400, array $details = array() ): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'success' => false,
				'data'    => null,
				'error'   => array(
					'code'    => $code,
					'message' => $message,
					'details' => $details,
				),
			),
			$status
		);
	}
}

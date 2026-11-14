<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\User;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\UserLookupInterface;

final class WpUserLookup implements UserLookupInterface {

	public function searchUsers( string $query, int $limit = 10 ): array {
		$query = trim( $query );

		if ( '' === $query ) {
			return array();
		}

		$limit = max( 1, min( $limit, 20 ) );

		if ( ctype_digit( $query ) ) {
			$user = get_userdata( (int) $query );

			if ( ! $user instanceof \WP_User ) {
				return array();
			}

			return array(
				$this->mapUser( $user ),
			);
		}

		$wpdb = mintlms_wpdb();
		$like = null !== $wpdb
			? '*' . $wpdb->esc_like( $query ) . '*'
			: '*' . $query . '*';

		$userQuery = new \WP_User_Query(
			array(
				'number'         => $limit,
				'search'         => $like,
				'search_columns' => array( 'user_login', 'user_email', 'user_nicename', 'display_name' ),
				'orderby'        => 'display_name',
				'order'          => 'ASC',
			)
		);

		$results = array();

		foreach ( $userQuery->get_results() as $user ) {
			if ( ! $user instanceof \WP_User ) {
				continue;
			}

			$results[] = $this->mapUser( $user );
		}

		return $results;
	}

	public function listUsers( int $page = 1, int $perPage = 100, string $search = '' ): array {
		$page    = max( 1, $page );
		$perPage = max( 1, min( 200, $perPage ) );
		$search  = trim( $search );

		$args = array(
			'number'  => $perPage,
			'paged'   => $page,
			'orderby' => 'display_name',
			'order'   => 'ASC',
			'fields'  => 'all',
		);

		if ( '' !== $search ) {
			$wpdb = mintlms_wpdb();
			$like = null !== $wpdb
				? '*' . $wpdb->esc_like( $search ) . '*'
				: '*' . $search . '*';

			$args['search']         = $like;
			$args['search_columns'] = array( 'user_login', 'user_email', 'user_nicename', 'display_name' );
		}

		$query = new \WP_User_Query( $args );
		$users = array();

		foreach ( $query->get_results() as $user ) {
			if ( $user instanceof \WP_User ) {
				$users[] = $this->mapUser( $user );
			}
		}

		return array(
			'users' => $users,
			'total' => (int) $query->get_total(),
		);
	}

	public function getDisplayName( int $userId ): ?string {
		$user = get_userdata( $userId );

		if ( ! $user instanceof \WP_User ) {
			return null;
		}

		$name = trim( (string) $user->display_name );

		return '' !== $name ? $name : null;
	}

	/**
	 * @return array{id: int, email: string, display_name: string, user_login: string}
	 */
	private function mapUser( \WP_User $user ): array {
		return array(
			'id'           => (int) $user->ID,
			'email'        => (string) $user->user_email,
			'display_name' => (string) $user->display_name,
			'user_login'   => (string) $user->user_login,
		);
	}
}

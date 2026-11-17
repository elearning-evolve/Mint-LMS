<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Capability;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Native wp-admin CPT lists: instructors only see their own Mint posts.
 * Mirrors LearnDash Instructor Role `author__in` pre_get_posts scoping.
 */
final class AdminContentListScope {

	public function register(): void {
		add_action( 'pre_get_posts', array( $this, 'restrictMintAdminLists' ) );
	}

	public function restrictMintAdminLists( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// REST content lists are scoped in ContentPostListController.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		if ( ! ContentOwnership::currentUserMustOwnContent() ) {
			return;
		}

		$postType = $query->get( 'post_type' );
		if ( ! $this->isMintListPostType( $postType ) ) {
			return;
		}

		$userId = get_current_user_id();
		if ( $userId <= 0 ) {
			return;
		}

		$query->set( 'author', $userId );
	}

	/**
	 * @param mixed $postType Query post_type value.
	 */
	private function isMintListPostType( mixed $postType ): bool {
		if ( is_string( $postType ) ) {
			return PostTypes::isContentType( $postType );
		}

		if ( ! is_array( $postType ) || array() === $postType ) {
			return false;
		}

		foreach ( $postType as $type ) {
			if ( ! is_string( $type ) || ! PostTypes::isContentType( $type ) ) {
				return false;
			}
		}

		return true;
	}
}

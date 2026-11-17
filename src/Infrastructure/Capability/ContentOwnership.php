<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Capability;

defined( 'ABSPATH' ) || exit;

/**
 * Author scoping for Mint content lists (LearnDash / Lifter-style).
 *
 * Manager + Administrator: site-wide content.
 * Instructor (edit without edit_others): own posts only.
 */
final class ContentOwnership {

	/**
	 * True when the current user may list/edit content authored by others.
	 */
	public static function currentUserCanViewAll(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		return current_user_can( Roles::CAP_EDIT_OTHERS )
			|| current_user_can( Roles::CAP_MANAGE )
			|| current_user_can( 'manage_options' );
	}

	/**
	 * Instructors who can edit Mint content but not others' posts.
	 */
	public static function currentUserMustOwnContent(): bool {
		return current_user_can( Roles::CAP_EDIT ) && ! self::currentUserCanViewAll();
	}

	/**
	 * Restrict a WP_Query args array to the current author when required.
	 *
	 * @param array<string, mixed> $queryArgs Query arguments.
	 * @return array<string, mixed>
	 */
	public static function applyAuthorRestriction( array $queryArgs ): array {
		if ( ! self::currentUserMustOwnContent() ) {
			return $queryArgs;
		}

		$userId = get_current_user_id();
		if ( $userId <= 0 ) {
			return $queryArgs;
		}

		$queryArgs['author'] = $userId;

		return $queryArgs;
	}
}

<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

final class FirstRunState {

	public const OPTION_FIRST_RUN_COMPLETE     = 'mintlms_first_run_complete';
	public const OPTION_GUIDED_COURSE_COMPLETE = 'mintlms_guided_course_complete';
	public const USER_META_WELCOME_DISMISSED   = 'mintlms_welcome_dismissed';
	/** Temporary testing flag — remove after welcome QA. */
	public const PREVIEW_WELCOME_QUERY         = 'mintlms_preview_welcome';

	/**
	 * TEMP testing switch: force first-run welcome on admin.php?page=mint-lms.
	 * Does not delete courses. Set to false (or remove) when QA is done.
	 */
	public const TEMP_FORCE_WELCOME_FOR_TESTING = false;

	public static function isFirstRunComplete(): bool {
		return (bool) get_option( self::OPTION_FIRST_RUN_COMPLETE, false );
	}

	/**
	 * Admin-only force preview of the first-run welcome (keeps existing courses).
	 * Enabled while TEMP_FORCE_WELCOME_FOR_TESTING is true, or via
	 * admin.php?page=mint-lms&mintlms_preview_welcome=1
	 */
	public static function isWelcomePreviewRequested(): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( self::TEMP_FORCE_WELCOME_FOR_TESTING ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only preview toggle for admins.
		if ( ! isset( $_GET[ self::PREVIEW_WELCOME_QUERY ] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only preview toggle for admins.
		$flag = sanitize_text_field( wp_unslash( (string) $_GET[ self::PREVIEW_WELCOME_QUERY ] ) );

		return '1' === $flag || 'true' === $flag;
	}

	public static function isGuidedCourseComplete(): bool {
		return (bool) get_option( self::OPTION_GUIDED_COURSE_COMPLETE, false );
	}

	public static function markFirstRunStarted(): void {
		update_option( self::OPTION_FIRST_RUN_COMPLETE, true, false );
		self::dismissWelcomeForUser( get_current_user_id() );
	}

	public static function markOnboardingComplete(): void {
		update_option( self::OPTION_FIRST_RUN_COMPLETE, true, false );
		update_option( self::OPTION_GUIDED_COURSE_COMPLETE, true, false );
		self::dismissWelcomeForUser( get_current_user_id() );
	}

	public static function dismissWelcomeForUser( int $userId ): void {
		if ( $userId <= 0 ) {
			return;
		}

		update_user_meta( $userId, self::USER_META_WELCOME_DISMISSED, 1 );
	}

	public static function hasDismissedWelcome( int $userId ): bool {
		if ( $userId <= 0 ) {
			return false;
		}

		return (bool) get_user_meta( $userId, self::USER_META_WELCOME_DISMISSED, true );
	}

	public static function userCourseCount( int $userId ): int {
		if ( $userId <= 0 ) {
			return 0;
		}

		$query = new \WP_Query(
			array(
				'post_type'              => PostTypes::COURSE,
				'post_status'            => array( 'draft', 'publish', PostTypes::STATUS_ARCHIVED ),
				'author'                 => $userId,
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Admin / Instructor first-time dashboard: welcome until skip, create, or own a course.
	 * Instructor gets a 2-step classroom variant; Admin/Manager keep 3 steps.
	 */
	public static function shouldShowWelcome( int $userId ): bool {
		if ( self::isWelcomePreviewRequested() ) {
			return true;
		}

		if ( $userId <= 0 ) {
			return false;
		}

		if ( self::hasDismissedWelcome( $userId ) ) {
			return false;
		}

		if ( self::userCourseCount( $userId ) > 0 ) {
			return false;
		}

		return true;
	}

	public static function shouldOfferGuidedFlow( int $userId ): bool {
		return self::isFirstRunComplete()
			&& ! self::isGuidedCourseComplete()
			&& 0 === self::userCourseCount( $userId )
			&& self::hasDismissedWelcome( $userId );
	}
}

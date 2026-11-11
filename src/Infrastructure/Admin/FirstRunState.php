<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

final class FirstRunState {

	public const OPTION_FIRST_RUN_COMPLETE     = 'mintlms_first_run_complete';
	public const OPTION_GUIDED_COURSE_COMPLETE = 'mintlms_guided_course_complete';

	public static function isFirstRunComplete(): bool {
		return (bool) get_option( self::OPTION_FIRST_RUN_COMPLETE, false );
	}

	public static function isGuidedCourseComplete(): bool {
		return (bool) get_option( self::OPTION_GUIDED_COURSE_COMPLETE, false );
	}

	public static function markFirstRunStarted(): void {
		update_option( self::OPTION_FIRST_RUN_COMPLETE, true, false );
	}

	public static function markOnboardingComplete(): void {
		update_option( self::OPTION_FIRST_RUN_COMPLETE, true, false );
		update_option( self::OPTION_GUIDED_COURSE_COMPLETE, true, false );
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

	public static function shouldOfferGuidedFlow( int $userId ): bool {
		return self::isFirstRunComplete()
			&& ! self::isGuidedCourseComplete()
			&& 0 === self::userCourseCount( $userId );
	}
}

<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Progress;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\LessonVideoProgressInterface;
use MintLMS\Infrastructure\PostType\PostTypes;

final class WpLessonVideoProgress implements LessonVideoProgressInterface {

	public const USER_META_PREFIX = 'mintlms_video_watched_';

	public function isRequired( int $lessonId ): bool {
		if ( $lessonId <= 0 ) {
			return false;
		}

		$raw = get_post_meta( $lessonId, PostTypes::META_LESSON_SETTINGS, true );

		return is_array( $raw ) && ! empty( $raw['videoProgression'] );
	}

	public function hasWatched( int $userId, int $lessonId ): bool {
		if ( $userId <= 0 || $lessonId <= 0 ) {
			return false;
		}

		return (bool) get_user_meta( $userId, self::USER_META_PREFIX . $lessonId, true );
	}

	public function markWatched( int $userId, int $lessonId ): void {
		if ( $userId <= 0 || $lessonId <= 0 ) {
			return;
		}

		update_user_meta( $userId, self::USER_META_PREFIX . $lessonId, 1 );
	}
}

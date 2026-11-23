<?php
declare(strict_types=1);

namespace MintLMS\Application\Progress;

defined( 'ABSPATH' ) || exit;

/**
 * Lesson-scoped nonce action for HTML5 video watch attestation.
 */
final class VideoWatchNonce {

	public static function action( int $lessonId ): string {
		return 'mintlms_video_watch_' . max( 0, $lessonId );
	}
}

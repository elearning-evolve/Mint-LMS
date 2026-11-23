<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Progress;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\LessonVideoProgressInterface;
use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Video watch attestation for lesson completion.
 *
 * "Must watch" is only enforceable for self-hosted HTML5 &lt;video&gt; URLs.
 * oEmbed providers (YouTube/Vimeo/etc.) cannot fire a reliable ended event, so
 * progression is not required for those embeds (avoids soft-locking completion).
 */
final class WpLessonVideoProgress implements LessonVideoProgressInterface {

	public const USER_META_PREFIX = 'mintlms_video_watched_';

	public function isRequired( int $lessonId ): bool {
		if ( $lessonId <= 0 || ! $this->settingEnabled( $lessonId ) ) {
			return false;
		}

		return $this->hasHtml5TrackableVideo( $lessonId );
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

	private function settingEnabled( int $lessonId ): bool {
		$raw = get_post_meta( $lessonId, PostTypes::META_LESSON_SETTINGS, true );

		return is_array( $raw ) && ! empty( $raw['videoProgression'] );
	}

	/**
	 * True when the lesson video would render as HTML5 &lt;video&gt; (same rule as
	 * ShortcodeRegistrar::videoEmbed — oEmbed wins when the provider matches).
	 */
	private function hasHtml5TrackableVideo( int $lessonId ): bool {
		$url = $this->resolveVideoUrl( $lessonId );

		if ( '' === $url ) {
			return false;
		}

		if ( function_exists( 'wp_oembed_get' ) ) {
			$embed = wp_oembed_get( $url );
			if ( is_string( $embed ) && '' !== $embed ) {
				return false;
			}
		}

		return true;
	}

	private function resolveVideoUrl( int $lessonId ): string {
		$meta = get_post_meta( $lessonId, PostTypes::META_VIDEO_URL, true );
		if ( is_string( $meta ) && '' !== trim( $meta ) ) {
			return trim( $meta );
		}

		$raw = get_post_meta( $lessonId, PostTypes::META_LESSON_SETTINGS, true );
		if ( ! is_array( $raw ) ) {
			return '';
		}

		$fromSettings = isset( $raw['videoUrl'] ) ? trim( (string) $raw['videoUrl'] ) : '';

		return $fromSettings;
	}
}

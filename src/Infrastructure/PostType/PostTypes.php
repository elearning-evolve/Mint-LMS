<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * Canonical post type slugs and postmeta keys for CPT-backed content.
 */
final class PostTypes {

	public const COURSE   = 'mint-course';
	public const LESSON   = 'mint-lesson';
	public const QUIZ     = 'mint-quiz';
	public const QUESTION = 'mint-question';

	public const STATUS_ARCHIVED = 'mint-archived';

	public const META_ENROLLMENT_TYPE       = '_mint_enrollment_type';
	public const META_COURSE_SETTINGS       = '_mint_course_settings';
	public const META_SECTION_ID            = '_mint_section_id';
	public const META_COURSE_ID             = '_mint_course_id';
	public const META_LESSON_ID             = '_mint_lesson_id';
	public const META_VIDEO_URL             = '_mint_video_url';
	public const META_ATTACHMENT_ID         = '_mint_attachment_id';
	public const META_IS_PREVIEW            = '_mint_is_preview';
	public const META_AVAILABLE_AFTER_DAYS  = '_mint_available_after_days';
	public const META_SORT_ORDER            = '_mint_sort_order';
	public const META_PASS_PERCENT          = '_mint_pass_percent';
	public const META_LESSON_SETTINGS       = '_mint_lesson_settings';
	public const META_QUIZ_SETTINGS         = '_mint_quiz_settings';
	public const META_QUESTION_SETTINGS     = '_mint_question_settings';
	public const META_QUESTION_SHELL        = '_mint_question_shell';
	public const META_LEGACY_ID             = '_mint_legacy_id';

	/**
	 * @return list<string>
	 */
	public static function all(): array {
		return array( self::COURSE, self::LESSON, self::QUIZ, self::QUESTION );
	}

	public static function isContentType( string $postType ): bool {
		return in_array( $postType, self::all(), true );
	}

	/**
	 * True once the author has used Save draft / Publish (not Gutenberg auto-draft).
	 */
	public static function hasExplicitSave( \WP_Post $post ): bool {
		return $post->ID > 0 && 'auto-draft' !== $post->post_status;
	}

	/**
	 * Native WordPress list-table URL for a Mint CPT.
	 */
	public static function listUrl( string $postType ): string {
		if ( ! in_array( $postType, self::all(), true ) ) {
			return admin_url( 'admin.php?page=mint-lms' );
		}

		return admin_url( 'edit.php?post_type=' . $postType );
	}

	/**
	 * Empty library lesson used only as a quiz/question host (not a real parent).
	 */
	public static function isDisposableHostLesson( int $lessonId ): bool {
		if ( $lessonId <= 0 ) {
			return false;
		}

		$post = get_post( $lessonId );
		if ( ! $post instanceof \WP_Post || self::LESSON !== $post->post_type ) {
			return false;
		}

		if ( (int) get_post_meta( $lessonId, self::META_COURSE_ID, true ) > 0 ) {
			return false;
		}

		if ( '' !== trim( wp_strip_all_tags( (string) $post->post_content ) ) ) {
			return false;
		}

		if ( '' !== trim( (string) get_post_meta( $lessonId, self::META_VIDEO_URL, true ) ) ) {
			return false;
		}

		if ( (int) get_post_meta( $lessonId, self::META_ATTACHMENT_ID, true ) > 0 ) {
			return false;
		}

		if ( (int) get_post_thumbnail_id( $lessonId ) > 0 ) {
			return false;
		}

		return true;
	}
}

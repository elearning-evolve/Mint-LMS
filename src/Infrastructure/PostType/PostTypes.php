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

	public const META_ENROLLMENT_TYPE      = '_mint_enrollment_type';
	public const META_COURSE_SETTINGS      = '_mint_course_settings';
	public const META_SECTION_ID           = '_mint_section_id';
	public const META_COURSE_ID            = '_mint_course_id';
	public const META_LESSON_ID            = '_mint_lesson_id';
	public const META_VIDEO_URL            = '_mint_video_url';
	public const META_ATTACHMENT_ID        = '_mint_attachment_id';
	public const META_IS_PREVIEW           = '_mint_is_preview';
	public const META_AVAILABLE_AFTER_DAYS = '_mint_available_after_days';
	public const META_SORT_ORDER           = '_mint_sort_order';
	public const META_PASS_PERCENT         = '_mint_pass_percent';
	public const META_LESSON_SETTINGS      = '_mint_lesson_settings';
	public const META_QUIZ_SETTINGS        = '_mint_quiz_settings';
	public const META_QUESTION_SETTINGS    = '_mint_question_settings';
	public const META_QUESTION_SHELL       = '_mint_question_shell';
	public const META_DISPOSABLE_HOST      = '_mint_disposable_host';
	public const META_LEGACY_ID            = '_mint_legacy_id';

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
	 * Empty scaffolding lesson used only as a quiz/question host (not a real Attach parent).
	 *
	 * Author-created library lessons (even with empty content) must NOT match — otherwise the
	 * quiz "Attach to lesson" picker hides them or falls back to the host title "New Lesson".
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

		if ( '1' === (string) get_post_meta( $lessonId, self::META_DISPOSABLE_HOST, true ) ) {
			return true;
		}

		// Legacy hosts (pre-meta): empty scaffolding title/slug ONLY when a quiz
		// still points at this lesson. Author library drafts titled "New Lesson"
		// (courseId 0, no quiz) must stay selectable in Add Existing.
		$defaultTitle  = __( 'New Lesson', 'mint-lms' );
		$title         = trim( (string) $post->post_title );
		$slug          = (string) $post->post_name;
		$looksLikeHost = ( $title === $defaultTitle ) || ( 0 === strpos( $slug, 'new-lesson-' ) );

		if ( ! $looksLikeHost ) {
			return false;
		}

		return self::lessonIsReferencedByQuiz( $lessonId );
	}

	/**
	 * True when at least one quiz still uses this lesson as its host parent.
	 */
	private static function lessonIsReferencedByQuiz( int $lessonId ): bool {
		if ( $lessonId <= 0 ) {
			return false;
		}

		$query = new \WP_Query(
			array(
				'post_type'              => self::QUIZ,
				'post_status'            => array( 'publish', 'draft', 'private', self::STATUS_ARCHIVED, 'trash' ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => self::META_LESSON_ID,
						'value' => $lessonId,
						'type'  => 'NUMERIC',
					),
				),
			)
		);

		return array() !== $query->posts;
	}

	/**
	 * Mark a lesson as an internal quiz/question host (Attach picker must hide it).
	 */
	public static function markDisposableHostLesson( int $lessonId ): void {
		if ( $lessonId <= 0 ) {
			return;
		}

		update_post_meta( $lessonId, self::META_DISPOSABLE_HOST, '1' );
	}

	/**
	 * Author claimed this lesson (edited in builder) — no longer an internal host.
	 */
	public static function clearDisposableHostLesson( int $lessonId ): void {
		if ( $lessonId <= 0 ) {
			return;
		}

		delete_post_meta( $lessonId, self::META_DISPOSABLE_HOST );
	}

	/**
	 * Builder question name for contents lists (created title, not the prompt body).
	 */
	public static function questionContentsTitle( int $questionId, string $promptFallback = '' ): string {
		if ( $questionId > 0 ) {
			$raw     = get_post_meta( $questionId, self::META_QUESTION_SETTINGS, true );
			$display = is_array( $raw ) ? sanitize_text_field( (string) ( $raw['displayTitle'] ?? '' ) ) : '';
			if ( '' !== $display ) {
				return $display;
			}

			$post = get_post( $questionId );
			if ( $post instanceof \WP_Post ) {
				$title = trim( (string) $post->post_title );
				if ( '' !== $title ) {
					return $title;
				}
			}
		}

		$prompt = trim( wp_strip_all_tags( $promptFallback ) );

		return '' !== $prompt ? $prompt : __( 'Untitled question', 'mint-lms' );
	}
}

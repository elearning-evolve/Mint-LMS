<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Native WP lesson editor (Gutenberg / classic) ↔ Mint lesson builder.
 *
 * Default Add New / Edit surface is WordPress. Mint builder opens via sidebar link.
 */
final class LessonPostEditUi {

	public function register(): void {
		add_filter( 'use_block_editor_for_post_type', array( $this, 'enableBlockEditor' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'registerBuilderMetaBox' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'renderSubmitBoxLink' ) );
	}

	/**
	 * @param bool   $use_block_editor Whether the post type uses the block editor.
	 * @param string $post_type        Post type slug.
	 */
	public function enableBlockEditor( bool $use_block_editor, string $post_type ): bool {
		if ( PostTypes::LESSON === $post_type ) {
			return true;
		}

		return $use_block_editor;
	}

	public function registerBuilderMetaBox(): void {
		add_meta_box(
			'mintlms-lesson-builder-link',
			__( 'Mint LMS', 'mint-lms' ),
			array( $this, 'renderBuilderMetaBox' ),
			PostTypes::LESSON,
			'side',
			'high'
		);
	}

	/**
	 * @param \WP_Post $post Post being edited.
	 */
	public function renderBuilderMetaBox( \WP_Post $post ): void {
		NativeBuilderGate::render(
			$post,
			array(
				'pending' => __( 'Save the lesson as a draft or publish it first, then open it in Mint LMS Builder.', 'mint-lms' ),
				'label'   => __( 'Open Lesson In Mint LMS Builder', 'mint-lms' ),
				'url'     => self::lessonBuilderUrl( (int) $post->ID ),
			)
		);
	}

	public function renderSubmitBoxLink( \WP_Post $post ): void {
		if ( PostTypes::LESSON !== $post->post_type || $post->ID <= 0 ) {
			return;
		}

		echo '<div class="misc-pub-section mint-lms-lesson-submitbox">';
		NativeBuilderGate::render(
			$post,
			array(
				'pending' => __( 'Save the lesson as a draft or publish it first, then open it in Mint LMS Builder.', 'mint-lms' ),
				'label'   => __( 'Open Lesson In Mint LMS Builder', 'mint-lms' ),
				'url'     => self::lessonBuilderUrl( (int) $post->ID ),
			)
		);
		echo '</div>';
	}

	/**
	 * Canonical Mint URL for editing a lesson in the custom builder.
	 */
	public static function lessonBuilderUrl( int $lessonId ): string {
		if ( $lessonId <= 0 ) {
			return PostTypes::listUrl( PostTypes::LESSON );
		}

		$courseId = (int) get_post_meta( $lessonId, PostTypes::META_COURSE_ID, true );

		if ( $courseId <= 0 ) {
			return admin_url(
				'admin.php?page=mint-lms-lesson-edit&lesson_id=' . $lessonId . '&from=lessons'
			);
		}

		return admin_url(
			'admin.php?page=mint-lms-builder&course_id=' . $courseId
			. '&lesson_id=' . $lessonId
			. '&from=lessons'
		);
	}
}

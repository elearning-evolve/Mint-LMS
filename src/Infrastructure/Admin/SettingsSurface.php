<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Gates admin settings UI to surfaces that are actually enforced or consumed.
 *
 * Incomplete toggles stay persisted for forward-compat but are hidden so authors
 * cannot enable controls that do nothing (WP plugin / LMS hygiene).
 */
final class SettingsSurface {

	/**
	 * Feature keys → whether the control may appear in admin UI.
	 *
	 * @var array<string, bool>
	 */
	private const FEATURES = array(
		// Course.
		'course.pagination'          => false,
		'course.completion_redirect' => false,
		'course.points_on_complete'  => false,
		'course.student_complete'    => true,

		// Lesson.
		'lesson.materials'           => true,
		'lesson.video_progression'   => true,
		'lesson.video_controls'      => true,
		'lesson.video_autoplay'      => true,
		'lesson.video_pause_hidden'  => false,
		'lesson.video_resume'        => false,
		'lesson.assignment_uploads'  => false,
		'lesson.min_time'            => false,
		'lesson.sample'              => true,
		'lesson.release_immediate'   => true,
		'lesson.release_enrollment'  => true,
		'lesson.release_date'        => false,
		'lesson.external'            => false,

		// Quiz access / unfinished run-results.
		'quiz.access_card'           => false,
		'quiz.certificate'           => false,
		'quiz.autostart'             => false,
		'quiz.question_overview'     => false,
		'quiz.quiz_summary'          => false,
		'quiz.custom_ordering'       => false,
		'quiz.sort_by_category'      => false,
		'quiz.custom_elements'       => false,
		'quiz.result_messages'       => false,
		'quiz.show_average_score'    => false,
		'quiz.show_category_score'   => false,

		// Quiz used surfaces.
		'quiz.passing_score'         => true,
		'quiz.saving'                => true,
		'quiz.retakes'               => true,
		'quiz.time_limit'            => true,
		'quiz.question_display'      => true,
		'quiz.randomize_order'       => true,
		'quiz.randomize_answers'     => true,
		'quiz.skip_question'         => true,
		'quiz.show_title'            => true,
		'quiz.materials'             => true,
		'quiz.restart'               => true,
		'quiz.custom_results'        => true,
		'quiz.answer_feedback'       => true,

		// Question.
		'question.allow_html'        => false,
		'question.free_preview'      => false,
	);

	public static function enabled( string $feature ): bool {
		return ! empty( self::FEATURES[ $feature ] );
	}

	/**
	 * @return array<string, bool>
	 */
	public static function all(): array {
		return self::FEATURES;
	}
}

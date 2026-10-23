<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Strings for Alpine/JS admin UI (wp_localize_script → mintLmsAdmin.i18n).
 */
final class AdminI18n {

	/**
	 * @return array<string, string>
	 */
	public static function strings(): array {
		return array(
			// Common.
			'untitledCourse'       => __( 'Untitled course', 'mint-lms' ),
			'untitledCourseAlt'    => __( 'Untitled Course', 'mint-lms' ),
			'untitledLesson'       => __( 'Untitled lesson', 'mint-lms' ),
			'untitledLessonGroup'  => __( 'Untitled lesson group', 'mint-lms' ),
			'untitledQuiz'         => __( 'Untitled quiz', 'mint-lms' ),
			'untitledQuestion'     => __( 'Untitled question', 'mint-lms' ),
			'untitled'             => __( 'Untitled', 'mint-lms' ),
			'backToCourse'         => __( 'Back to course', 'mint-lms' ),
			'backToCourses'        => __( 'Back to courses', 'mint-lms' ),
			'backToQuestions'      => __( 'Back to questions', 'mint-lms' ),
			'backToQuizzes'        => __( 'Back to quizzes', 'mint-lms' ),
			'backToLessons'        => __( 'Back to lessons', 'mint-lms' ),
			'contents'             => __( 'Contents', 'mint-lms' ),
			'course'               => __( 'Course', 'mint-lms' ),
			'lesson'               => __( 'Lesson', 'mint-lms' ),
			'quiz'                 => __( 'Quiz', 'mint-lms' ),
			'question'             => __( 'Question', 'mint-lms' ),
			'newQuiz'              => __( 'New Quiz', 'mint-lms' ),
			'newQuestion'          => __( 'New Question', 'mint-lms' ),
			'newLesson'            => __( 'New Lesson', 'mint-lms' ),
			'newLessonGroup'       => __( 'New Lesson Group', 'mint-lms' ),
			'save'                 => __( 'Save', 'mint-lms' ),
			'saving'               => __( 'Saving…', 'mint-lms' ),
			'adding'               => __( 'Adding…', 'mint-lms' ),
			'addToQuiz'            => __( 'Add to quiz', 'mint-lms' ),
			'addToLesson'          => __( 'Add to lesson', 'mint-lms' ),
			'addToCourse'          => __( 'Add to course', 'mint-lms' ),
			'addToLessonGroup'     => __( 'Add to lesson group', 'mint-lms' ),
			'noContentYet'         => __( 'No content yet', 'mint-lms' ),
			'noLessonGroup'        => __( 'No lesson group', 'mint-lms' ),
			'noAttachment'         => __( 'No attachment', 'mint-lms' ),
			'enterATitle'          => __( 'Enter a title', 'mint-lms' ),
			'questionTitle'        => __( 'Question title', 'mint-lms' ),
			'quizTitle'            => __( 'Quiz title', 'mint-lms' ),
			'option'               => __( 'Option', 'mint-lms' ),
			'writeYourQuestion'    => __( 'Write your question…', 'mint-lms' ),
			'questionPrompt'       => __( 'Question prompt…', 'mint-lms' ),
			'saveQuestion'         => __( 'Save question', 'mint-lms' ),
			'searchLessonGroups'   => __( 'Search lesson groups…', 'mint-lms' ),
			'searchLessons'        => __( 'Search lessons…', 'mint-lms' ),
			'searchQuizzes'        => __( 'Search quizzes…', 'mint-lms' ),
			'searchQuestions'      => __( 'Search questions…', 'mint-lms' ),
			'everyQuizInLibrary'   => __( 'Every quiz in your library.', 'mint-lms' ),
			'everyQuizInCourse'    => __( 'Every quiz in this course, across all lessons.', 'mint-lms' ),
			'selectAQuiz'          => __( 'Select a quiz…', 'mint-lms' ),
			'selectALesson'        => __( 'Select a lesson…', 'mint-lms' ),
			'selectACourse'        => __( 'Select a course…', 'mint-lms' ),
			'outOf'                => __( 'out of', 'mint-lms' ),
			'questions'            => __( 'questions', 'mint-lms' ),

			// Course settings.
			'updateProduct'        => __( 'Update product', 'mint-lms' ),
			'createProduct'        => __( 'Create product', 'mint-lms' ),
			'paginationOn'         => __( 'Lesson lists are split into pages', 'mint-lms' ),
			'paginationOff'        => __( 'Show everything on one page', 'mint-lms' ),
			'accessEndsAfterDays'  => __( 'Access ends after a set number of days', 'mint-lms' ),
			'accessNoTimeLimit'    => __( 'Students keep access with no time limit', 'mint-lms' ),
			'materialsPlaceholder' => __( 'Write notes, links or downloads students should see…', 'mint-lms' ),
			'completionRedirectPh' => __( 'Leave empty to stay on the course', 'mint-lms' ),
			'bold'                 => __( 'Bold', 'mint-lms' ),
			'italic'               => __( 'Italic', 'mint-lms' ),
			'bulletList'           => __( 'Bullet list', 'mint-lms' ),
			'numberedList'         => __( 'Numbered list', 'mint-lms' ),
			'link'                 => __( 'Link', 'mint-lms' ),
			'noCertificate'        => __( 'No certificate', 'mint-lms' ),
			'completionCertificate'=> __( 'Course completion certificate', 'mint-lms' ),
		);
	}
}

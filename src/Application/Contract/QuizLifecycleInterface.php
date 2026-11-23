<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

defined( 'ABSPATH' ) || exit;

interface QuizLifecycleInterface {

	public function onLessonDeleted( int $lessonId ): void;

	/**
	 * Soft-delete quizzes (and their questions) when a lesson is moved to trash.
	 */
	public function onLessonTrashed( int $lessonId ): void;
}

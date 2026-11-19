<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

interface QuizLifecycleInterface {

	public function onLessonDeleted( int $lessonId ): void;
}

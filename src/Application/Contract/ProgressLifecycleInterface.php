<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

interface ProgressLifecycleInterface {

	public function onLessonDeleted( int $lessonId ): void;
}

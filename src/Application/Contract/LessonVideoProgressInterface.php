<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

interface LessonVideoProgressInterface {

	public function isRequired( int $lessonId ): bool;

	public function hasWatched( int $userId, int $lessonId ): bool;

	public function markWatched( int $userId, int $lessonId ): void;
}

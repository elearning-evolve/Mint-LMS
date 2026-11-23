<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

defined( 'ABSPATH' ) || exit;

interface ProgressLifecycleInterface {

	public function onLessonDeleted( int $lessonId ): void;
}

<?php
declare(strict_types=1);

namespace MintLMS\Application\Lesson\Dto;

defined( 'ABSPATH' ) || exit;

final readonly class ReorderLessonsDto {

	/**
	 * @param list<int> $lessonIds
	 */
	public function __construct(
		public array $lessonIds,
	) {
	}
}

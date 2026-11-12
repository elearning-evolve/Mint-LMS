<?php
declare(strict_types=1);

namespace MintLMS\Application\Course;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Course\Dto\CourseStructureDto;
use MintLMS\Application\Course\Dto\CourseStructureLessonDto;
use MintLMS\Application\Course\Dto\CourseStructureSectionDto;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Lesson\Lesson;
use MintLMS\Domain\Section\Section;
use MintLMS\Domain\Section\SectionRepositoryInterface;

final class CourseStructureService {

	public function __construct(
		private CourseRepositoryInterface $courseRepository,
		private SectionRepositoryInterface $sectionRepository,
		private AuthorizationInterface $authorization,
	) {
	}

	public function getStructure( int $courseId, int $userId ): CourseStructureDto {
		$course = $this->courseRepository->findById( $courseId );

		if ( null === $course ) {
			throw new NotFoundException( 'Course not found.' );
		}

		if ( ! $this->authorization->canViewCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$rows = $this->sectionRepository->loadStructureRows( $courseId );

		return $this->buildStructureDto( $course, $rows );
	}

	public function getPublishedStructure( int $courseId ): CourseStructureDto {
		$course = $this->courseRepository->findById( $courseId );

		if ( null === $course || CourseStatus::Published !== $course->status ) {
			throw new NotFoundException( 'Course not found.' );
		}

		$rows = $this->sectionRepository->loadStructureRows( $courseId, true );

		return $this->buildStructureDto( $course, $rows );
	}

	/**
	 * @param list<array{section: Section, lesson: ?Lesson}> $rows
	 */
	private function buildStructureDto( \MintLMS\Domain\Course\Course $course, array $rows ): CourseStructureDto {
		/** @var array<int, CourseStructureSectionDto> $sectionsById */
		$sectionsById = array();

		foreach ( $rows as $row ) {
			$section = $row['section'];
			$lesson  = $row['lesson'];

			if ( ! isset( $sectionsById[ $section->id ] ) ) {
				$sectionsById[ $section->id ] = new CourseStructureSectionDto(
					$section->id,
					$section->title,
					$section->sortOrder,
					$section->createdAt->format( 'c' ),
					array(),
				);
			}

			if ( null === $lesson ) {
				continue;
			}

			$existing  = $sectionsById[ $section->id ];
			$lessons   = $existing->lessons;
			$lessons[] = new CourseStructureLessonDto(
				$lesson->id,
				$lesson->sectionId,
				$lesson->title,
				$lesson->slug,
				$lesson->content,
				$lesson->videoUrl,
				$lesson->attachmentId,
				$lesson->isPreview,
				$lesson->availableAfterDays,
				false,
				null,
				$lesson->sortOrder,
				$lesson->createdAt->format( 'c' ),
				$lesson->updatedAt->format( 'c' ),
				$lesson->featuredImageId,
				'',
			);

			$sectionsById[ $section->id ] = new CourseStructureSectionDto(
				$existing->id,
				$existing->title,
				$existing->sortOrder,
				$existing->createdAt,
				$lessons,
			);
		}

		$sections = array_values( $sectionsById );

		usort(
			$sections,
			static function ( CourseStructureSectionDto $a, CourseStructureSectionDto $b ): int {
				if ( 0 === $a->id && 0 !== $b->id ) {
					return -1;
				}
				if ( 0 !== $a->id && 0 === $b->id ) {
					return 1;
				}

				return $a->sortOrder <=> $b->sortOrder;
			}
		);

		foreach ( $sections as $index => $section ) {
			$lessons = $section->lessons;
			usort(
				$lessons,
				static fn( CourseStructureLessonDto $a, CourseStructureLessonDto $b ): int => $a->sortOrder <=> $b->sortOrder
			);
			$sections[ $index ] = new CourseStructureSectionDto(
				$section->id,
				$section->title,
				$section->sortOrder,
				$section->createdAt,
				$lessons,
			);
		}

		return new CourseStructureDto(
			$course->id,
			$course->title,
			$course->slug,
			$course->status->value,
			$sections,
		);
	}
}

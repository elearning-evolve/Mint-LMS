<?php
declare(strict_types=1);

namespace MintLMS\Application\Section;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Application\Section\Dto\CreateSectionDto;
use MintLMS\Application\Section\Dto\ReorderSectionsDto;
use MintLMS\Application\Section\Dto\SectionDto;
use MintLMS\Application\Section\Dto\UpdateSectionDto;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Lesson\LessonRepositoryInterface;
use MintLMS\Domain\Section\Section;
use MintLMS\Domain\Section\SectionRepositoryInterface;
use MintLMS\Domain\Shared\Clock;

final class SectionService {

	public function __construct(
		private SectionRepositoryInterface $sectionRepository,
		private LessonRepositoryInterface $lessonRepository,
		private CourseRepositoryInterface $courseRepository,
		private AuthorizationInterface $authorization,
		private Clock $clock,
	) {
	}

	public function create( int $courseId, CreateSectionDto $dto, int $userId ): SectionDto {
		$course = $this->findCourseOrFail( $courseId );

		if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$title = trim( $dto->title );
		if ( '' === $title ) {
			throw new ValidationException( 'Validation failed.', array( 'title' => 'Title is required.' ) );
		}

		$section = new Section(
			0,
			$courseId,
			$title,
			$this->sectionRepository->nextSortOrder( $courseId ),
			$this->clock->now(),
		);

		return SectionDto::fromSection( $this->sectionRepository->save( $section ) );
	}

	public function update( int $id, UpdateSectionDto $dto, int $userId ): SectionDto {
		$section = $this->findSectionOrFail( $id );
		$course  = $this->findCourseOrFail( $section->courseId );

		if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$title = null !== $dto->title ? trim( $dto->title ) : $section->title;

		if ( '' === $title ) {
			throw new ValidationException( 'Validation failed.', array( 'title' => 'Title is required.' ) );
		}

		$updated = new Section(
			$section->id,
			$section->courseId,
			$title,
			$section->sortOrder,
			$section->createdAt,
		);

		return SectionDto::fromSection( $this->sectionRepository->save( $updated ) );
	}

	public function delete( int $id, int $userId ): void {
		$section = $this->findSectionOrFail( $id );
		$course  = $this->findCourseOrFail( $section->courseId );

		if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$this->lessonRepository->deleteBySectionId( $section->id );
		$this->sectionRepository->delete( $section->id );
	}

	public function reorder( int $courseId, ReorderSectionsDto $dto, int $userId ): void {
		$course = $this->findCourseOrFail( $courseId );

		if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		if ( array() === $dto->sectionIds ) {
			throw new ValidationException( 'Validation failed.', array( 'ids' => 'At least one lesson group ID is required.' ) );
		}

		$sections = $this->sectionRepository->findByCourseId( $courseId );

		if ( count( $sections ) !== count( $dto->sectionIds ) ) {
			throw new ValidationException(
				'Validation failed.',
				array( 'ids' => 'Lesson group IDs must include every lesson group in the course.' )
			);
		}

		$existingIds = array_map(
			static fn( Section $section ): int => $section->id,
			$sections
		);

		foreach ( $dto->sectionIds as $sectionId ) {
			if ( ! in_array( $sectionId, $existingIds, true ) ) {
				throw new ValidationException(
					'Validation failed.',
					array( 'ids' => 'One or more lesson group IDs do not belong to this course.' )
				);
			}
		}

		if ( count( array_unique( $dto->sectionIds ) ) !== count( $dto->sectionIds ) ) {
			throw new ValidationException( 'Validation failed.', array( 'ids' => 'Lesson group IDs must be unique.' ) );
		}

		$this->sectionRepository->reorder( $courseId, $dto->sectionIds );
	}

	private function findSectionOrFail( int $id ): Section {
		$section = $this->sectionRepository->findById( $id );

		if ( null === $section ) {
			throw new NotFoundException( 'Lesson group not found.' );
		}

		return $section;
	}

	private function findCourseOrFail( int $courseId ): \MintLMS\Domain\Course\Course {
		$course = $this->courseRepository->findById( $courseId );

		if ( null === $course ) {
			throw new NotFoundException( 'Course not found.' );
		}

		return $course;
	}
}

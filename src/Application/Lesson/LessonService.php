<?php
declare(strict_types=1);

namespace MintLMS\Application\Lesson;

use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Contract\ProgressLifecycleInterface;
use MintLMS\Application\Quiz\QuizService;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Application\Lesson\Dto\CreateLessonDto;
use MintLMS\Application\Lesson\Dto\LessonDto;
use MintLMS\Application\Lesson\Dto\ReorderLessonsDto;
use MintLMS\Application\Lesson\Dto\UpdateLessonDto;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Lesson\Lesson;
use MintLMS\Domain\Lesson\LessonRepositoryInterface;
use MintLMS\Domain\Section\SectionRepositoryInterface;
use MintLMS\Domain\Shared\Clock;

final class LessonService {

	public function __construct(
		private LessonRepositoryInterface $lessonRepository,
		private SectionRepositoryInterface $sectionRepository,
		private CourseRepositoryInterface $courseRepository,
		private AuthorizationInterface $authorization,
		private Clock $clock,
		private ProgressLifecycleInterface $progressLifecycle,
		private ?QuizService $quizService = null,
	) {
	}

	public function create( int $sectionId, CreateLessonDto $dto, int $userId ): LessonDto {
		$section = $this->findSectionOrFail( $sectionId );
		$course  = $this->findCourseOrFail( $section->courseId );

		if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$title = trim( $dto->title );
		if ( '' === $title ) {
			throw new ValidationException( 'Validation failed.', array( 'title' => 'Title is required.' ) );
		}

		$slug = null !== $dto->slug ? $this->normalizeSlug( $dto->slug ) : $this->generateUniqueSlug( $title, $section->courseId );
		$this->assertValidSlug( $slug );

		if ( null !== $this->lessonRepository->findBySlugAndCourseId( $slug, $section->courseId ) ) {
			throw new ValidationException( 'Validation failed.', array( 'slug' => 'Slug is already in use in this course.' ) );
		}

		$now = $this->clock->now();

		$lesson = new Lesson(
			0,
			$sectionId,
			$section->courseId,
			$title,
			$slug,
			$dto->content,
			$dto->videoUrl,
			$dto->attachmentId,
			$dto->isPreview,
			null,
			$this->lessonRepository->nextSortOrder( $sectionId, $section->courseId ),
			$now,
			$now,
			null,
		);

		return LessonDto::fromLesson( $this->lessonRepository->save( $lesson ) );
	}

	/**
	 * Create a lesson on a course. sectionId 0 / null = ungrouped (no section required).
	 */
	public function createForCourse( int $courseId, CreateLessonDto $dto, int $userId, ?int $sectionId = null ): LessonDto {
		$course = $this->findCourseOrFail( $courseId );

		if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$resolvedSectionId = null !== $sectionId && $sectionId > 0 ? $sectionId : 0;

		if ( $resolvedSectionId > 0 ) {
			$section = $this->findSectionOrFail( $resolvedSectionId );
			if ( $section->courseId !== $courseId ) {
				throw new ValidationException(
					'Validation failed.',
					array( 'section_id' => 'Lesson group does not belong to this course.' )
				);
			}
		}

		$title = trim( $dto->title );
		if ( '' === $title ) {
			$title = __( 'New Lesson', 'mint-lms' );
		}

		$slug = null !== $dto->slug ? $this->normalizeSlug( $dto->slug ) : $this->generateUniqueSlug( $title, $courseId );
		$this->assertValidSlug( $slug );

		if ( null !== $this->lessonRepository->findBySlugAndCourseId( $slug, $courseId ) ) {
			$slug = $this->generateUniqueSlug( $title, $courseId );
		}

		$now = $this->clock->now();

		$lesson = new Lesson(
			0,
			$resolvedSectionId,
			$courseId,
			$title,
			$slug,
			$dto->content,
			$dto->videoUrl,
			$dto->attachmentId,
			$dto->isPreview,
			null,
			$this->lessonRepository->nextSortOrder( $resolvedSectionId, $courseId ),
			$now,
			$now,
			null,
		);

		return LessonDto::fromLesson( $this->lessonRepository->save( $lesson ) );
	}

	/**
	 * Library lesson — not attached to any course until the author adds it.
	 */
	public function createStandalone( CreateLessonDto $dto, int $userId ): LessonDto {
		if ( ! $this->authorization->canCreateCourse( $userId ) ) {
			throw new ForbiddenException();
		}

		$title = trim( $dto->title );
		if ( '' === $title ) {
			$title = __( 'New Lesson', 'mint-lms' );
		}

		$slug = null !== $dto->slug ? $this->normalizeSlug( $dto->slug ) : $this->generateUniqueSlug( $title, 0 );
		$this->assertValidSlug( $slug );

		if ( null !== $this->lessonRepository->findBySlugAndCourseId( $slug, 0 ) ) {
			$slug = $this->generateUniqueSlug( $title, 0 );
		}

		$now = $this->clock->now();

		$lesson = new Lesson(
			0,
			0,
			0,
			$title,
			$slug,
			$dto->content,
			$dto->videoUrl,
			$dto->attachmentId,
			$dto->isPreview,
			null,
			0,
			$now,
			$now,
			null,
		);

		return LessonDto::fromLesson( $this->lessonRepository->save( $lesson ) );
	}

	/**
	 * Attach a library lesson to a course.
	 * Pass sectionId null/0 to keep the lesson ungrouped (no auto-created section).
	 */
	public function attachToCourse( int $lessonId, int $courseId, ?int $sectionId, int $userId ): LessonDto {
		$lesson = $this->findLessonOrFail( $lessonId );
		$this->assertCanManageLesson( $userId, $lesson );

		if ( $lesson->courseId > 0 && $lesson->courseId !== $courseId ) {
			$previousCourse = $this->findCourseOrFail( $lesson->courseId );
			if ( ! $this->authorization->canEditCourse( $userId, $previousCourse->authorId ) ) {
				throw new ForbiddenException();
			}
		}

		$course = $this->findCourseOrFail( $courseId );

		if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$resolvedSectionId = 0;
		if ( null !== $sectionId && $sectionId > 0 ) {
			$section = $this->findSectionOrFail( $sectionId );
			if ( $section->courseId !== $courseId ) {
				throw new ValidationException(
					'Validation failed.',
					array( 'section_id' => 'Lesson group does not belong to this course.' )
				);
			}
			$resolvedSectionId = $section->id;
		}

		if ( $lesson->courseId === $courseId && $lesson->sectionId === $resolvedSectionId ) {
			return LessonDto::fromLesson( $lesson );
		}

		$slug = $lesson->slug;
		if ( null !== $this->lessonRepository->findBySlugAndCourseId( $slug, $courseId ) ) {
			$existing = $this->lessonRepository->findBySlugAndCourseId( $slug, $courseId );
			if ( null === $existing || $existing->id !== $lesson->id ) {
				$slug = $this->generateUniqueSlug( $lesson->title, $courseId );
			}
		}

		$updated = new Lesson(
			$lesson->id,
			$resolvedSectionId,
			$courseId,
			$lesson->title,
			$slug,
			$lesson->content,
			$lesson->videoUrl,
			$lesson->attachmentId,
			$lesson->isPreview,
			$lesson->availableAfterDays,
			$this->lessonRepository->nextSortOrder( $resolvedSectionId, $courseId ),
			$lesson->createdAt,
			$this->clock->now(),
			$lesson->featuredImageId,
		);

		$saved = $this->lessonRepository->save( $updated );

		if ( null !== $this->quizService ) {
			$this->quizService->syncCourseIdForLesson( $saved->id, $courseId );
		}

		return LessonDto::fromLesson( $saved );
	}

	/**
	 * Detach a lesson from its course — returns it to the standalone library.
	 */
	public function detachFromCourse( int $lessonId, int $userId ): LessonDto {
		$lesson = $this->findLessonOrFail( $lessonId );
		$this->assertCanManageLesson( $userId, $lesson );

		if ( $lesson->courseId <= 0 ) {
			return LessonDto::fromLesson( $lesson );
		}

		$slug = $lesson->slug;
		if ( null !== $this->lessonRepository->findBySlugAndCourseId( $slug, 0 ) ) {
			$existing = $this->lessonRepository->findBySlugAndCourseId( $slug, 0 );
			if ( null === $existing || $existing->id !== $lesson->id ) {
				$slug = $this->generateUniqueSlug( $lesson->title, 0 );
			}
		}

		$updated = new Lesson(
			$lesson->id,
			0,
			0,
			$lesson->title,
			$slug,
			$lesson->content,
			$lesson->videoUrl,
			$lesson->attachmentId,
			$lesson->isPreview,
			$lesson->availableAfterDays,
			0,
			$lesson->createdAt,
			$this->clock->now(),
			$lesson->featuredImageId,
		);

		$saved = $this->lessonRepository->save( $updated );

		if ( null !== $this->quizService ) {
			$this->quizService->syncCourseIdForLesson( $saved->id, 0 );
		}

		return LessonDto::fromLesson( $saved );
	}

	public function update( int $id, UpdateLessonDto $dto, int $userId ): LessonDto {
		$lesson = $this->findLessonOrFail( $id );
		$this->assertCanManageLesson( $userId, $lesson );

		$title      = null !== $dto->title ? trim( $dto->title ) : $lesson->title;
		$slug       = null !== $dto->slug ? $this->normalizeSlug( $dto->slug ) : $lesson->slug;
		$content    = null !== $dto->content ? $dto->content : $lesson->content;
		$videoUrl   = null !== $dto->videoUrl ? $dto->videoUrl : $lesson->videoUrl;
		$attachment = null !== $dto->attachmentId ? $dto->attachmentId : $lesson->attachmentId;
		$isPreview          = null !== $dto->isPreview ? $dto->isPreview : $lesson->isPreview;
		$availableAfterDays = $dto->hasAvailableAfterDays
			? $this->normalizeAvailableAfterDays( $dto->availableAfterDays )
			: $lesson->availableAfterDays;
		$featuredImageId = $dto->updateFeaturedImage ? $dto->featuredImageId : $lesson->featuredImageId;

		if ( '' === $title ) {
			throw new ValidationException( 'Validation failed.', array( 'title' => 'Title is required.' ) );
		}

		$this->assertValidSlug( $slug );

		if ( $slug !== $lesson->slug ) {
			$existing = $this->lessonRepository->findBySlugAndCourseId( $slug, $lesson->courseId );
			if ( null !== $existing && $existing->id !== $lesson->id ) {
				throw new ValidationException( 'Validation failed.', array( 'slug' => 'Slug is already in use in this course.' ) );
			}
		}

		$updated = new Lesson(
			$lesson->id,
			$lesson->sectionId,
			$lesson->courseId,
			$title,
			$slug,
			$content,
			$videoUrl,
			$attachment,
			$isPreview,
			$availableAfterDays,
			$lesson->sortOrder,
			$lesson->createdAt,
			$this->clock->now(),
			$featuredImageId,
		);

		return LessonDto::fromLesson( $this->lessonRepository->save( $updated ) );
	}

	public function delete( int $id, int $userId ): void {
		$lesson = $this->findLessonOrFail( $id );
		$this->assertCanManageLesson( $userId, $lesson );

		$this->progressLifecycle->onLessonDeleted( $lesson->id );

		if ( null !== $this->quizService ) {
			$this->quizService->onLessonDeleted( $lesson->id );
		}

		$this->lessonRepository->delete( $lesson->id );
	}

	public function get( int $id, int $userId ): LessonDto {
		$lesson = $this->findLessonOrFail( $id );
		$this->assertCanViewLesson( $userId, $lesson );

		return LessonDto::fromLesson( $lesson );
	}

	public function reorder( int $sectionId, ReorderLessonsDto $dto, int $userId, ?int $courseId = null ): void {
		if ( 0 === $sectionId ) {
			if ( null === $courseId || $courseId <= 0 ) {
				throw new ValidationException(
					'Validation failed.',
					array( 'course_id' => 'Course ID is required when reordering ungrouped lessons.' )
				);
			}
			$course = $this->findCourseOrFail( $courseId );
			if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
				throw new ForbiddenException();
			}
			$lessons = $this->lessonRepository->findByCourseIdAndSectionId( $courseId, 0 );
		} else {
			$section = $this->findSectionOrFail( $sectionId );
			$course  = $this->findCourseOrFail( $section->courseId );

			if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
				throw new ForbiddenException();
			}

			$lessons = $this->lessonRepository->findByCourseIdAndSectionId( $section->courseId, $sectionId );
		}

		if ( array() === $dto->lessonIds ) {
			throw new ValidationException( 'Validation failed.', array( 'ids' => 'At least one lesson ID is required.' ) );
		}

		if ( count( $lessons ) !== count( $dto->lessonIds ) ) {
			throw new ValidationException(
				'Validation failed.',
				array( 'ids' => 'Lesson IDs must include every lesson in the lesson group.' )
			);
		}

		$existingIds = array_map(
			static fn( Lesson $lesson ): int => $lesson->id,
			$lessons
		);

		foreach ( $dto->lessonIds as $lessonId ) {
			if ( ! in_array( $lessonId, $existingIds, true ) ) {
				throw new ValidationException(
					'Validation failed.',
					array( 'ids' => 'One or more lesson IDs do not belong to this lesson group.' )
				);
			}
		}

		if ( count( array_unique( $dto->lessonIds ) ) !== count( $dto->lessonIds ) ) {
			throw new ValidationException( 'Validation failed.', array( 'ids' => 'Lesson IDs must be unique.' ) );
		}

		$this->lessonRepository->reorder( $sectionId, $dto->lessonIds );
	}

	private function findLessonOrFail( int $id ): Lesson {
		$lesson = $this->lessonRepository->findById( $id );

		if ( null === $lesson ) {
			throw new NotFoundException( 'Lesson not found.' );
		}

		return $lesson;
	}

	private function findSectionOrFail( int $sectionId ): \MintLMS\Domain\Section\Section {
		$section = $this->sectionRepository->findById( $sectionId );

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

	private function assertCanManageLesson( int $userId, Lesson $lesson ): void {
		if ( $lesson->courseId > 0 ) {
			$course = $this->findCourseOrFail( $lesson->courseId );
			if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
				throw new ForbiddenException();
			}

			return;
		}

		if ( ! $this->authorization->canCreateCourse( $userId ) ) {
			throw new ForbiddenException();
		}
	}

	private function assertCanViewLesson( int $userId, Lesson $lesson ): void {
		if ( $lesson->courseId > 0 ) {
			$course = $this->findCourseOrFail( $lesson->courseId );
			if ( ! $this->authorization->canViewCourse( $userId, $course->authorId ) ) {
				throw new ForbiddenException();
			}

			return;
		}

		if ( ! $this->authorization->canCreateCourse( $userId ) ) {
			throw new ForbiddenException();
		}
	}

	private function generateUniqueSlug( string $title, int $courseId ): string {
		$base = $this->normalizeSlug( $title );

		if ( '' === $base ) {
			$base = 'lesson';
		}

		$slug    = $base;
		$counter = 1;

		while ( null !== $this->lessonRepository->findBySlugAndCourseId( $slug, $courseId ) ) {
			$slug = $base . '-' . $counter;
			++$counter;
		}

		return $slug;
	}

	private function normalizeSlug( string $slug ): string {
		$slug = strtolower( trim( $slug ) );
		$slug = (string) preg_replace( '/[^a-z0-9-]+/', '-', $slug );
		$slug = (string) preg_replace( '/-+/', '-', $slug );

		return trim( $slug, '-' );
	}

	private function normalizeAvailableAfterDays( ?int $days ): ?int {
		if ( null === $days || $days <= 0 ) {
			return null;
		}

		return $days;
	}

	private function assertValidSlug( string $slug ): void {
		if ( '' === $slug ) {
			throw new ValidationException( 'Validation failed.', array( 'slug' => 'Slug is required.' ) );
		}

		if ( ! preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug ) ) {
			throw new ValidationException(
				'Validation failed.',
				array( 'slug' => 'Slug may only contain lowercase letters, numbers, and hyphens.' )
			);
		}
	}
}

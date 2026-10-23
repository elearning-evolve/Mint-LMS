<?php
declare(strict_types=1);

namespace MintLMS\Application\Course;

use MintLMS\Application\Contract\AdminDashboardRepositoryInterface;
use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Contract\UserLookupInterface;
use MintLMS\Application\Course\Dto\CourseDto;
use MintLMS\Application\Course\Dto\CourseListDto;
use MintLMS\Application\Course\Dto\CreateCourseDto;
use MintLMS\Application\Course\Dto\UpdateCourseDto;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Application\Event\DomainEventPublisher;
use MintLMS\Domain\Course\Course;
use MintLMS\Domain\Course\CourseRepositoryInterface;
use MintLMS\Domain\Course\CourseStatus;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Domain\Event\CoursePublished;
use MintLMS\Domain\Shared\Clock;

final class CourseService {

	private const MAX_PER_PAGE = 100;

	private const DEFAULT_PER_PAGE = 20;

	public function __construct(
		private CourseRepositoryInterface $repository,
		private AuthorizationInterface $authorization,
		private Clock $clock,
		private ?AdminDashboardRepositoryInterface $dashboardRepository = null,
		private ?UserLookupInterface $users = null,
		private ?DomainEventPublisher $events = null,
	) {
	}

	public function create( CreateCourseDto $dto, int $userId ): CourseDto {
		if ( ! $this->authorization->canCreateCourse( $userId ) ) {
			throw new ForbiddenException();
		}

		$title = trim( $dto->title );
		if ( '' === $title ) {
			throw new ValidationException( 'Validation failed.', array( 'title' => 'Title is required.' ) );
		}

		$slug = null !== $dto->slug ? $this->normalizeSlug( $dto->slug ) : $this->generateUniqueSlug( $title );
		$this->assertValidSlug( $slug );

		if ( null !== $this->repository->findBySlug( $slug ) ) {
			throw new ValidationException( 'Validation failed.', array( 'slug' => 'Slug is already in use.' ) );
		}

		$now = $this->clock->now();

		$course = new Course(
			0,
			$title,
			$slug,
			$dto->description,
			$dto->featuredImageId,
			CourseStatus::Draft,
			$dto->enrollmentType,
			$userId,
			$now,
			$now,
		);

		return CourseDto::fromCourse( $this->repository->save( $course ) );
	}

	public function update( int $id, UpdateCourseDto $dto, int $userId ): CourseDto {
		$course = $this->findCourseOrFail( $id );

		if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$title       = null !== $dto->title ? trim( $dto->title ) : $course->title;
		$slug        = null !== $dto->slug ? $this->normalizeSlug( $dto->slug ) : $course->slug;
		$description = null !== $dto->description ? $dto->description : $course->description;
		$imageId     = $dto->updateFeaturedImage ? $dto->featuredImageId : $course->featuredImageId;
		$enrollment  = null !== $dto->enrollmentType ? $dto->enrollmentType : $course->enrollmentType;
		$status      = null !== $dto->status ? $dto->status : $course->status;
		$settings = $course->settings->with(
			$dto->emailOnPublish,
			$dto->studentComplete,
			$dto->certificate,
		);

		$settingsPatch = array();
		if ( null !== $dto->progression ) {
			$settingsPatch['progression'] = $dto->progression;
		}
		if ( null !== $dto->expireAccess ) {
			$settingsPatch['expireAccess'] = $dto->expireAccess;
		}
		if ( null !== $dto->expireAccessDays ) {
			$settingsPatch['expireAccessDays'] = $dto->expireAccessDays;
		}
		if ( null !== $dto->prerequisitesEnabled ) {
			$settingsPatch['prerequisitesEnabled'] = $dto->prerequisitesEnabled;
		}
		if ( null !== $dto->prerequisiteCourseIds ) {
			$settingsPatch['prerequisiteCourseIds'] = array_values(
				array_filter(
					array_map( 'intval', $dto->prerequisiteCourseIds ),
					static fn( int $id ): bool => $id > 0 && $id !== $course->id
				)
			);
		}
		if ( null !== $dto->prerequisiteCompare ) {
			$settingsPatch['prerequisiteCompare'] = $dto->prerequisiteCompare;
		}
		if ( $dto->clearAccessStartAt ) {
			$settingsPatch['accessStartAt'] = null;
		} elseif ( null !== $dto->accessStartAt ) {
			$settingsPatch['accessStartAt'] = $dto->accessStartAt;
		}
		if ( $dto->clearAccessEndAt ) {
			$settingsPatch['accessEndAt'] = null;
		} elseif ( null !== $dto->accessEndAt ) {
			$settingsPatch['accessEndAt'] = $dto->accessEndAt;
		}
		if ( null !== $dto->seatLimit ) {
			$settingsPatch['seatLimit'] = $dto->seatLimit;
		}
		if ( null !== $dto->materialsEnabled ) {
			$settingsPatch['materialsEnabled'] = $dto->materialsEnabled;
		}
		if ( null !== $dto->materialsContent ) {
			$settingsPatch['materialsContent'] = $dto->materialsContent;
		}
		if ( null !== $dto->lessonListVisibility ) {
			$settingsPatch['lessonListVisibility'] = $dto->lessonListVisibility;
		}
		if ( null !== $dto->paginationEnabled ) {
			$settingsPatch['paginationEnabled'] = $dto->paginationEnabled;
		}
		if ( null !== $dto->lessonsPerPage ) {
			$settingsPatch['lessonsPerPage'] = $dto->lessonsPerPage;
		}
		if ( null !== $dto->groupsPerPage ) {
			$settingsPatch['groupsPerPage'] = $dto->groupsPerPage;
		}
		if ( null !== $dto->completionRedirect ) {
			$settingsPatch['completionRedirect'] = $dto->completionRedirect;
		}
		if ( null !== $dto->pointsOnComplete ) {
			$settingsPatch['pointsOnComplete'] = $dto->pointsOnComplete;
		}
		if ( array() !== $settingsPatch ) {
			$settings = $settings->merge( $settingsPatch );
		}
		$wasPublished = CourseStatus::Published === $course->status;

		if ( null !== $imageId && $imageId <= 0 ) {
			$imageId = null;
		}

		if ( '' === $title ) {
			throw new ValidationException( 'Validation failed.', array( 'title' => 'Title is required.' ) );
		}

		$this->assertValidSlug( $slug );

		if ( $slug !== $course->slug ) {
			$existing = $this->repository->findBySlug( $slug );
			if ( null !== $existing && $existing->id !== $course->id ) {
				throw new ValidationException( 'Validation failed.', array( 'slug' => 'Slug is already in use.' ) );
			}
		}

		$updated = new Course(
			$course->id,
			$title,
			$slug,
			$description,
			$imageId,
			$status,
			$enrollment,
			$course->authorId,
			$course->createdAt,
			$this->clock->now(),
			$settings,
		);

		$saved = $this->repository->save( $updated );

		if ( ! $wasPublished && CourseStatus::Published === $saved->status && $saved->settings->emailOnPublish ) {
			$this->dispatchPublished( $saved->id );
		}

		return CourseDto::fromCourse( $saved );
	}

	public function delete( int $id, int $userId ): void {
		$course = $this->findCourseOrFail( $id );

		if ( ! $this->authorization->canDeleteCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		$this->repository->delete( $course->id );
	}

	public function trash( int $id, int $userId ): CourseDto {
		$course = $this->findCourseOrFail( $id );

		if ( ! $this->authorization->canDeleteCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		if ( CourseStatus::Trashed === $course->status ) {
			return CourseDto::fromCourse( $course );
		}

		$trashed = new Course(
			$course->id,
			$course->title,
			$course->slug,
			$course->description,
			$course->featuredImageId,
			CourseStatus::Trashed,
			$course->enrollmentType,
			$course->authorId,
			$course->createdAt,
			$this->clock->now(),
			$course->settings,
		);

		return CourseDto::fromCourse( $this->repository->save( $trashed ) );
	}

	public function restore( int $id, int $userId ): CourseDto {
		$course = $this->findCourseOrFail( $id );

		if ( ! $this->authorization->canEditCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		if ( CourseStatus::Trashed !== $course->status ) {
			return CourseDto::fromCourse( $course );
		}

		$restored = new Course(
			$course->id,
			$course->title,
			$course->slug,
			$course->description,
			$course->featuredImageId,
			CourseStatus::Draft,
			$course->enrollmentType,
			$course->authorId,
			$course->createdAt,
			$this->clock->now(),
			$course->settings,
		);

		return CourseDto::fromCourse( $this->repository->save( $restored ) );
	}

	public function get( int $id, int $userId ): CourseDto {
		$course = $this->findCourseOrFail( $id );

		if ( ! $this->authorization->canViewCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		return CourseDto::fromCourse( $course );
	}

	public function getPublished( int $id ): ?CourseDto {
		$course = $this->repository->findById( $id );

		if ( null === $course || CourseStatus::Published !== $course->status ) {
			return null;
		}

		return CourseDto::fromCourse( $course );
	}

	public function list( int $page, int $perPage, int $userId, ?CourseStatus $status = null, ?string $search = null ): CourseListDto {
		if ( ! $this->authorization->canListCourses( $userId ) ) {
			throw new ForbiddenException();
		}

		$page    = max( 1, $page );
		$perPage = max( 1, min( self::MAX_PER_PAGE, $perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE ) );

		$authorId = $this->authorization->canViewAllCourses( $userId ) ? null : $userId;

		$result = $this->repository->list( $page, $perPage, $authorId, $status, $search );

		$allTotal       = $this->repository->list( 1, 1, $authorId, null, null )['total'];
		$publishedTotal = $this->repository->list( 1, 1, $authorId, CourseStatus::Published, null )['total'];
		$draftTotal     = $this->repository->list( 1, 1, $authorId, CourseStatus::Draft, null )['total'];
		$archivedTotal  = $this->repository->list( 1, 1, $authorId, CourseStatus::Archived, null )['total'];

		$trashTotal = 0;
		if ( CourseStatus::Trashed !== $status ) {
			$trashResult = $this->repository->list( 1, 1, $authorId, CourseStatus::Trashed, null );
			$trashTotal  = $trashResult['total'];
		} else {
			$trashTotal = $result['total'];
		}

		$courseIds = array_map( static fn( Course $course ): int => $course->id, $result['courses'] );
		$stats     = array();

		if ( null !== $this->dashboardRepository && array() !== $courseIds ) {
			$stats = $this->dashboardRepository->getCourseStatsBatch( $courseIds );
		}

		$users = $this->users;
		$courses = array_map(
			static function ( Course $course ) use ( $stats, $users ): CourseDto {
				$authorName = '';
				if ( null !== $users ) {
					$authorName = (string) ( $users->getDisplayName( $course->authorId ) ?? '' );
				}

				return CourseDto::fromCourse(
					$course,
					$stats[ $course->id ] ?? null,
					$authorName
				);
			},
			$result['courses']
		);

		return new CourseListDto(
			$courses,
			$result['total'],
			$page,
			$perPage,
			$trashTotal,
			$allTotal,
			$publishedTotal,
			$draftTotal,
			$archivedTotal,
		);
	}

	public function publish( int $id, int $userId ): CourseDto {
		$course = $this->findCourseOrFail( $id );

		if ( ! $this->authorization->canPublishCourse( $userId, $course->authorId ) ) {
			throw new ForbiddenException();
		}

		if ( CourseStatus::Published === $course->status ) {
			return CourseDto::fromCourse( $course );
		}

		$published = new Course(
			$course->id,
			$course->title,
			$course->slug,
			$course->description,
			$course->featuredImageId,
			CourseStatus::Published,
			$course->enrollmentType,
			$course->authorId,
			$course->createdAt,
			$this->clock->now(),
			$course->settings,
		);

		$saved = $this->repository->save( $published );

		if ( $saved->settings->emailOnPublish ) {
			$this->dispatchPublished( $saved->id );
		}

		return CourseDto::fromCourse( $saved );
	}

	private function dispatchPublished( int $courseId ): void {
		if ( null === $this->events ) {
			return;
		}

		$this->events->publish(
			new CoursePublished(
				$courseId,
				$this->clock->now(),
			)
		);
	}

	private function findCourseOrFail( int $id ): Course {
		$course = $this->repository->findById( $id );

		if ( null === $course ) {
			throw new NotFoundException( 'Course not found.' );
		}

		return $course;
	}

	private function generateUniqueSlug( string $title ): string {
		$base = $this->normalizeSlug( $title );

		if ( '' === $base ) {
			$base = 'course';
		}

		$slug    = $base;
		$counter = 1;

		while ( null !== $this->repository->findBySlug( $slug ) ) {
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

<?php
declare(strict_types=1);

namespace MintLMS;

use MintLMS\Application\Certificate\CertificateService;
use MintLMS\Application\Contract\AuthorizationInterface;
use MintLMS\Application\Contract\CacheInterface;
use MintLMS\Application\Contract\EventDispatcherInterface;
use MintLMS\Application\Contract\MediaInterface;
use MintLMS\Api\V1\Courses as CoursesFacade;
use MintLMS\Api\V1\Enrollment as EnrollmentFacade;
use MintLMS\Api\V1\Progress as ProgressFacade;
use MintLMS\Application\Course\CourseService;
use MintLMS\Application\Course\CourseStructureService;
use MintLMS\Application\Course\Dto\CourseDto;
use MintLMS\Application\Enrollment\Dto\EnrollmentDto;
use MintLMS\Application\Enrollment\EnrollmentService;
use MintLMS\Application\Event\DomainEventPublisher;
use MintLMS\Application\Progress\ProgressService;
use MintLMS\Infrastructure\Auth\WpAuthorization;
use MintLMS\Infrastructure\Cache\WpObjectCache;
use MintLMS\Infrastructure\Event\SimpleEventDispatcher;
use MintLMS\Infrastructure\Event\WpHookBridge;
use MintLMS\Infrastructure\Media\WpMedia;

final class Plugin {

	private static ?SimpleEventDispatcher $dispatcher = null;

	private static ?AuthorizationInterface $authorization = null;

	private static ?CacheInterface $cache = null;

	private static ?MediaInterface $media = null;

	private static ?DomainEventPublisher $eventPublisher = null;

	private static ?EnrollmentService $enrollmentService = null;

	private static ?ProgressService $progressService = null;

	private static ?CourseService $courseService = null;

	private static ?CourseStructureService $structureService = null;

	private static ?CertificateService $certificateService = null;

	public static function boot(): void {
		if ( null !== self::$dispatcher ) {
			return;
		}

		self::$dispatcher     = new SimpleEventDispatcher();
		self::$authorization  = new WpAuthorization();
		self::$cache          = new WpObjectCache();
		self::$media          = new WpMedia();
		self::$eventPublisher = new DomainEventPublisher( self::$dispatcher );

		( new WpHookBridge() )->register( self::$dispatcher );
	}

	public static function eventDispatcher(): EventDispatcherInterface {
		self::requireBoot();

		return self::$dispatcher;
	}

	public static function eventPublisher(): DomainEventPublisher {
		self::requireBoot();

		return self::$eventPublisher;
	}

	public static function authorization(): AuthorizationInterface {
		self::requireBoot();

		return self::$authorization;
	}

	public static function cache(): CacheInterface {
		self::requireBoot();

		return self::$cache;
	}

	public static function media(): MediaInterface {
		self::requireBoot();

		return self::$media;
	}

	public static function enrollmentService(): EnrollmentService {
		self::requireBoot();

		if ( null === self::$enrollmentService ) {
			throw new \RuntimeException( 'Mint LMS enrollment service has not been registered.' );
		}

		return self::$enrollmentService;
	}

	public static function progressService(): ProgressService {
		self::requireBoot();

		if ( null === self::$progressService ) {
			throw new \RuntimeException( 'Mint LMS progress service has not been registered.' );
		}

		return self::$progressService;
	}

	public static function registerProgressService( ProgressService $service ): void {
		self::requireBoot();

		self::$progressService = $service;

		ProgressFacade::bind(
			static function ( int $userId, int $courseId ): \MintLMS\Application\Progress\Dto\ProgressDto {
				return self::$progressService->getProgressForUser( $userId, $courseId );
			},
			static function ( int $userId, int $lessonId ): void {
				self::$progressService->completeLesson( $userId, $lessonId );
			},
		);
	}

	public static function registerCourseServices(
		CourseService $courseService,
		CourseStructureService $structureService,
	): void {
		self::requireBoot();

		self::$courseService    = $courseService;
		self::$structureService = $structureService;

		CoursesFacade::bind(
			static function ( int $id ): ?CourseDto {
				return self::$courseService->getPublished( $id );
			},
			static function ( int $id ): \MintLMS\Application\Course\Dto\CourseStructureDto {
				return self::$structureService->getPublishedStructure( $id );
			},
		);
	}

	public static function courseService(): CourseService {
		self::requireBoot();

		if ( null === self::$courseService ) {
			throw new \RuntimeException( 'Mint LMS course service has not been registered.' );
		}

		return self::$courseService;
	}

	public static function registerEnrollmentService( EnrollmentService $service ): void {
		self::requireBoot();

		self::$enrollmentService = $service;

		EnrollmentFacade::bind(
			static function ( int $userId, int $courseId ): EnrollmentDto {
				return self::$enrollmentService->enroll( $userId, $courseId, $userId );
			},
			static function ( int $userId, int $courseId ): bool {
				return self::$enrollmentService->isEnrolled( $userId, $courseId );
			},
		);
	}

	public static function certificateService(): CertificateService {
		self::requireBoot();

		if ( null === self::$certificateService ) {
			throw new \RuntimeException( 'Mint LMS certificate service has not been registered.' );
		}

		return self::$certificateService;
	}

	public static function registerCertificateService( CertificateService $service ): void {
		self::requireBoot();

		self::$certificateService = $service;
	}

	private static function requireBoot(): void {
		if ( null === self::$dispatcher ) {
			throw new \RuntimeException( 'Mint LMS plugin has not been booted.' );
		}
	}
}

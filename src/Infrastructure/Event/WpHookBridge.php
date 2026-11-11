<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Event;

use MintLMS\Domain\Event\CourseCompleted;
use MintLMS\Domain\Event\DomainEvent;
use MintLMS\Domain\Event\EnrollmentCancelled;
use MintLMS\Domain\Event\EnrollmentCreated;
use MintLMS\Domain\Event\LessonCompleted;

final class WpHookBridge {

	/** @var array<string, string> */
	private const HOOK_MAP = array(
		'lesson.completed'     => 'mintlms_lesson_completed',
		'course.completed'     => 'mintlms_course_completed',
		'course.published'     => 'mintlms_course_published',
		'enrollment.created'   => 'mintlms_enrollment_created',
		'enrollment.cancelled' => 'mintlms_enrollment_cancelled',
	);

	public function register( SimpleEventDispatcher $dispatcher ): void {
		foreach ( self::HOOK_MAP as $eventName => $hook ) {
			$dispatcher->addListener(
				$eventName,
				static function ( DomainEvent $event ) use ( $hook ): void {
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Hook is from mintlms_* map.
					do_action( $hook, $event );
				}
			);
		}
	}

	/**
	 * @return list<string>
	 */
	public static function supportedHooks(): array {
		return array_values( self::HOOK_MAP );
	}

	/**
	 * @return class-string<DomainEvent>|null
	 */
	public static function eventClassForHook( string $hook ): ?string {
		$map = array(
			'mintlms_lesson_completed'     => LessonCompleted::class,
			'mintlms_course_completed'     => CourseCompleted::class,
			'mintlms_enrollment_created'   => EnrollmentCreated::class,
			'mintlms_enrollment_cancelled' => EnrollmentCancelled::class,
		);

		return $map[ $hook ] ?? null;
	}
}

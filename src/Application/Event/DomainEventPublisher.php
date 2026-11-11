<?php
declare(strict_types=1);

namespace MintLMS\Application\Event;

use MintLMS\Application\Contract\EventDispatcherInterface;
use MintLMS\Domain\Event\DomainEvent;

/**
 * Thin application-layer wrapper for publishing domain events from use cases.
 *
 * Usage pattern:
 *
 *   final class CompleteLessonHandler {
 *       public function __construct(
 *           private DomainEventPublisher $events,
 *       ) {}
 *
 *       public function handle( CompleteLessonCommand $command ): void {
 *           // ... persist state ...
 *           $this->events->publish(
 *               new LessonCompleted( $userId, $lessonId, $courseId, $clock->now() )
 *           );
 *       }
 *   }
 */
final class DomainEventPublisher {

	public function __construct(
		private EventDispatcherInterface $dispatcher,
	) {
	}

	public function publish( DomainEvent $event ): void {
		$this->dispatcher->dispatch( $event );
	}
}

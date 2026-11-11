<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Event;

use MintLMS\Application\Contract\EventDispatcherInterface;
use MintLMS\Domain\Event\DomainEvent;

final class SimpleEventDispatcher implements EventDispatcherInterface {

	/** @var array<string, list<callable(DomainEvent): void>> */
	private array $listeners = array();

	/**
	 * @param callable(DomainEvent): void $listener
	 */
	public function addListener( string $eventName, callable $listener ): void {
		$this->listeners[ $eventName ][] = $listener;
	}

	public function dispatch( DomainEvent $event ): void {
		$name = $event->name();

		if ( ! isset( $this->listeners[ $name ] ) ) {
			return;
		}

		foreach ( $this->listeners[ $name ] as $listener ) {
			$listener( $event );
		}
	}
}

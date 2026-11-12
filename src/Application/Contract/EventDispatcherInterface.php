<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

use MintLMS\Domain\Event\DomainEvent;

interface EventDispatcherInterface {

	public function dispatch( DomainEvent $event ): void;
}

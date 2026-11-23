<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

defined( 'ABSPATH' ) || exit;

use MintLMS\Domain\Event\DomainEvent;

interface EventDispatcherInterface {

	public function dispatch( DomainEvent $event ): void;
}

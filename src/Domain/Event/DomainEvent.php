<?php
declare(strict_types=1);

namespace MintLMS\Domain\Event;

interface DomainEvent {

	public function name(): string;

	public function occurredAt(): \DateTimeImmutable;
}

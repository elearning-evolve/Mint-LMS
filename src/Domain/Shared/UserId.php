<?php
declare(strict_types=1);

namespace MintLMS\Domain\Shared;

final readonly class UserId {

	private function __construct(
		private int $value,
	) {
		if ( $this->value <= 0 ) {
			throw new \InvalidArgumentException( 'User ID must be a positive integer.' );
		}
	}

	public static function fromInt( int $value ): self {
		return new self( $value );
	}

	public function toInt(): int {
		return $this->value;
	}

	public function equals( self $other ): bool {
		return $this->value === $other->value;
	}
}

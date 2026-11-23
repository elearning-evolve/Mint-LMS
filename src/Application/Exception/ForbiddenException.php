<?php
declare(strict_types=1);

namespace MintLMS\Application\Exception;

defined( 'ABSPATH' ) || exit;

final class ForbiddenException extends \RuntimeException {

	public const REASON_GENERIC = 'generic';

	public const REASON_ENROLLMENT = 'enrollment';

	public const REASON_PROGRESSION = 'progression';

	public const REASON_DRIP = 'drip';

	public const REASON_EXPIRED = 'expired';

	public const REASON_UNAVAILABLE = 'unavailable';

	private string $reason = self::REASON_GENERIC;

	public function __construct( string $message = 'You do not have permission to perform this action.' ) {
		parent::__construct( $message );
	}

	public function forReason( string $reason ): self {
		$this->reason = $reason;

		return $this;
	}

	public function reason(): string {
		return $this->reason;
	}
}

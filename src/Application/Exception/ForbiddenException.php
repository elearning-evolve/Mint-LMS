<?php
declare(strict_types=1);

namespace MintLMS\Application\Exception;

final class ForbiddenException extends \RuntimeException {

	public function __construct( string $message = 'You do not have permission to perform this action.' ) {
		parent::__construct( $message );
	}
}

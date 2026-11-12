<?php
declare(strict_types=1);

namespace MintLMS\Application\Exception;

final class NotFoundException extends \RuntimeException {

	public function __construct( string $message = 'Resource not found.' ) {
		parent::__construct( $message );
	}
}

<?php
declare(strict_types=1);

namespace MintLMS\Application\Exception;

final class ValidationException extends \RuntimeException {

	/**
	 * @param array<string, string> $errors
	 */
	public function __construct(
		string $message = 'Validation failed.',
		private array $errors = array(),
	) {
		parent::__construct( $message );
	}

	/**
	 * @return array<string, string>
	 */
	public function errors(): array {
		return $this->errors;
	}
}

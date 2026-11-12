<?php
declare(strict_types=1);

namespace MintLMS\Api\V1\Registry;

interface QuestionTypeInterface {

	public function slug(): string;

	public function label(): string;
}

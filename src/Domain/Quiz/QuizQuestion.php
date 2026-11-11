<?php
declare(strict_types=1);

namespace MintLMS\Domain\Quiz;

final readonly class QuizQuestion {

	public const TYPE_MCQ        = 'mcq';
	public const TYPE_MCQ_MULTI  = 'mcq_multi';
	public const TYPE_TRUE_FALSE = 'true_false';
	public const TYPE_ESSAY      = 'essay';

	/**
	 * @return list<string>
	 */
	public static function types(): array {
		return array(
			self::TYPE_MCQ,
			self::TYPE_MCQ_MULTI,
			self::TYPE_TRUE_FALSE,
			self::TYPE_ESSAY,
		);
	}

	public function __construct(
		public int $id,
		public int $quizId,
		public string $type,
		public string $prompt,
		/** @var list<string> */
		public array $options,
		public string $correctAnswer,
		public int $sortOrder,
	) {
	}

	public function isAutoGradable(): bool {
		return self::TYPE_ESSAY !== $this->type;
	}
}

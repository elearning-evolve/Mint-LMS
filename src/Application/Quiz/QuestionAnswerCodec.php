<?php
declare(strict_types=1);

namespace MintLMS\Application\Quiz;

/**
 * Encodes / decodes multi-correct answers stored in the correct_answer TEXT column.
 * Domain stays a plain string; Application owns the JSON convention.
 */
final class QuestionAnswerCodec {

	/**
	 * @param list<string> $answers
	 */
	public static function encodeMulti( array $answers ): string {
		$clean = array();

		foreach ( $answers as $answer ) {
			$value = trim( (string) $answer );
			if ( '' === $value ) {
				continue;
			}
			$clean[] = $value;
		}

		$clean = array_values( array_unique( $clean ) );
		sort( $clean, SORT_STRING );

		$encoded = json_encode( $clean );

		return is_string( $encoded ) ? $encoded : '[]';
	}

	/**
	 * @return list<string>
	 */
	public static function decodeMulti( string $raw ): array {
		$trimmed = trim( $raw );

		if ( '' === $trimmed ) {
			return array();
		}

		if ( str_starts_with( $trimmed, '[' ) ) {
			$decoded = json_decode( $trimmed, true );
			if ( is_array( $decoded ) ) {
				$clean = array();
				foreach ( $decoded as $item ) {
					$value = trim( (string) $item );
					if ( '' !== $value ) {
						$clean[] = $value;
					}
				}
				$clean = array_values( array_unique( $clean ) );
				sort( $clean, SORT_STRING );

				return $clean;
			}
		}

		return array( $trimmed );
	}

	/**
	 * Normalize REST/body correct_answer (string|array) for persistence.
	 *
	 * @param list<string> $options
	 */
	public static function normalizeForType( string $type, mixed $correctAnswer, array $options = array() ): string {
		if ( \MintLMS\Domain\Quiz\QuizQuestion::TYPE_ESSAY === $type ) {
			return '';
		}

		if (
			\MintLMS\Domain\Quiz\QuizQuestion::TYPE_MCQ_MULTI === $type
			|| \MintLMS\Domain\Quiz\QuizQuestion::TYPE_TRUE_FALSE === $type
		) {
			if ( is_array( $correctAnswer ) ) {
				$list = array_map( 'strval', $correctAnswer );
			} elseif ( is_string( $correctAnswer ) ) {
				$list = self::decodeMulti( $correctAnswer );
			} else {
				$list = array();
			}

			if ( array() !== $options ) {
				$list = array_values(
					array_filter(
						$list,
						static fn( string $answer ): bool => in_array( $answer, $options, true )
					)
				);
			}

			return self::encodeMulti( $list );
		}

		if ( is_array( $correctAnswer ) ) {
			$first = reset( $correctAnswer );

			return is_scalar( $first ) ? (string) $first : '';
		}

		return is_scalar( $correctAnswer ) ? (string) $correctAnswer : '';
	}
}

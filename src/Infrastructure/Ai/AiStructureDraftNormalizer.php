<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Ai;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Ai\AiScope;
use MintLMS\Application\Exception\ValidationException;

/**
 * Validates + strips AI JSON so only Mint LMS hierarchy remains.
 * Does not write to the database.
 */
final class AiStructureDraftNormalizer {

	/**
	 * @param array<string, mixed> $raw
	 * @return array{
	 *   root: string,
	 *   course?: array<string, mixed>,
	 *   lesson?: array<string, mixed>,
	 *   quiz?: array<string, mixed>
	 * }
	 */
	public function normalize( array $raw ): array {
		$this->rejectForbiddenTypes( $raw );

		$root = sanitize_key( (string) ( $raw['root'] ?? '' ) );
		if ( ! AiScope::isAllowedRoot( $root ) ) {
			throw new ValidationException(
				esc_html__( 'AI draft root must be course, lesson, or quiz (Mint LMS only).', 'mint-lms' ),
				array( 'ai' => 'invalid_root' )
			);
		}

		$out = array( 'root' => $root );

		if ( AiScope::ROOT_COURSE === $root ) {
			$course = $this->normalizeCourse( is_array( $raw['course'] ?? null ) ? $raw['course'] : array() );
			if ( null === $course ) {
				throw new ValidationException(
					esc_html__( 'AI did not return a usable Mint LMS course.', 'mint-lms' ),
					array( 'ai' => 'invalid_course' )
				);
			}
			$out['course'] = $course;
		} elseif ( AiScope::ROOT_LESSON === $root ) {
			$lesson = $this->normalizeLesson( is_array( $raw['lesson'] ?? null ) ? $raw['lesson'] : array() );
			if ( null === $lesson ) {
				throw new ValidationException(
					esc_html__( 'AI did not return a usable Mint LMS lesson.', 'mint-lms' ),
					array( 'ai' => 'invalid_lesson' )
				);
			}
			$out['lesson'] = $lesson;
		} else {
			$quiz = $this->normalizeQuiz( is_array( $raw['quiz'] ?? null ) ? $raw['quiz'] : array() );
			if ( null === $quiz ) {
				throw new ValidationException(
					esc_html__( 'AI did not return a usable Mint LMS quiz.', 'mint-lms' ),
					array( 'ai' => 'invalid_quiz' )
				);
			}
			$out['quiz'] = $quiz;
		}

		return $out;
	}

	/**
	 * @param array<string, mixed> $node
	 */
	private function rejectForbiddenTypes( array $node ): void {
		$stack = array( $node );
		while ( $stack ) {
			$current = array_pop( $stack );
			if ( ! is_array( $current ) ) {
				continue;
			}
			if ( isset( $current['post_type'] ) ) {
				$type = sanitize_key( (string) $current['post_type'] );
				if ( AiScope::isForbiddenPostType( $type ) || ( '' !== $type && ! AiScope::isAllowedPostType( $type ) ) ) {
					throw new ValidationException(
						esc_html__( 'AI attempted to target a non–Mint LMS content type. Blocked.', 'mint-lms' ),
						array(
							'ai'        => 'out_of_scope',
							'post_type' => esc_html( $type ),
						)
					);
				}
			}
			foreach ( $current as $value ) {
				if ( is_array( $value ) ) {
					$stack[] = $value;
				}
			}
		}
	}

	/**
	 * @param array<string, mixed> $course
	 * @return array{title: string, description: string, lesson_groups: list<array<string, mixed>>}|null
	 */
	private function normalizeCourse( array $course ): ?array {
		$title = sanitize_text_field( (string) ( $course['title'] ?? '' ) );
		if ( '' === $title ) {
			return null;
		}

		$groups_in = $course['lesson_groups'] ?? $course['sections'] ?? array();
		$groups    = array();
		if ( is_array( $groups_in ) ) {
			foreach ( $groups_in as $group ) {
				if ( ! is_array( $group ) ) {
					continue;
				}
				$groups[] = $this->normalizeLessonGroup( $group );
			}
		}

		return array(
			'title'         => $title,
			'description'   => sanitize_textarea_field( (string) ( $course['description'] ?? '' ) ),
			'lesson_groups' => $groups,
		);
	}

	/**
	 * @param array<string, mixed> $group
	 * @return array{title: string, lessons: list<array<string, mixed>>}
	 */
	private function normalizeLessonGroup( array $group ): array {
		$title      = sanitize_text_field( (string) ( $group['title'] ?? __( 'Lesson Group', 'mint-lms' ) ) );
		$lessons_in = $group['lessons'] ?? array();
		$lessons    = array();
		if ( is_array( $lessons_in ) ) {
			foreach ( $lessons_in as $lesson ) {
				if ( ! is_array( $lesson ) ) {
					continue;
				}
				$normalized = $this->normalizeLesson( $lesson );
				if ( null !== $normalized ) {
					$lessons[] = $normalized;
				}
			}
		}

		return array(
			'title'   => $title,
			'lessons' => $lessons,
		);
	}

	/**
	 * @param array<string, mixed> $lesson
	 * @return array{title: string, description: string, quizzes: list<array<string, mixed>>}|null
	 */
	private function normalizeLesson( array $lesson ): ?array {
		$title = sanitize_text_field( (string) ( $lesson['title'] ?? '' ) );
		if ( '' === $title ) {
			return null;
		}

		$quizzes_in = $lesson['quizzes'] ?? array();
		$quizzes    = array();
		if ( is_array( $quizzes_in ) ) {
			foreach ( $quizzes_in as $quiz ) {
				if ( ! is_array( $quiz ) ) {
					continue;
				}
				$normalized = $this->normalizeQuiz( $quiz );
				if ( null !== $normalized ) {
					$quizzes[] = $normalized;
				}
			}
		}

		return array(
			'title'       => $title,
			'description' => sanitize_textarea_field( (string) ( $lesson['description'] ?? '' ) ),
			'quizzes'     => $quizzes,
		);
	}

	/**
	 * @param array<string, mixed> $quiz
	 * @return array{title: string, description: string, questions: list<array<string, mixed>>}|null
	 */
	private function normalizeQuiz( array $quiz ): ?array {
		$title = sanitize_text_field( (string) ( $quiz['title'] ?? '' ) );
		if ( '' === $title ) {
			return null;
		}

		$questions_in = $quiz['questions'] ?? array();
		$questions    = array();
		if ( is_array( $questions_in ) ) {
			foreach ( $questions_in as $question ) {
				if ( ! is_array( $question ) ) {
					continue;
				}
				$normalized = $this->normalizeQuestion( $question );
				if ( null !== $normalized ) {
					$questions[] = $normalized;
				}
			}
		}

		return array(
			'title'       => $title,
			'description' => sanitize_textarea_field( (string) ( $quiz['description'] ?? '' ) ),
			'questions'   => $questions,
		);
	}

	/**
	 * @param array<string, mixed> $item
	 * @return array{type: string, prompt: string, options: list<string>, correct_answer: string}|null
	 */
	private function normalizeQuestion( array $item ): ?array {
		$type   = sanitize_key( (string) ( $item['type'] ?? 'mcq' ) );
		$prompt = sanitize_text_field( (string) ( $item['prompt'] ?? $item['title'] ?? '' ) );
		if ( '' === $prompt ) {
			return null;
		}

		if ( ! in_array( $type, AiScope::allowedQuestionTypes(), true ) ) {
			$type = 'mcq';
		}

		$options = array();
		if ( isset( $item['options'] ) && is_array( $item['options'] ) ) {
			foreach ( $item['options'] as $option ) {
				$clean = sanitize_text_field( (string) $option );
				if ( '' !== $clean ) {
					$options[] = $clean;
				}
			}
		}

		$correct = sanitize_text_field( (string) ( $item['correct_answer'] ?? '' ) );

		if ( 'true_false' === $type ) {
			$options = array( 'True', 'False' );
			$correct = in_array( strtolower( $correct ), array( 'true', '1', 'yes' ), true ) ? 'True' : 'False';
		} elseif ( count( $options ) < 2 ) {
			return null;
		} elseif ( '' === $correct || ! in_array( $correct, $options, true ) ) {
			$correct = $options[0];
		}

		return array(
			'type'           => $type,
			'prompt'         => $prompt,
			'options'        => $options,
			'correct_answer' => $correct,
		);
	}
}

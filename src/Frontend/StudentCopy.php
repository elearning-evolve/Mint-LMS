<?php
declare(strict_types=1);

namespace MintLMS\Frontend;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Exception\ForbiddenException;

/**
 * Presentation-edge copy for student UI. Application keeps stable English keys;
 * this class maps them (and count-derived labels) into mint-lms gettext strings.
 */
final class StudentCopy {

	/**
	 * Translate known lock / access messages.
	 */
	public static function lockMessage( string $reason, string $rawMessage = '' ): string {
		$rawMessage = trim( $rawMessage );

		switch ( $reason ) {
			case ForbiddenException::REASON_ENROLLMENT:
				return __( 'Enroll in this course to access this lesson.', 'mint-lms' );
			case ForbiddenException::REASON_PROGRESSION:
				return __( 'Finish the previous lesson first.', 'mint-lms' );
			case ForbiddenException::REASON_EXPIRED:
				return __( 'Your access to this course has expired.', 'mint-lms' );
			case ForbiddenException::REASON_UNAVAILABLE:
				if ( 'This course has not opened yet.' === $rawMessage ) {
					return __( 'This course has not opened yet.', 'mint-lms' );
				}
				if ( 'This course is no longer available.' === $rawMessage ) {
					return __( 'This course is no longer available.', 'mint-lms' );
				}

				return __( 'This lesson is not available.', 'mint-lms' );
			case ForbiddenException::REASON_DRIP:
				if ( 1 === preg_match( '/^Available in (\d+) day$/', $rawMessage, $matches ) ) {
					return sprintf(
						/* translators: %d: number of days until drip unlock */
						__( 'Available in %d day', 'mint-lms' ),
						(int) $matches[1]
					);
				}
				if ( 1 === preg_match( '/^Available in (\d+) days$/', $rawMessage, $matches ) ) {
					return sprintf(
						/* translators: %d: number of days until drip unlock */
						__( 'Available in %d days', 'mint-lms' ),
						(int) $matches[1]
					);
				}

				return __( 'This lesson is not available yet.', 'mint-lms' );
			default:
				return '' !== $rawMessage
					? $rawMessage
					: __( 'This lesson is part of a course that requires enrollment. Enroll to unlock all lessons.', 'mint-lms' );
		}
	}

	/**
	 * Localize curriculum outline labels built as stable English in Application.
	 *
	 * @param list<array<string, mixed>> $outline
	 * @return list<array<string, mixed>>
	 */
	public static function localizeContentOutline( array $outline ): array {
		$localized = array();

		foreach ( $outline as $section ) {
			if ( ! is_array( $section ) ) {
				continue;
			}

			$lessons = array();
			foreach ( $section['lessons'] ?? array() as $lesson ) {
				if ( ! is_array( $lesson ) ) {
					continue;
				}

				$quizzes = array();
				foreach ( $lesson['quizzes'] ?? array() as $quiz ) {
					if ( ! is_array( $quiz ) ) {
						continue;
					}

					$questions = array();
					foreach ( $quiz['questions'] ?? array() as $question ) {
						if ( ! is_array( $question ) ) {
							continue;
						}

						$text = trim( (string) ( $question['text'] ?? '' ) );
						if ( '' === $text || 'Untitled question' === $text ) {
							$text = __( 'Untitled question', 'mint-lms' );
						}

						$questions[] = array(
							'id'   => (int) ( $question['id'] ?? 0 ),
							'text' => $text,
						);
					}

					$title = trim( (string) ( $quiz['title'] ?? '' ) );
					if ( '' === $title || 'Quiz' === $title ) {
						$title = __( 'Quiz', 'mint-lms' );
					}

					$quizzes[] = array(
						'id'        => (int) ( $quiz['id'] ?? 0 ),
						'title'     => $title,
						'meta'      => self::questionsMeta( count( $questions ) ),
						'questions' => $questions,
					);
				}

				$lessons[] = array(
					'id'         => (int) ( $lesson['id'] ?? 0 ),
					'title'      => (string) ( $lesson['title'] ?? '' ),
					'meta'       => self::quizzesMeta( count( $quizzes ) ),
					'accessible' => ! empty( $lesson['accessible'] ),
					'quizzes'    => $quizzes,
				);
			}

			$localized[] = array(
				'id'      => (int) ( $section['id'] ?? 0 ),
				'title'   => (string) ( $section['title'] ?? '' ),
				'meta'    => self::lessonsMeta( count( $lessons ) ),
				'lessons' => $lessons,
			);
		}

		return $localized;
	}

	public static function lessonsMeta( int $count ): string {
		if ( 1 === $count ) {
			return __( '1 lesson', 'mint-lms' );
		}

		return sprintf(
			/* translators: %d: lesson count */
			__( '%d lessons', 'mint-lms' ),
			$count
		);
	}

	public static function quizzesMeta( int $count ): string {
		if ( 0 === $count ) {
			return __( 'No quiz yet', 'mint-lms' );
		}

		if ( 1 === $count ) {
			return __( '1 quiz', 'mint-lms' );
		}

		return sprintf(
			/* translators: %d: quiz count */
			__( '%d quizzes', 'mint-lms' ),
			$count
		);
	}

	public static function questionsMeta( int $count ): string {
		if ( 1 === $count ) {
			return __( '1 question', 'mint-lms' );
		}

		return sprintf(
			/* translators: %d: question count */
			__( '%d questions', 'mint-lms' ),
			$count
		);
	}
}

<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Quiz\Dto\QuizDto;

/** @var QuizDto|null $quiz */
/** @var bool $quizRequired */
/** @var bool $hasPassedQuiz */
/** @var bool $isEnrolled */
/** @var bool $isReviewMode */
/** @var bool $quizHeading */
$quiz          = $quiz ?? null;
$quizRequired  = $quizRequired ?? false;
$hasPassedQuiz = $hasPassedQuiz ?? true;
$isEnrolled    = $isEnrolled ?? false;
$isReviewMode  = ! empty( $isReviewMode );
$quizHeading   = ! isset( $quizHeading ) || (bool) $quizHeading;

if ( null === $quiz || ( ! $isEnrolled && ! $isReviewMode ) ) {
	return;
}

if ( ! $quizRequired && ! $isReviewMode ) {
	return;
}

$questionCount = count( $quiz->questions );
$quizBoot      = wp_json_encode(
	array(
		'quizId'      => (int) $quiz->id,
		'passPercent' => (int) $quiz->passPercent,
		'hasPassed'   => (bool) $hasPassedQuiz,
		'previewMode' => (bool) $isReviewMode,
		'questions'   => array_map(
			static function ( $question ): array {
				$row             = $question->toArray();
				$row['prompt']   = wp_kses_post( (string) ( $row['prompt'] ?? '' ) );
				$row['options']  = array_map( 'strval', is_array( $row['options'] ?? null ) ? $row['options'] : array() );
				return $row;
			},
			$quiz->questions
		),
	)
);
if ( ! is_string( $quizBoot ) ) {
	$quizBoot = '{}';
}
?>
<div
	class="mint-s3-quiz-runner"
	x-data="mintLessonQuiz(<?php echo esc_attr( $quizBoot ); ?>)"
>
	<?php if ( $quizHeading ) : ?>
		<div class="mint-s3-quiz-runner__head">
			<h2 class="mint-s3-quiz-runner__title"><?php echo esc_html( $quiz->title ); ?></h2>
			<?php if ( $questionCount > 0 ) : ?>
				<div class="mint-s3-quiz-runner__count">
					<?php
					echo esc_html(
						1 === $questionCount
							? __( '1 question', 'mint-lms' )
							: sprintf(
								/* translators: %d: question count */
								__( '%d questions', 'mint-lms' ),
								$questionCount
							)
					);
					?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<template x-if="hasPassed">
		<div class="mint-s3-quiz-result mint-s3-quiz-result--pass">
			<p class="mint-s3-quiz-result__title" x-text="previewMode ? mintLmsStudent.i18n.quizPreviewPassed : mintLmsStudent.i18n.quizPassed"></p>
		</div>
	</template>

	<template x-if="submitted && !hasPassed">
		<div class="mint-s3-quiz-result mint-s3-quiz-result--fail">
			<p class="mint-s3-quiz-result__title" x-text="mintLmsStudent.i18n.quizFailed"></p>
			<p class="mint-s3-quiz-result__meta" x-text="Math.round(scorePercent) + '% — ' + mintLmsStudent.i18n.quizRetry"></p>
			<button type="button" class="mint-s3b-nav__next mint-mt-4" @click="retry()"><?php esc_html_e( 'Try again', 'mint-lms' ); ?></button>
		</div>
	</template>

	<template x-if="!hasPassed && !submitted">
		<div class="mint-s3-quiz-take">
			<div class="mint-s3-quiz-take__progress">
				<span><?php esc_html_e( 'Question', 'mint-lms' ); ?> <span x-text="currentIndex + 1"></span> <?php esc_html_e( 'of', 'mint-lms' ); ?> <span x-text="questions.length"></span></span>
				<div class="mint-s3-quiz-take__bar" aria-hidden="true">
					<div class="mint-s3-quiz-take__bar-fill" :style="'width:' + (((currentIndex + 1) / Math.max(questions.length, 1)) * 100) + '%'"></div>
				</div>
			</div>

			<template x-for="(question, qIndex) in questions" :key="question.id">
				<div class="mint-s3-question-card" x-show="qIndex === currentIndex" x-cloak>
					<div class="mint-s3-question-card__prompt" x-html="question.prompt"></div>

					<template x-if="question.type === 'true_false'">
						<div class="mint-s3-choice-list">
							<label class="mint-s3-choice" :class="isChoiceSelected(question.id, 'true') ? 'is-selected' : ''">
								<input type="radio" class="mint-s3-choice__input" :name="'q-' + question.id" value="true" x-model="answers[question.id]" />
								<span class="mint-s3-choice__dot" aria-hidden="true"></span>
								<span><?php esc_html_e( 'True', 'mint-lms' ); ?></span>
							</label>
							<label class="mint-s3-choice" :class="isChoiceSelected(question.id, 'false') ? 'is-selected' : ''">
								<input type="radio" class="mint-s3-choice__input" :name="'q-' + question.id" value="false" x-model="answers[question.id]" />
								<span class="mint-s3-choice__dot" aria-hidden="true"></span>
								<span><?php esc_html_e( 'False', 'mint-lms' ); ?></span>
							</label>
						</div>
					</template>

					<template x-if="question.type === 'mcq'">
						<div class="mint-s3-choice-list">
							<template x-for="(option, oIndex) in question.options" :key="oIndex">
								<label class="mint-s3-choice" :class="isChoiceSelected(question.id, option) ? 'is-selected' : ''">
									<input type="radio" class="mint-s3-choice__input" :name="'q-' + question.id" :value="option" x-model="answers[question.id]" />
									<span class="mint-s3-choice__dot" aria-hidden="true"></span>
									<span x-text="option"></span>
								</label>
							</template>
						</div>
					</template>

					<template x-if="question.type === 'mcq_multi'">
						<div class="mint-s3-choice-list">
							<template x-for="(option, oIndex) in question.options" :key="oIndex">
								<label class="mint-s3-choice" :class="isMultiSelected(question.id, option) ? 'is-selected' : ''">
									<input
										type="checkbox"
										class="mint-s3-choice__input"
										:value="option"
										:checked="isMultiSelected(question.id, option)"
										@change="toggleMultiAnswer(question.id, option, $event.target.checked)"
									/>
									<span class="mint-s3-choice__box" aria-hidden="true"></span>
									<span x-text="option"></span>
								</label>
							</template>
						</div>
					</template>

					<template x-if="question.type === 'essay'">
						<textarea
							class="mint-s3-essay"
							rows="5"
							x-model="answers[question.id]"
							placeholder="<?php echo esc_attr__( 'Write your answer…', 'mint-lms' ); ?>"
						></textarea>
					</template>
				</div>
			</template>

			<div class="mint-s3-quiz-take__nav">
				<button
					type="button"
					class="mint-s3b-nav__prev"
					@click="goPrev()"
					:disabled="currentIndex === 0"
				><?php esc_html_e( 'Previous', 'mint-lms' ); ?></button>
				<button
					type="button"
					class="mint-s3b-nav__next"
					x-show="currentIndex < questions.length - 1"
					@click="goNext()"
				><?php esc_html_e( 'Next question', 'mint-lms' ); ?> ›</button>
				<button
					type="button"
					class="mint-s3b-nav__next"
					x-show="currentIndex === questions.length - 1"
					x-cloak
					@click="submitQuiz()"
					:disabled="submitting"
				>
					<span x-text="submitting ? mintLmsStudent.i18n.submittingQuiz : mintLmsStudent.i18n.submitQuiz"></span>
				</button>
			</div>
		</div>
	</template>

	<p x-show="error" x-text="error" class="mint-mt-3 mint-text-sm mint-text-danger"></p>
</div>

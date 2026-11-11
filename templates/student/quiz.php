<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Quiz\Dto\QuizDto;
use MintLMS\Infrastructure\PostType\PostTypes;

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
$storedSettings = get_post_meta( (int) $quiz->id, PostTypes::META_QUIZ_SETTINGS, true );
$storedSettings = is_array( $storedSettings ) ? $storedSettings : array();
$quizBoot       = wp_json_encode(
	array(
		'quizId'      => (int) $quiz->id,
		'passPercent' => (int) $quiz->passPercent,
		'hasPassed'   => (bool) $hasPassedQuiz,
		'previewMode' => (bool) $isReviewMode,
		'settings'    => array(
			'timeLimitEnabled'           => ! empty( $storedSettings['timeLimitEnabled'] ),
			'timeLimitHours'             => (string) ( $storedSettings['timeLimitHours'] ?? '00' ),
			'timeLimitMinutes'           => (string) ( $storedSettings['timeLimitMinutes'] ?? '00' ),
			'timeLimitSeconds'           => (string) ( $storedSettings['timeLimitSeconds'] ?? '00' ),
			'questionCompletion'         => ! empty( $storedSettings['questionCompletion'] ),
			'questionDisplay'            => ( $storedSettings['questionDisplay'] ?? 'one' ) === 'all' ? 'all' : 'one',
			'displayBackButton'          => ! empty( $storedSettings['displayBackButton'] ),
			'skipQuestion'               => ! empty( $storedSettings['skipQuestion'] ),
			'resultsMode'                => sanitize_key( (string) ( $storedSettings['resultsMode'] ?? 'end' ) ),
			'restrictRetakes'            => ! empty( $storedSettings['restrictRetakes'] ),
			'retriesAllowed'             => absint( $storedSettings['retriesAllowed'] ?? 0 ),
			'quizSavingEnabled'          => ! empty( $storedSettings['quizSavingEnabled'] ),
			'quizSavingIntervalSeconds'  => max( 5, absint( $storedSettings['quizSavingIntervalSeconds'] ?? 20 ) ),
			'restartButton'              => ! empty( $storedSettings['restartButton'] ),
			'randomizeOrder'             => ! empty( $storedSettings['randomizeOrder'] ),
			'randomizeAnswers'           => ! empty( $storedSettings['randomizeAnswers'] ),
		),
		'questions'   => array_map(
			static function ( $question ): array {
				$row = $question->toArray();
				unset( $row['correctAnswer'], $row['correctAnswers'] );
				$row['prompt']  = wp_kses_post( (string) ( $row['prompt'] ?? '' ) );
				$row['options'] = array_map( 'strval', is_array( $row['options'] ?? null ) ? $row['options'] : array() );
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

	<template x-if="hasPassed && !submitted">
		<div class="mint-s3-quiz-result mint-s3-quiz-result--pass">
			<p class="mint-s3-quiz-result__title" x-text="mintLmsStudent.i18n.quizPassed"></p>
		</div>
	</template>

	<template x-if="submitted">
		<div class="mint-s3-quiz-result" :class="resultToneClass()">
			<p class="mint-s3-quiz-result__title" x-text="resultTitle()"></p>
			<p class="mint-s3-quiz-result__meta" x-text="resultMeta()"></p>
			<template x-if="review.length">
				<div class="mint-s3-quiz-review">
					<template x-for="item in review" :key="'rv-' + item.id">
						<div class="mint-s3-quiz-review__item" :class="reviewItemClass(item)">
							<div class="mint-s3-quiz-review__prompt" x-html="questionPrompt(item.id)"></div>
							<div class="mint-s3-quiz-review__badge" x-text="reviewBadge(item)"></div>
							<p class="mint-s3-quiz-review__line">
								<span><?php esc_html_e( 'Your answer', 'mint-lms' ); ?>:</span>
								<span x-text="formatAnswer(item.given)"></span>
							</p>
							<p class="mint-s3-quiz-review__line" x-show="!item.pending">
								<span><?php esc_html_e( 'Correct answer', 'mint-lms' ); ?>:</span>
								<span x-text="formatAnswer(item.correctAnswer)"></span>
							</p>
						</div>
					</template>
				</div>
			</template>
			<button
				type="button"
				class="mint-s3b-nav__next mint-mt-4"
				x-show="canRetry()"
				@click="retry()"
			><?php esc_html_e( 'Try again', 'mint-lms' ); ?></button>
		</div>
	</template>

	<template x-if="!hasPassed && !submitted">
		<div class="mint-s3-quiz-take">
			<div class="mint-s3-quiz-take__progress">
				<div class="mint-s3b-progress mint-s3-quiz-take__pill">
					<span class="mint-s3b-progress__crumb" x-show="settings.questionDisplay !== 'all'">
						<?php esc_html_e( 'Question', 'mint-lms' ); ?>
						<span x-text="currentIndex + 1"></span>
						<?php esc_html_e( 'of', 'mint-lms' ); ?>
						<span x-text="questions.length"></span>
					</span>
					<span class="mint-s3b-progress__crumb" x-show="settings.questionDisplay === 'all'" x-cloak>
						<?php esc_html_e( 'Questions', 'mint-lms' ); ?>
						<span x-text="questions.length"></span>
					</span>
					<span class="mint-s3-quiz-take__timer" x-show="timerSeconds > 0" x-text="formatTimer()" x-cloak></span>
					<span class="mint-s3b-progress__badge"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></span>
				</div>
				<div class="mint-s3-quiz-take__bar" aria-hidden="true" x-show="settings.questionDisplay !== 'all'">
					<div class="mint-s3-quiz-take__bar-fill" :style="'width:' + (((currentIndex + 1) / Math.max(questions.length, 1)) * 100) + '%'"></div>
				</div>
			</div>

			<template x-for="(question, qIndex) in questions" :key="question.id">
				<div class="mint-s3b-stack mint-s3-question-stack" x-show="settings.questionDisplay === 'all' || qIndex === currentIndex" x-cloak>
					<div class="mint-s3b-stack__item">
						<div class="mint-s3b-stack__quiz mint-s3-question-stack__head">
							<span class="mint-s3b-stack__icon mint-s3b-stack__icon--quiz" aria-hidden="true">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
							</span>
							<div class="mint-s3b-stack__quiz-copy">
								<div class="mint-s3-question-card__prompt" x-html="question.prompt"></div>
								<div class="mint-s3b-stack__quiz-meta">
									<span class="mint-s3b-badge mint-s3b-badge--sm"><?php esc_html_e( 'Question', 'mint-lms' ); ?></span>
									<span x-show="question.type === 'mcq'"><?php esc_html_e( 'Multiple choice', 'mint-lms' ); ?></span>
									<span x-show="question.type === 'mcq_multi'"><?php esc_html_e( 'Select all that apply', 'mint-lms' ); ?></span>
									<span x-show="question.type === 'true_false'"><?php esc_html_e( 'True / False', 'mint-lms' ); ?></span>
									<span x-show="question.type === 'essay'"><?php esc_html_e( 'Essay', 'mint-lms' ); ?></span>
								</div>
							</div>
						</div>

						<template x-if="question.type === 'true_false'">
							<div class="mint-s3-quiz__body mint-s3-choice-list">
								<label class="mint-s3-row mint-s3-row--nested mint-s3-choice" :class="isChoiceSelected(question.id, 'true') ? 'is-selected' : ''">
									<input type="radio" class="mint-s3-choice__input" :name="'q-' + question.id" value="true" x-model="answers[question.id]" />
									<span class="mint-s3-choice__key" aria-hidden="true">T</span>
									<span class="mint-s3-choice__label"><?php esc_html_e( 'True', 'mint-lms' ); ?></span>
								</label>
								<label class="mint-s3-row mint-s3-row--nested mint-s3-choice" :class="isChoiceSelected(question.id, 'false') ? 'is-selected' : ''">
									<input type="radio" class="mint-s3-choice__input" :name="'q-' + question.id" value="false" x-model="answers[question.id]" />
									<span class="mint-s3-choice__key" aria-hidden="true">F</span>
									<span class="mint-s3-choice__label"><?php esc_html_e( 'False', 'mint-lms' ); ?></span>
								</label>
							</div>
						</template>

						<template x-if="question.type === 'mcq'">
							<div class="mint-s3-quiz__body mint-s3-choice-list">
								<template x-for="(option, oIndex) in question.options" :key="oIndex">
									<label class="mint-s3-row mint-s3-row--nested mint-s3-choice" :class="isChoiceSelected(question.id, option) ? 'is-selected' : ''">
										<input type="radio" class="mint-s3-choice__input" :name="'q-' + question.id" :value="option" x-model="answers[question.id]" />
										<span class="mint-s3-choice__key" aria-hidden="true" x-text="String.fromCharCode(65 + oIndex)"></span>
										<span class="mint-s3-choice__label" x-text="option"></span>
									</label>
								</template>
							</div>
						</template>

						<template x-if="question.type === 'mcq_multi'">
							<div class="mint-s3-quiz__body mint-s3-choice-list">
								<template x-for="(option, oIndex) in question.options" :key="oIndex">
									<label class="mint-s3-row mint-s3-row--nested mint-s3-choice" :class="isMultiSelected(question.id, option) ? 'is-selected' : ''">
										<input
											type="checkbox"
											class="mint-s3-choice__input"
											:value="option"
											:checked="isMultiSelected(question.id, option)"
											@change="toggleMultiAnswer(question.id, option, $event.target.checked)"
										/>
										<span class="mint-s3-choice__key mint-s3-choice__key--box" aria-hidden="true" x-text="String.fromCharCode(65 + oIndex)"></span>
										<span class="mint-s3-choice__label" x-text="option"></span>
									</label>
								</template>
							</div>
						</template>

						<template x-if="question.type === 'essay'">
							<div class="mint-s3-quiz__body mint-s3-choice-list">
								<textarea
									class="mint-s3-essay"
									rows="5"
									x-model="answers[question.id]"
									placeholder="<?php echo esc_attr__( 'Write your answer…', 'mint-lms' ); ?>"
								></textarea>
							</div>
						</template>
					</div>
				</div>
			</template>

			<div class="mint-s3-quiz-take__nav">
				<button
					type="button"
					class="mint-s3b-nav__prev"
					x-show="settings.displayBackButton && settings.questionDisplay !== 'all'"
					@click="goPrev()"
					:disabled="currentIndex === 0"
				><?php esc_html_e( 'Previous', 'mint-lms' ); ?></button>
				<span x-show="!(settings.displayBackButton && settings.questionDisplay !== 'all')"></span>
				<button
					type="button"
					class="mint-s3b-nav__next"
					x-show="settings.questionDisplay !== 'all' && currentIndex < questions.length - 1"
					@click="goNext()"
					:disabled="!canLeaveQuestion(currentIndex)"
				><?php esc_html_e( 'Next question', 'mint-lms' ); ?> ›</button>
				<button
					type="button"
					class="mint-s3b-nav__next"
					x-show="settings.questionDisplay === 'all' || currentIndex === questions.length - 1"
					x-cloak
					@click="submitQuiz()"
					:disabled="submitting || !canSubmit()"
				>
					<span x-text="submitting ? mintLmsStudent.i18n.submittingQuiz : mintLmsStudent.i18n.submitQuiz"></span>
				</button>
			</div>
		</div>
	</template>

	<p x-show="error" x-text="error" class="mint-mt-3 mint-text-sm mint-text-danger"></p>
</div>

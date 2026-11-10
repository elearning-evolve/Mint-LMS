<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Quiz\Dto\QuizDto;

/** @var QuizDto|null $quiz */
/** @var bool $quizRequired */
/** @var bool $hasPassedQuiz */
/** @var bool $isEnrolled */
/** @var bool $isReviewMode */
$quiz          = $quiz ?? null;
$quizRequired  = $quizRequired ?? false;
$hasPassedQuiz = $hasPassedQuiz ?? true;
$isEnrolled    = $isEnrolled ?? false;
$isReviewMode  = ! empty( $isReviewMode );

if ( null === $quiz || ( ! $isEnrolled && ! $isReviewMode ) ) {
	return;
}

if ( ! $quizRequired && ! $isReviewMode ) {
	return;
}
?>
<div
	class="mint-my-8 mint-rounded-xl mint-border mint-border-rule mint-bg-bg mint-p-6"
	x-data="mintLessonQuiz({
		quizId: <?php echo (int) $quiz->id; ?>,
		passPercent: <?php echo (int) $quiz->passPercent; ?>,
		hasPassed: <?php echo $hasPassedQuiz ? 'true' : 'false'; ?>,
		previewMode: <?php echo $isReviewMode ? 'true' : 'false'; ?>,
		questions: <?php echo wp_json_encode( array_map( static fn( $q ) => $q->toArray(), $quiz->questions ) ); ?>
	})"
>
	<div class="mint-mb-5 mint-flex mint-items-center mint-justify-between mint-gap-4">
		<h2 class="mint-text-over mint-font-semibold mint-uppercase mint-tracking-widest mint-text-ink"><?php echo esc_html( $quiz->title ); ?></h2>
		<span class="mint-shrink-0 mint-text-sm mint-text-ink-2">
			<?php
			printf(
				/* translators: %d: minimum pass percentage */
				esc_html__( 'Pass: %d%%', 'mint-lms' ),
				(int) $quiz->passPercent
			);
			?>
		</span>
	</div>

	<template x-if="hasPassed">
		<div class="mint-mb-4 mint-rounded-lg mint-bg-success-wash mint-px-4 mint-py-4">
			<p class="mint-text-sm mint-font-medium mint-text-success" x-text="previewMode ? mintLmsStudent.i18n.quizPreviewPassed : mintLmsStudent.i18n.quizPassed"></p>
		</div>
	</template>

	<template x-if="!hasPassed && !submitted">
		<div class="mint-flex mint-flex-col mint-gap-5">
			<template x-for="(question, qIndex) in questions" :key="question.id">
				<div class="mint-rounded-lg mint-bg-bg-subtle mint-p-4">
					<p class="mint-mb-3 mint-text-base mint-font-semibold mint-text-ink" x-text="(qIndex + 1) + '. ' + question.prompt"></p>
					<template x-if="question.type === 'true_false'">
						<div class="mint-flex mint-gap-4">
							<label class="mint-flex mint-cursor-pointer mint-items-center mint-gap-2 mint-text-sm mint-text-ink-2">
								<input type="radio" class="mint-h-4 mint-w-4 mint-accent-accent" :name="'q-' + question.id" value="true" x-model="answers[question.id]" />
								<?php esc_html_e( 'True', 'mint-lms' ); ?>
							</label>
							<label class="mint-flex mint-cursor-pointer mint-items-center mint-gap-2 mint-text-sm mint-text-ink-2">
								<input type="radio" class="mint-h-4 mint-w-4 mint-accent-accent" :name="'q-' + question.id" value="false" x-model="answers[question.id]" />
								<?php esc_html_e( 'False', 'mint-lms' ); ?>
							</label>
						</div>
					</template>
					<template x-if="question.type === 'mcq'">
						<div class="mint-flex mint-flex-col mint-gap-2">
							<template x-for="(option, oIndex) in question.options" :key="oIndex">
								<label class="mint-flex mint-cursor-pointer mint-items-center mint-gap-2 mint-text-sm mint-text-ink-2">
									<input type="radio" class="mint-h-4 mint-w-4 mint-accent-accent" :name="'q-' + question.id" :value="option" x-model="answers[question.id]" />
									<span x-text="option"></span>
								</label>
							</template>
						</div>
					</template>
					<template x-if="question.type === 'mcq_multi'">
						<div class="mint-flex mint-flex-col mint-gap-2">
							<template x-for="(option, oIndex) in question.options" :key="oIndex">
								<label class="mint-flex mint-cursor-pointer mint-items-center mint-gap-2 mint-text-sm mint-text-ink-2">
									<input
										type="checkbox"
										class="mint-h-4 mint-w-4 mint-accent-accent"
										:value="option"
										:checked="isMultiSelected(question.id, option)"
										@change="toggleMultiAnswer(question.id, option, $event.target.checked)"
									/>
									<span x-text="option"></span>
								</label>
							</template>
						</div>
					</template>
					<template x-if="question.type === 'essay'">
						<textarea
							class="mint-w-full mint-min-h-[110px] mint-rounded-md mint-border mint-border-rule mint-bg-bg mint-px-3 mint-py-2 mint-text-sm mint-text-ink"
							rows="4"
							x-model="answers[question.id]"
							placeholder="<?php echo esc_attr__( 'Write your answer…', 'mint-lms' ); ?>"
						></textarea>
					</template>
				</div>
			</template>
			<div>
				<button type="button" class="mint-inline-flex mint-items-center mint-justify-center mint-h-control mint-px-4 mint-text-sm mint-font-medium mint-rounded-md mint-bg-accent mint-text-neutral-50 hover:mint-bg-accent-hover mint-transition-colors mint-duration-hover" @click="submitQuiz()" :disabled="submitting">
					<span x-text="submitting ? mintLmsStudent.i18n.submittingQuiz : mintLmsStudent.i18n.submitQuiz"></span>
				</button>
			</div>
		</div>
	</template>

	<template x-if="submitted && !hasPassed">
		<div>
			<div class="mint-mb-4 mint-rounded-lg mint-bg-danger-wash mint-px-4 mint-py-4">
				<p class="mint-mb-1 mint-text-sm mint-font-medium mint-text-danger" x-text="mintLmsStudent.i18n.quizFailed"></p>
				<p class="mint-text-sm mint-text-ink-2" x-text="Math.round(scorePercent) + '% — ' + mintLmsStudent.i18n.quizRetry"></p>
			</div>
			<button type="button" class="mint-inline-flex mint-items-center mint-justify-center mint-h-control mint-px-4 mint-text-sm mint-font-medium mint-rounded-md mint-border mint-border-control mint-bg-bg mint-text-ink hover:mint-bg-bg-hover mint-transition-colors mint-duration-hover" @click="retry()">
				<?php esc_html_e( 'Try again', 'mint-lms' ); ?>
			</button>
		</div>
	</template>

	<p x-show="error" x-text="error" class="mint-mt-3 mint-text-sm mint-text-danger"></p>
</div>

<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Student\Dto\StudentPlayerContextDto;

/** @var StudentPlayerContextDto $context */
/** @var callable(int): string $lessonUrl */
$context        = $context;
$lessonUrl      = $lessonUrl;
$dashboardUrl   = $dashboardUrl ?? home_url( '/' );
$attachmentUrl  = $attachmentUrl ?? null;
$videoEmbed     = $videoEmbed ?? '';
$quizPreviewUrl = is_string( $quizPreviewUrl ?? null ) ? $quizPreviewUrl : '';

$completedIds = $context->completedLessonIds;
$lesson       = $context->currentLesson;
$pct          = (int) round( $context->progressPct );
?>
<div
	x-data="mintCoursePlayer({
		courseId: <?php echo (int) $context->courseId; ?>,
		lessonId: <?php echo (int) $lesson->id; ?>,
		progressPct: <?php echo (float) $context->progressPct; ?>,
		isComplete: <?php echo $context->isCurrentLessonComplete ? 'true' : 'false'; ?>,
		completedIds: <?php echo wp_json_encode( $completedIds ); ?>,
		isEnrolled: <?php echo $context->isEnrolled ? 'true' : 'false'; ?>,
		quizRequired: <?php echo $context->quizRequired ? 'true' : 'false'; ?>,
		hasPassedQuiz: <?php echo $context->hasPassedQuiz ? 'true' : 'false'; ?>
	})"
>
	<?php if ( ! empty( $context->isReviewMode ) ) : ?>
		<div class="mint-border-b mint-border-rule mint-bg-accent-wash mint-px-4 sm:mint-px-6 mint-py-2.5">
			<div class="mint-mx-auto mint-max-w-content mint-text-sm mint-font-medium mint-text-accent">
				<?php esc_html_e( 'Preview mode — this is how students see this lesson. Attempts and progress are not saved.', 'mint-lms' ); ?>
			</div>
		</div>
	<?php endif; ?>
	<div class="mint-border-b mint-border-rule mint-bg-bg mint-px-4 sm:mint-px-6 mint-py-4">
		<div class="mint-mx-auto mint-flex mint-max-w-content mint-flex-col mint-gap-4 sm:mint-flex-row sm:mint-items-center sm:mint-justify-between">
			<div class="mint-flex mint-min-w-0 mint-items-center mint-gap-2 mint-text-sm">
				<a href="<?php echo esc_url( $dashboardUrl ); ?>" class="mint-truncate mint-font-medium mint-text-ink-2 hover:mint-text-accent mint-no-underline mint-transition-colors mint-duration-hover"><?php echo esc_html( $context->courseTitle ); ?></a>
				<svg class="mint-h-3.5 mint-w-3.5 mint-shrink-0 mint-text-ink-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
				<span class="mint-truncate mint-font-medium mint-text-ink"><?php echo esc_html( $lesson->title ); ?></span>
			</div>
			<?php if ( $context->isEnrolled && empty( $context->isReviewMode ) ) : ?>
				<div class="mint-flex mint-min-w-[200px] mint-flex-col mint-gap-1.5 sm:mint-w-48">
					<span class="mint-text-xs mint-font-medium mint-text-ink-3" x-text="Math.round(progressPct) + '% complete'"></span>
					<progress
						class="mint-block mint-h-1.5 mint-w-full mint-appearance-none mint-overflow-hidden mint-rounded-full mint-bg-bg-track [&::-moz-progress-bar]:mint-rounded-full [&::-moz-progress-bar]:mint-bg-accent [&::-webkit-progress-bar]:mint-rounded-full [&::-webkit-progress-bar]:mint-bg-bg-track [&::-webkit-progress-value]:mint-rounded-full [&::-webkit-progress-value]:mint-bg-accent"
						:value="Math.round(progressPct)"
						max="100"
						aria-label="<?php esc_attr_e( 'Course progress', 'mint-lms' ); ?>"
					></progress>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="mint-mx-auto mint-grid mint-max-w-content mint-grid-cols-1 mint-gap-8 mint-px-4 mint-py-8 sm:mint-px-6 lg:mint-grid-cols-[1fr_280px]">

		<main class="mint-min-w-0">

			<?php if ( '' !== $videoEmbed ) : ?>
				<div class="mint-aspect-video mint-w-full mint-overflow-hidden mint-rounded-xl mint-border mint-border-rule mint-bg-bg-ink [&_iframe]:mint-h-full [&_iframe]:mint-w-full">
					<?php echo $videoEmbed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>

			<h1 class="mint-mt-6 mint-text-h2 mint-font-semibold mint-text-ink mint-tracking-tight"><?php echo esc_html( $lesson->title ); ?></h1>

			<?php if ( '' !== $lesson->content ) : ?>
				<div class="mint-mt-4 mint-text-prose mint-text-ink-2 [&_p]:mint-mb-4 [&_p:last-child]:mint-mb-0">
					<?php echo wp_kses_post( wpautop( $lesson->content ) ); ?>
				</div>
			<?php endif; ?>

			<?php if ( is_string( $attachmentUrl ) && '' !== $attachmentUrl ) : ?>
				<div class="mint-mt-6 mint-mb-6">
					<a href="<?php echo esc_url( $attachmentUrl ); ?>" class="mint-inline-flex mint-items-center mint-gap-2 mint-h-control mint-px-4 mint-text-sm mint-font-medium mint-rounded-md mint-border mint-border-control mint-bg-bg mint-text-ink hover:mint-bg-bg-hover mint-transition-colors mint-duration-hover mint-no-underline" download>
						<svg class="mint-h-4 mint-w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
						<?php esc_html_e( 'Download attachment', 'mint-lms' ); ?>
					</a>
				</div>
			<?php endif; ?>

			<?php
			$isReviewMode = ! empty( $context->isReviewMode );
			if ( $isReviewMode && null !== $context->quiz && '' !== $quizPreviewUrl ) :
				?>
				<div class="mint-mt-8 mint-rounded-xl mint-border mint-border-rule mint-bg-bg mint-p-6">
					<div class="mint-flex mint-flex-col mint-gap-3 sm:mint-flex-row sm:mint-items-center sm:mint-justify-between">
						<div>
							<div class="mint-text-over mint-font-semibold mint-uppercase mint-tracking-widest mint-text-ink"><?php echo esc_html( $context->quiz->title ); ?></div>
							<p class="mint-mt-1 mint-mb-0 mint-text-sm mint-text-ink-2">
								<?php
								printf(
									/* translators: %d: minimum pass percentage */
									esc_html__( 'Pass: %d%%', 'mint-lms' ),
									(int) $context->quiz->passPercent
								);
								?>
							</p>
						</div>
						<a href="<?php echo esc_url( $quizPreviewUrl ); ?>" class="mint-inline-flex mint-h-[42px] mint-items-center mint-justify-center mint-rounded-lg mint-border-0 mint-bg-cta mint-px-[18px] mint-text-sm mint-font-bold mint-text-ink mint-no-underline hover:mint-bg-cta-hover"><?php esc_html_e( 'Start quiz', 'mint-lms' ); ?></a>
					</div>
				</div>
				<?php
			elseif ( ! $isReviewMode ) :
				$quiz          = $context->quiz;
				$quizRequired  = $context->quizRequired;
				$hasPassedQuiz = $context->hasPassedQuiz;
				$isEnrolled    = $context->isEnrolled;
				require MINTLMS_PATH . 'templates/student/quiz.php';
			endif;
			?>

			<div class="mint-mt-8 mint-flex mint-flex-col mint-gap-3 sm:mint-flex-row sm:mint-items-center sm:mint-justify-between">
				<div>
					<?php if ( null !== $context->previousLessonId ) : ?>
						<a href="<?php echo esc_url( $lessonUrl( $context->previousLessonId ) ); ?>" class="mint-inline-flex mint-items-center mint-justify-center mint-h-control-md mint-px-4 mint-text-sm mint-font-medium mint-rounded-md mint-border mint-border-control mint-bg-bg mint-text-ink hover:mint-bg-bg-hover mint-transition-colors mint-duration-hover mint-no-underline">
							<?php esc_html_e( '← Previous', 'mint-lms' ); ?>
						</a>
					<?php endif; ?>
				</div>
				<div>
					<?php if ( $context->isEnrolled && $context->canMarkComplete ) : ?>
						<button
							type="button"
							class="mint-inline-flex mint-items-center mint-justify-center mint-h-control-md mint-px-5 mint-text-sm mint-font-medium mint-rounded-md mint-transition-colors mint-duration-hover"
							:class="isComplete ? 'mint-bg-success-wash mint-text-success mint-cursor-default' : 'mint-bg-accent mint-text-neutral-50 hover:mint-bg-accent-hover'"
							:disabled="isComplete || marking || (quizRequired && !hasPassedQuiz)"
							@click="markComplete()"
						>
							<span x-text="marking ? mintLmsStudent.i18n.marking : (isComplete ? mintLmsStudent.i18n.completed : (<?php echo null !== $context->nextLessonId ? 'true' : 'false'; ?> ? '<?php echo esc_js( __( 'Mark complete & continue', 'mint-lms' ) ); ?>' : mintLmsStudent.i18n.markComplete))"></span>
						</button>
					<?php elseif ( $context->isEnrolled && ! $context->canMarkComplete && empty( $context->isReviewMode ) ) : ?>
						<p class="mint-m-0 mint-text-sm mint-text-ink-3"><?php esc_html_e( 'Your instructor marks lessons complete.', 'mint-lms' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</main>

		<aside class="mint-min-w-0">
			<div class="mint-mb-4 mint-text-over mint-font-semibold mint-uppercase mint-tracking-widest mint-text-ink-3"><?php esc_html_e( 'CURRICULUM', 'mint-lms' ); ?></div>

			<?php foreach ( $context->structure->sections as $section ) : ?>
				<div class="mint-mb-5">
					<?php if ( $section->id > 0 && '' !== trim( $section->title ) ) : ?>
						<h3 class="mint-mb-2 mint-text-xs mint-font-semibold mint-uppercase mint-tracking-wide mint-text-ink-3"><?php echo esc_html( $section->title ); ?></h3>
					<?php endif; ?>
					<div class="mint-space-y-1">
						<?php
						foreach ( $section->lessons as $sectionLesson ) :
							$isCurrent   = $sectionLesson->id === $lesson->id;
							$isCompleted = in_array( $sectionLesson->id, $completedIds, true );
							$accessible  = ( $context->isEnrolled || $sectionLesson->isPreview || ! empty( $context->isReviewMode ) ) && ! $sectionLesson->isDripLocked;
							$hasContent  = $isCurrent || '' !== $sectionLesson->content || null !== $sectionLesson->videoUrl || $sectionLesson->isDripLocked;
							$lessonId    = (int) $sectionLesson->id;
							?>
							<?php if ( $accessible && $hasContent ) : ?>
								<a
									href="<?php echo esc_url( $lessonUrl( $sectionLesson->id ) ); ?>"
									class="mint-flex mint-items-center mint-gap-2.5 mint-rounded-md mint-px-2 mint-py-2 mint-text-sm mint-no-underline mint-transition-colors mint-duration-hover <?php echo $isCurrent ? 'mint-bg-accent-wash mint-text-accent' : 'mint-text-ink-2 hover:mint-bg-bg-hover hover:mint-text-ink'; ?>"
									<?php echo $isCurrent ? 'aria-current="page"' : ''; ?>
								>
									<div
										class="mint-flex mint-h-7 mint-w-7 mint-shrink-0 mint-items-center mint-justify-center mint-rounded-full mint-transition-colors mint-duration-hover"
										:class="completedIds.includes(<?php echo esc_attr( (string) $lessonId ); ?>) ? 'mint-bg-success-wash mint-text-success' : (<?php echo $isCurrent ? 'true' : 'false'; ?> ? 'mint-bg-accent mint-text-neutral-50' : 'mint-bg-bg-subtle mint-text-ink-3')"
									>
										<template x-if="completedIds.includes(<?php echo esc_attr( (string) $lessonId ); ?>)">
											<svg class="mint-h-3.5 mint-w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
										</template>
										<template x-if="!completedIds.includes(<?php echo esc_attr( (string) $lessonId ); ?>)">
											<?php if ( $isCurrent ) : ?>
												<svg class="mint-h-3 mint-w-3" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
											<?php else : ?>
												<svg class="mint-h-3 mint-w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
											<?php endif; ?>
										</template>
									</div>
									<span class="mint-min-w-0 mint-truncate mint-font-medium"><?php echo esc_html( $sectionLesson->title ); ?></span>
								</a>
							<?php else : ?>
								<div class="mint-flex mint-items-start mint-gap-2.5 mint-rounded-md mint-px-2 mint-py-2 mint-opacity-60" title="<?php echo esc_attr( $sectionLesson->dripMessage ?? '' ); ?>">
									<div class="mint-flex mint-h-7 mint-w-7 mint-shrink-0 mint-items-center mint-justify-center mint-rounded-full mint-bg-bg-subtle mint-text-ink-3">
										<svg class="mint-h-3 mint-w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
									</div>
									<div class="mint-min-w-0">
										<span class="mint-block mint-truncate mint-text-sm mint-font-medium mint-text-ink-2"><?php echo esc_html( $sectionLesson->title ); ?></span>
										<?php if ( $sectionLesson->isDripLocked && is_string( $sectionLesson->dripMessage ) && '' !== $sectionLesson->dripMessage ) : ?>
											<span class="mint-mt-0.5 mint-block mint-text-xs mint-text-ink-3"><?php echo esc_html( $sectionLesson->dripMessage ); ?></span>
										<?php endif; ?>
									</div>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</aside>
	</div>
</div>

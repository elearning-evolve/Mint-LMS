<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Instructor lesson preview — S3B student-style lesson page.
 *
 * @var array<string, mixed> $preview
 */
$preview             = $preview;
$quizzes             = is_array( $preview['quizzes'] ?? null ) ? $preview['quizzes'] : array();
$courseTitle         = trim( (string) ( $preview['courseTitle'] ?? '' ) );
$lessonTitle         = trim( (string) ( $preview['title'] ?? '' ) );
$authorName          = trim( (string) ( $preview['authorName'] ?? '' ) );
$lessonBody          = trim( (string) ( $preview['content'] ?? '' ) );
$previousLessonTitle = trim( (string) ( $preview['previousLessonTitle'] ?? '' ) );
$featuredUrl         = is_string( $featuredUrl ?? null ) ? $featuredUrl : '';
$courseUrl           = is_string( $courseUrl ?? null ) ? $courseUrl : '';
$previousUrl         = is_string( $previousUrl ?? null ) ? $previousUrl : '';
$nextLessonUrl       = is_string( $nextLessonUrl ?? null ) ? $nextLessonUrl : '';
$nextQuizUrl         = is_string( $nextQuizUrl ?? null ) ? $nextQuizUrl : '';
$displayLesson       = '' !== $lessonTitle ? $lessonTitle : __( 'Untitled lesson', 'mint-lms' );
$forwardUrl          = '' !== $nextQuizUrl ? $nextQuizUrl : $nextLessonUrl;
$forwardLabel        = '' !== $nextQuizUrl
	? __( 'Next Quiz', 'mint-lms' )
	: __( 'Next Lesson', 'mint-lms' );
?>

<div class="mint-s3b-wrap">
	<div class="mint-s3b-hero">
		<div class="mint-s3b-kicker"><?php esc_html_e( 'Lesson preview', 'mint-lms' ); ?></div>
		<h1 class="mint-s3b-title"><?php echo esc_html( $displayLesson ); ?></h1>

		<?php if ( '' !== $featuredUrl ) : ?>
			<div class="mint-s3b-media-frame">
				<div class="mint-s3b-media">
					<img src="<?php echo esc_url( $featuredUrl ); ?>" alt="<?php echo esc_attr( $displayLesson ); ?>" />
				</div>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $authorName ) : ?>
			<div class="mint-s3b-author">
				<?php esc_html_e( 'Written by', 'mint-lms' ); ?>
				<span class="mint-underline"><?php echo esc_html( $authorName ); ?></span>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( '' !== $courseTitle || '' !== $displayLesson ) : ?>
		<div class="mint-s3b-progress-row">
			<div class="mint-s3b-progress">
				<div class="mint-s3b-progress__crumb">
					<?php if ( '' !== $courseTitle ) : ?>
						<?php echo esc_html( $courseTitle ); ?>
						<span aria-hidden="true"> › </span>
					<?php endif; ?>
					<?php echo esc_html( $displayLesson ); ?>
				</div>
				<div class="mint-s3b-progress__badge"><?php esc_html_e( 'In progress', 'mint-lms' ); ?></div>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( '' !== $lessonBody ) : ?>
		<div class="mint-s3b-body-card">
			<div class="mint-course-overview__description mint-text-[15px] mint-leading-6 mint-text-[#33334A] [&_p]:mint-mb-3 [&_p:last-child]:mint-mb-0">
				<?php echo wp_kses_post( wpautop( $lessonBody ) ); ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( array() !== $quizzes ) : ?>
		<div class="mint-s3b-stack">
			<div class="mint-s3b-stack__head">
				<span class="mint-s3b-stack__icon" aria-hidden="true">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
				</span>
				<span><?php esc_html_e( 'Lesson Content', 'mint-lms' ); ?></span>
			</div>

			<?php foreach ( $quizzes as $quiz ) : ?>
				<?php
				$quizTitle     = (string) ( $quiz['title'] ?? __( 'Quiz', 'mint-lms' ) );
				$questions     = is_array( $quiz['questions'] ?? null ) ? $quiz['questions'] : array();
				$questionCount = count( $questions );
				$quizMeta      = trim( (string) ( $quiz['meta'] ?? '' ) );
				if ( '' === $quizMeta ) {
					$quizMeta = 1 === $questionCount
						? __( '1 question', 'mint-lms' )
						: sprintf(
							/* translators: %d: question count */
							__( '%d questions', 'mint-lms' ),
							$questionCount
						);
				}
				?>
				<details class="mint-s3b-stack__item">
					<summary class="mint-s3b-stack__quiz">
						<span class="mint-s3b-stack__icon mint-s3b-stack__icon--quiz" aria-hidden="true">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
						</span>
						<div class="mint-s3b-stack__quiz-copy">
							<div class="mint-s3b-stack__quiz-title"><?php echo esc_html( $quizTitle ); ?></div>
							<div class="mint-s3b-stack__quiz-meta">
								<span class="mint-s3b-badge mint-s3b-badge--sm"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></span>
								<span><?php echo esc_html( $quizMeta ); ?></span>
							</div>
						</div>
						<span class="mint-s3b-expand">
							<span class="mint-s3b-expand__label mint-s3b-expand__label--open"><?php esc_html_e( 'Expand', 'mint-lms' ); ?></span>
							<span class="mint-s3b-expand__label mint-s3b-expand__label--close"><?php esc_html_e( 'Collapse', 'mint-lms' ); ?></span>
							<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<path class="mint-s3b-expand__chevron--open" d="M5.5 8 10 12.5 14.5 8" />
								<path class="mint-s3b-expand__chevron--close" d="M5.5 12 10 7.5 14.5 12" />
							</svg>
						</span>
					</summary>
					<div class="mint-s3-quiz__body">
						<?php if ( array() === $questions ) : ?>
							<div class="mint-s3-question">
								<div class="mint-text-[13px] mint-text-[#5C5C77]"><?php esc_html_e( 'No questions yet.', 'mint-lms' ); ?></div>
							</div>
						<?php else : ?>
							<?php foreach ( $questions as $question ) : ?>
								<div class="mint-s3-question">
									<div class="mint-shrink-0 mint-rounded-full mint-bg-white mint-px-1.5 mint-py-0.5 mint-text-[9px] mint-font-bold mint-uppercase mint-tracking-[0.06em] mint-text-cta-ink">Q</div>
									<div class="mint-text-[13px] mint-text-[#17222B]"><?php echo esc_html( (string) ( $question['text'] ?? '' ) ); ?></div>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</details>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="mint-s3b-nav">
		<?php if ( '' !== $previousUrl ) : ?>
			<a href="<?php echo esc_url( $previousUrl ); ?>" class="mint-s3b-nav__prev">‹ <?php esc_html_e( 'Previous Lesson', 'mint-lms' ); ?></a>
		<?php else : ?>
			<span></span>
		<?php endif; ?>

		<?php if ( '' !== $courseUrl ) : ?>
			<a href="<?php echo esc_url( $courseUrl ); ?>" class="mint-s3b-nav__back"><?php esc_html_e( 'Back to Course', 'mint-lms' ); ?></a>
		<?php else : ?>
			<span></span>
		<?php endif; ?>

		<?php if ( '' !== $forwardUrl ) : ?>
			<a href="<?php echo esc_url( $forwardUrl ); ?>" class="mint-s3b-nav__next"><?php echo esc_html( $forwardLabel ); ?> ›</a>
		<?php else : ?>
			<span></span>
		<?php endif; ?>
	</div>

	<?php if ( '' !== $previousUrl && '' !== $previousLessonTitle ) : ?>
		<div class="mint-s3b-nav__hint">— <?php echo esc_html( $previousLessonTitle ); ?></div>
	<?php endif; ?>
</div>

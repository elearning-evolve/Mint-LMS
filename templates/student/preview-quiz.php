<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Quiz\Dto\QuizDto;
use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * Instructor / student quiz runner — S3B canvas (attempts not saved in preview).
 *
 * @var QuizDto|null $quiz
 * @var string       $courseUrl
 * @var string       $lessonUrl
 * @var string       $courseTitle
 * @var string       $lessonTitle
 */
$quiz        = $quiz ?? null;
$courseUrl   = is_string( $courseUrl ?? null ) ? $courseUrl : '';
$lessonUrl   = is_string( $lessonUrl ?? null ) ? $lessonUrl : '';
$courseTitle = trim( (string) ( $courseTitle ?? '' ) );
$lessonTitle = trim( (string) ( $lessonTitle ?? '' ) );

$quizRequired  = true;
$hasPassedQuiz = false;
$isEnrolled    = true;
$isReviewMode  = true;
$quizHeading   = false;

$displayQuiz = $quiz instanceof QuizDto && '' !== trim( $quiz->title )
	? trim( $quiz->title )
	: __( 'Untitled quiz', 'mint-lms' );
$questions     = $quiz instanceof QuizDto ? $quiz->questions : array();
$questionCount = count( $questions );
$passPercent   = $quiz instanceof QuizDto ? (int) $quiz->passPercent : 0;
$quizMeta      = 1 === $questionCount
	? __( '1 question', 'mint-lms' )
	: sprintf(
		/* translators: %d: question count */
		__( '%d questions', 'mint-lms' ),
		$questionCount
	);
$associated    = '' !== $lessonTitle;
$previewBoot   = wp_json_encode(
	array(
		'quizTitle'      => $displayQuiz,
		'quizMeta'       => $quizMeta,
		'quizKicker'     => __( 'Quiz', 'mint-lms' ),
		'questionKicker' => __( 'Question', 'mint-lms' ),
	)
);
if ( ! is_string( $previewBoot ) ) {
	$previewBoot = '{}';
}
?>

<div
	class="mint-s3b-wrap"
	x-data="mintQuizPreview(<?php echo esc_attr( $previewBoot ); ?>)"
	@mint-quiz-progress="onQuizProgress($event.detail)"
>
	<article class="mint-s3-hero mint-s3-hero--wide">
		<div class="mint-s3b-kicker" x-text="headerKicker"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></div>
		<h1 class="mint-s3b-title" x-text="headerTitle"><?php echo esc_html( $displayQuiz ); ?></h1>

		<?php if ( $associated ) : ?>
			<p class="mint-s3-quiz-associate">
				<?php esc_html_e( 'Associated with', 'mint-lms' ); ?>
				<?php if ( '' !== $lessonUrl ) : ?>
					<a href="<?php echo esc_url( $lessonUrl ); ?>" class="mint-s3b-progress__crumb-link"><?php echo esc_html( $lessonTitle ); ?></a>
				<?php else : ?>
					<span><?php echo esc_html( $lessonTitle ); ?></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<?php if ( '' !== $displayQuiz ) : ?>
			<div class="mint-s3b-progress-row">
				<button
					type="button"
					class="mint-s3b-progress mint-s3b-progress--quiz-home"
					@click="started = false"
				>
					<span class="mint-s3b-progress__crumb" x-text="headerTitle"><?php echo esc_html( $displayQuiz ); ?></span>
					<span class="mint-s3b-progress__badge" x-text="headerBadge"><?php echo esc_html( $quizMeta ); ?></span>
				</button>
			</div>
		<?php endif; ?>
	</article>

	<section class="mint-s3-content">
		<div x-show="!started">
			<div>
				<h2 class="mint-m-0 mint-text-xl mint-font-semibold mint-tracking-[-0.02em] mint-text-ink"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></h2>
				<p class="mint-m-0 mint-mt-1 mint-text-sm mint-text-[#33334A]">
					<?php
					if ( $passPercent > 0 ) {
						printf(
							/* translators: %d: passing percentage */
							esc_html__( 'Answer each question. Passing score: %d%%.', 'mint-lms' ),
							$passPercent
						);
					} else {
						esc_html_e( 'Answer each question, then submit the quiz.', 'mint-lms' );
					}
					?>
				</p>
			</div>

			<div class="mint-s3-curriculum">
				<div class="mint-s3-rail--quiz">
					<details class="mint-s3-quiz mint-s3b-stack__item" <?php echo array() !== $questions ? 'open' : ''; ?>>
						<summary class="mint-s3b-stack__quiz">
							<span class="mint-s3b-stack__icon mint-s3b-stack__icon--quiz" aria-hidden="true">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
							</span>
							<div class="mint-s3b-stack__quiz-copy">
								<div class="mint-s3b-stack__quiz-title">
									<button
										type="button"
										class="mint-s3-quiz-name-link"
										@click.stop="started = false"
									><?php echo esc_html( $displayQuiz ); ?></button>
								</div>
								<div class="mint-s3b-stack__quiz-meta">
									<span class="mint-s3b-badge mint-s3b-badge--sm"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></span>
									<span><?php echo esc_html( $quizMeta ); ?></span>
								</div>
							</div>
							<?php if ( array() !== $questions ) : ?>
							<span class="mint-s3b-expand">
								<span class="mint-s3b-expand__label mint-s3b-expand__label--open"><?php esc_html_e( 'Expand', 'mint-lms' ); ?></span>
								<span class="mint-s3b-expand__label mint-s3b-expand__label--close"><?php esc_html_e( 'Collapse', 'mint-lms' ); ?></span>
								<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
									<path class="mint-s3b-expand__chevron--open" d="M5.5 8 10 12.5 14.5 8" />
									<path class="mint-s3b-expand__chevron--close" d="M5.5 12 10 7.5 14.5 12" />
								</svg>
							</span>
							<?php endif; ?>
						</summary>
						<?php if ( array() !== $questions ) : ?>
						<div class="mint-s3-quiz__body">
							<?php foreach ( $questions as $question ) : ?>
								<?php $questionName = PostTypes::questionContentsTitle( (int) $question->id, (string) $question->prompt ); ?>
								<div class="mint-s3-row mint-s3-row--nested">
									<span class="mint-s3b-stack__icon mint-s3b-stack__icon--quiz" aria-hidden="true">
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
									</span>
									<div class="mint-s3b-stack__quiz-copy">
										<div class="mint-s3b-stack__quiz-title"><?php echo esc_html( $questionName ); ?></div>
										<div class="mint-s3b-stack__quiz-meta">
											<span class="mint-s3b-badge mint-s3b-badge--sm"><?php esc_html_e( 'Question', 'mint-lms' ); ?></span>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
						<?php endif; ?>
					</details>
				</div>
			</div>

			<?php if ( $quiz instanceof QuizDto && array() !== $questions ) : ?>
				<div class="mint-s3-quiz-start">
					<button type="button" class="mint-s3b-nav__next" @click="started = true">
						<?php esc_html_e( 'Start a Quiz', 'mint-lms' ); ?>
					</button>
				</div>
			<?php else : ?>
				<div class="mint-s3-empty"><?php esc_html_e( 'No questions in this quiz yet.', 'mint-lms' ); ?></div>
			<?php endif; ?>
		</div>

		<div x-show="started" x-cloak>
			<?php if ( $quiz instanceof QuizDto && array() !== $questions ) : ?>
				<?php require MINTLMS_PATH . 'templates/student/quiz.php'; ?>
			<?php endif; ?>
		</div>

		<div class="mint-s3b-nav">
			<?php if ( $associated && '' !== $lessonUrl ) : ?>
				<a href="<?php echo esc_url( $lessonUrl ); ?>" class="mint-s3b-nav__back">‹ <?php esc_html_e( 'Back to Lesson', 'mint-lms' ); ?></a>
			<?php elseif ( '' !== $courseUrl ) : ?>
				<a href="<?php echo esc_url( $courseUrl ); ?>" class="mint-s3b-nav__back">‹ <?php esc_html_e( 'Back to Course', 'mint-lms' ); ?></a>
			<?php else : ?>
				<span></span>
			<?php endif; ?>
			<span></span>
			<span></span>
		</div>
	</section>
</div>

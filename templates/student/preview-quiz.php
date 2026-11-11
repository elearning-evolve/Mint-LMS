<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Quiz\Dto\QuizDto;

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
$questionCount = $quiz instanceof QuizDto ? count( $quiz->questions ) : 0;
$passPercent   = $quiz instanceof QuizDto ? (int) $quiz->passPercent : 0;
$quizMeta      = 1 === $questionCount
	? __( '1 question', 'mint-lms' )
	: sprintf(
		/* translators: %d: question count */
		__( '%d questions', 'mint-lms' ),
		$questionCount
	);
?>

<div class="mint-s3b-wrap">
	<article class="mint-s3-hero mint-s3-hero--wide">
		<div class="mint-s3b-kicker"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></div>
		<h1 class="mint-s3b-title"><?php echo esc_html( $displayQuiz ); ?></h1>

		<?php if ( '' !== $courseTitle || '' !== $lessonTitle || '' !== $displayQuiz ) : ?>
			<div class="mint-s3b-progress-row">
				<div class="mint-s3b-progress">
					<div class="mint-s3b-progress__crumb">
						<?php if ( '' !== $courseTitle ) : ?>
							<?php if ( '' !== $courseUrl ) : ?>
								<a href="<?php echo esc_url( $courseUrl ); ?>" class="mint-s3b-progress__crumb-link"><?php echo esc_html( $courseTitle ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $courseTitle ); ?>
							<?php endif; ?>
							<span aria-hidden="true"> › </span>
						<?php endif; ?>
						<?php if ( '' !== $lessonTitle ) : ?>
							<?php if ( '' !== $lessonUrl ) : ?>
								<a href="<?php echo esc_url( $lessonUrl ); ?>" class="mint-s3b-progress__crumb-link"><?php echo esc_html( $lessonTitle ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $lessonTitle ); ?>
							<?php endif; ?>
							<span aria-hidden="true"> › </span>
						<?php endif; ?>
						<?php echo esc_html( $displayQuiz ); ?>
					</div>
					<div class="mint-s3b-progress__badge"><?php echo esc_html( $quizMeta ); ?></div>
				</div>
			</div>
		<?php endif; ?>
	</article>

	<section class="mint-s3-content">
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

		<?php if ( ! $quiz instanceof QuizDto || array() === $quiz->questions ) : ?>
			<div class="mint-s3-empty"><?php esc_html_e( 'No questions in this quiz yet.', 'mint-lms' ); ?></div>
		<?php else : ?>
			<?php require MINTLMS_PATH . 'templates/student/quiz.php'; ?>
		<?php endif; ?>

		<div class="mint-s3b-nav">
			<?php if ( '' !== $lessonUrl ) : ?>
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

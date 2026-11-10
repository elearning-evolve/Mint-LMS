<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Quiz\Dto\QuizDto;

/**
 * Instructor quiz preview — live student quiz runner (attempts are not saved).
 *
 * @var QuizDto|null $quiz
 * @var string       $courseUrl
 * @var string       $lessonUrl
 */
$quiz      = $quiz ?? null;
$courseUrl = is_string( $courseUrl ?? null ) ? $courseUrl : '';
$lessonUrl = is_string( $lessonUrl ?? null ) ? $lessonUrl : '';

$quizRequired  = true;
$hasPassedQuiz = false;
$isEnrolled    = true;
$isReviewMode  = true;
?>

<div class="mint-s3b-wrap">
	<div class="mint-border mint-border-rule mint-bg-accent-wash mint-px-4 mint-py-2.5 mint-rounded-lg mint-mb-5 mint-text-sm mint-font-medium mint-text-accent">
		<?php esc_html_e( 'Preview mode — take this quiz as a student. Your attempt is not saved.', 'mint-lms' ); ?>
	</div>

	<?php if ( ! $quiz instanceof QuizDto || array() === $quiz->questions ) : ?>
		<div class="mint-rounded-xl mint-border mint-border-rule mint-bg-bg mint-px-6 mint-py-8 mint-text-[15px] mint-text-ink-2">
			<?php esc_html_e( 'This quiz has no questions yet.', 'mint-lms' ); ?>
		</div>
	<?php else : ?>
		<?php require MINTLMS_PATH . 'templates/student/quiz.php'; ?>
	<?php endif; ?>

	<?php if ( '' !== $lessonUrl || '' !== $courseUrl ) : ?>
		<div class="mint-mt-6 mint-flex mint-flex-wrap mint-gap-4">
			<?php if ( '' !== $lessonUrl ) : ?>
				<a href="<?php echo esc_url( $lessonUrl ); ?>" class="mint-s3b-nav__back">← <?php esc_html_e( 'Back to lesson', 'mint-lms' ); ?></a>
			<?php endif; ?>
			<?php if ( '' !== $courseUrl ) : ?>
				<a href="<?php echo esc_url( $courseUrl ); ?>" class="mint-s3b-nav__back">← <?php esc_html_e( 'Back to course', 'mint-lms' ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>

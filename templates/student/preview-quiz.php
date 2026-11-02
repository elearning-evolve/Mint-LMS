<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * S3C Quiz preview — same student shell as lesson preview (hero + progress + expandable card).
 *
 * @var array{
 *   title: string,
 *   meta: string,
 *   questions: list<array{id: int, text: string}>,
 *   courseTitle: string,
 *   authorName?: string,
 *   progressPct?: float
 * } $preview
 */
$preview     = $preview;
$questions   = is_array( $preview['questions'] ?? null ) ? $preview['questions'] : array();
$courseTitle = trim( (string) ( $preview['courseTitle'] ?? '' ) );
$quizTitle   = trim( (string) ( $preview['title'] ?? '' ) );
$authorName  = trim( (string) ( $preview['authorName'] ?? '' ) );
$pct         = isset( $preview['progressPct'] ) ? (int) round( (float) $preview['progressPct'] ) : 0;
$quizMeta    = trim( (string) ( $preview['meta'] ?? '' ) );
if ( '' === $quizMeta ) {
	$quizMeta = __( 'No questions yet', 'mint-lms' );
}
$displayQuiz = '' !== $quizTitle ? $quizTitle : __( 'Untitled quiz', 'mint-lms' );
$courseUrl   = is_string( $courseUrl ?? null ) ? $courseUrl : '';
?>

<div
	class="mint-s3b-wrap"
	x-data="{ open: {}, isOpen(k){ return !!this.open[k] }, toggle(k){ this.open = { ...this.open, [k]: !this.open[k] } } }"
>
	<article class="mint-s3-hero">
		<h1 class="mint-m-0 mint-text-[32px] mint-font-semibold mint-leading-[38px] mint-tracking-[-0.03em] mint-text-ink">
			<?php echo esc_html( $displayQuiz ); ?>
		</h1>

		<?php if ( '' !== $authorName ) : ?>
			<div class="mint-mt-[18px] mint-text-sm mint-text-[#5C5C77]">
				<?php esc_html_e( 'Written by', 'mint-lms' ); ?>
				<span class="mint-font-medium mint-text-cta-ink mint-underline"><?php echo esc_html( $authorName ); ?></span>
			</div>
		<?php endif; ?>
	</article>

	<div class="mint-s3b-progress">
		<div class="mint-min-w-0">
			<div class="mint-s3b-progress__crumb">
				<?php if ( '' !== $courseTitle ) : ?>
					<?php echo esc_html( $courseTitle ); ?>
					<span class="mint-mx-1" aria-hidden="true">›</span>
				<?php endif; ?>
				<?php echo esc_html( $displayQuiz ); ?>
			</div>
			<div class="mint-s3b-progress__pct">
				<?php
				printf(
					/* translators: %d: progress percentage */
					esc_html__( '%d%% complete', 'mint-lms' ),
					absint( $pct )
				);
				?>
			</div>
		</div>
		<div class="mint-s3b-progress__badge"><?php esc_html_e( 'In progress', 'mint-lms' ); ?></div>
	</div>

	<div class="mint-s3b-lesson-card">
		<div class="mint-s3b-lesson-card__head">
			<div class="mint-s3b-badge"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></div>
			<div class="mint-min-w-0 mint-flex-1">
				<div class="mint-s3b-lesson-card__name"><?php echo esc_html( $displayQuiz ); ?></div>
				<div class="mint-s3b-lesson-card__meta"><?php echo esc_html( $quizMeta ); ?></div>
			</div>
			<button
				type="button"
				class="mint-s3b-expand"
				@click.stop="toggle('quiz')"
				:aria-expanded="isOpen('quiz') ? 'true' : 'false'"
			>
				<span x-text="isOpen('quiz') ? '<?php echo esc_js( __( 'Collapse', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Expand', 'mint-lms' ) ); ?>'"><?php esc_html_e( 'Expand', 'mint-lms' ); ?></span>
				<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path :d="isOpen('quiz') ? 'M5.5 12 10 7.5 14.5 12' : 'M5.5 8 10 12.5 14.5 8'" d="M5.5 8 10 12.5 14.5 8" />
				</svg>
			</button>
		</div>

		<div class="mint-s3b-lesson-card__body" x-show="isOpen('quiz')" x-cloak>
			<?php if ( array() === $questions ) : ?>
				<div class="mint-px-1 mint-py-2 mint-text-[13px] mint-text-[#5C5C77]"><?php esc_html_e( 'No questions yet.', 'mint-lms' ); ?></div>
			<?php else : ?>
				<?php foreach ( $questions as $question ) : ?>
					<div class="mint-s3-question">
						<div class="mint-shrink-0 mint-rounded-full mint-bg-white mint-px-1.5 mint-py-0.5 mint-text-[9px] mint-font-bold mint-uppercase mint-tracking-[0.06em] mint-text-cta-ink"><?php esc_html_e( 'Q', 'mint-lms' ); ?></div>
						<div class="mint-text-[13px] mint-text-[#17222B]"><?php echo esc_html( (string) ( $question['text'] ?? '' ) ); ?></div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="mint-s3b-nav">
		<?php if ( '' !== $courseUrl ) : ?>
			<a href="<?php echo esc_url( $courseUrl ); ?>" class="mint-s3b-nav__back">← <?php esc_html_e( 'Back to course', 'mint-lms' ); ?></a>
		<?php else : ?>
			<span></span>
		<?php endif; ?>
		<span class="mint-s3b-nav__next mint-s3b-nav__next--disabled"><?php esc_html_e( 'Next lesson group', 'mint-lms' ); ?> →</span>
	</div>
</div>

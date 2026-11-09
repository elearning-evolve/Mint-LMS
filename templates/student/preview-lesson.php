<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * S3B Lesson preview — student view when opening a course-associated lesson.
 *
 * @var array{
 *   title: string,
 *   meta: string,
 *   quizzes: list<array<string, mixed>>,
 *   content?: string,
 *   courseTitle: string,
 *   sectionTitle?: string,
 *   featuredImageId?: ?int,
 *   authorName?: string,
 *   progressPct?: float
 * } $preview
 */
$preview     = $preview;
$quizzes     = is_array( $preview['quizzes'] ?? null ) ? $preview['quizzes'] : array();
$courseTitle = trim( (string) ( $preview['courseTitle'] ?? '' ) );
$lessonTitle = trim( (string) ( $preview['title'] ?? '' ) );
$authorName  = trim( (string) ( $preview['authorName'] ?? '' ) );
$lessonBody  = trim( (string) ( $preview['content'] ?? '' ) );
$featuredUrl = is_string( $featuredUrl ?? null ) ? $featuredUrl : '';
$courseUrl   = is_string( $courseUrl ?? null ) ? $courseUrl : '';
$nextUrl     = is_string( $nextUrl ?? null ) ? $nextUrl : '';
$pct         = isset( $preview['progressPct'] ) ? (int) round( (float) $preview['progressPct'] ) : 0;
$lessonMeta  = trim( (string) ( $preview['meta'] ?? '' ) );
if ( '' === $lessonMeta ) {
	$lessonMeta = __( 'No quiz yet', 'mint-lms' );
}
$displayLesson = '' !== $lessonTitle ? $lessonTitle : __( 'Untitled lesson', 'mint-lms' );
?>

<div
	class="mint-s3b-wrap"
	x-data="{ open: {}, isOpen(k){ return !!this.open[k] }, toggle(k){ this.open = { ...this.open, [k]: !this.open[k] } } }"
>
	<?php /* Same hero card pattern as course overview (620px mint card) */ ?>
	<article class="mint-s3-hero">
		<h1 class="mint-m-0 mint-text-[32px] mint-font-semibold mint-leading-[38px] mint-tracking-[-0.03em] mint-text-ink">
			<?php echo esc_html( $displayLesson ); ?>
		</h1>

		<?php if ( '' !== $featuredUrl ) : ?>
			<div class="mint-s3-hero__media">
				<img
					src="<?php echo esc_url( $featuredUrl ); ?>"
					alt="<?php echo esc_attr( $displayLesson ); ?>"
				/>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $authorName ) : ?>
			<div class="mint-mt-[18px] mint-text-sm mint-text-[#5C5C77]">
				<?php esc_html_e( 'Written by', 'mint-lms' ); ?>
				<span class="mint-font-medium mint-text-cta-ink mint-underline"><?php echo esc_html( $authorName ); ?></span>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $lessonBody ) : ?>
			<div class="mint-course-overview__description mint-mt-4 mint-max-w-full mint-text-[15px] mint-leading-6 mint-text-[#33334A] [&_p]:mint-mb-3 [&_p:last-child]:mint-mb-0">
				<?php echo wp_kses_post( wpautop( $lessonBody ) ); ?>
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
				<?php echo esc_html( $displayLesson ); ?>
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
			<div class="mint-s3b-badge"><?php esc_html_e( 'Lesson', 'mint-lms' ); ?></div>
			<div class="mint-min-w-0 mint-flex-1">
				<div class="mint-s3b-lesson-card__name"><?php echo esc_html( $displayLesson ); ?></div>
				<div class="mint-s3b-lesson-card__meta"><?php echo esc_html( $lessonMeta ); ?></div>
			</div>
			<button
				type="button"
				class="mint-s3b-expand"
				@click.stop="toggle('lesson')"
				:aria-expanded="isOpen('lesson') ? 'true' : 'false'"
			>
				<span x-text="isOpen('lesson') ? '<?php echo esc_js( __( 'Collapse', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Expand', 'mint-lms' ) ); ?>'"><?php esc_html_e( 'Expand', 'mint-lms' ); ?></span>
				<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path :d="isOpen('lesson') ? 'M5.5 12 10 7.5 14.5 12' : 'M5.5 8 10 12.5 14.5 8'" d="M5.5 8 10 12.5 14.5 8" />
				</svg>
			</button>
		</div>

		<div class="mint-s3b-lesson-card__body" x-show="isOpen('lesson')" x-cloak>
			<?php if ( array() === $quizzes ) : ?>
				<div class="mint-px-1 mint-py-2 mint-text-[13px] mint-text-[#5C5C77]"><?php esc_html_e( 'No quiz yet.', 'mint-lms' ); ?></div>
			<?php else : ?>
				<?php
				foreach ( $quizzes as $quiz ) :
					$quizId        = (int) ( $quiz['id'] ?? 0 );
					$quizKey       = 'quiz-' . $quizId;
					$quizTitle     = (string) ( $quiz['title'] ?? __( 'Quiz', 'mint-lms' ) );
					$quizMeta      = (string) ( $quiz['meta'] ?? '' );
					$quizQuestions = is_array( $quiz['questions'] ?? null ) ? $quiz['questions'] : array();
					?>
					<div class="mint-s3-rail--quiz">
						<div class="mint-s3-quiz">
							<div class="mint-flex mint-items-center mint-gap-2.5 mint-px-3.5 mint-py-[11px]">
								<div class="mint-s3b-badge mint-s3b-badge--sm"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></div>
								<div class="mint-min-w-0 mint-flex-1">
									<div class="mint-text-sm mint-font-semibold mint-text-[#17222B]"><?php echo esc_html( $quizTitle ); ?></div>
									<div class="mint-mt-0.5 mint-text-xs mint-text-[#5C5C77]"><?php echo esc_html( $quizMeta ); ?></div>
								</div>
								<?php if ( array() !== $quizQuestions ) : ?>
									<button
										type="button"
										class="mint-s3b-expand mint-s3b-expand--sm"
										@click.stop="toggle('<?php echo esc_js( $quizKey ); ?>')"
										:aria-expanded="isOpen('<?php echo esc_js( $quizKey ); ?>') ? 'true' : 'false'"
									>
										<span x-text="isOpen('<?php echo esc_js( $quizKey ); ?>') ? '<?php echo esc_js( __( 'Collapse', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Expand', 'mint-lms' ) ); ?>'"><?php esc_html_e( 'Expand', 'mint-lms' ); ?></span>
										<svg width="9" height="9" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
											<path :d="isOpen('<?php echo esc_js( $quizKey ); ?>') ? 'M5.5 12 10 7.5 14.5 12' : 'M5.5 8 10 12.5 14.5 8'" d="M5.5 8 10 12.5 14.5 8" />
										</svg>
									</button>
								<?php endif; ?>
							</div>
							<?php if ( array() !== $quizQuestions ) : ?>
								<div class="mint-s3-quiz__body" x-show="isOpen('<?php echo esc_js( $quizKey ); ?>')" x-cloak>
									<?php foreach ( $quizQuestions as $question ) : ?>
										<div class="mint-s3-question">
											<div class="mint-shrink-0 mint-rounded-full mint-bg-white mint-px-1.5 mint-py-0.5 mint-text-[9px] mint-font-bold mint-uppercase mint-tracking-[0.06em] mint-text-cta-ink">Q</div>
											<div class="mint-text-[13px] mint-text-[#17222B]"><?php echo esc_html( (string) ( $question['text'] ?? '' ) ); ?></div>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
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

		<?php if ( '' !== $nextUrl ) : ?>
			<a href="<?php echo esc_url( $nextUrl ); ?>" class="mint-s3b-nav__next"><?php esc_html_e( 'Next lesson group', 'mint-lms' ); ?> →</a>
		<?php else : ?>
			<span class="mint-s3b-nav__next mint-s3b-nav__next--disabled"><?php esc_html_e( 'Next lesson group', 'mint-lms' ); ?> →</span>
		<?php endif; ?>
	</div>
</div>

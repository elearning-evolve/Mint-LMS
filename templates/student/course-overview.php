<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Student\Dto\StudentCourseOverviewDto;

/** @var StudentCourseOverviewDto $overview */
$overview      = $overview;
$playerUrl     = $playerUrl ?? home_url( '/' );
$enrollUrl     = $enrollUrl ?? '';
$enrollMessage = $enrollMessage ?? '';
$purchaseUrl   = $purchaseUrl ?? '';
$productPrice  = $productPrice ?? '';
$featuredUrl   = is_string( $featuredUrl ?? null ) ? $featuredUrl : '';
$categoryName  = is_string( $categoryName ?? null ) ? trim( $categoryName ) : '';

$startLessonId = $overview->lastLessonId ?? $overview->firstLessonId;
$continueUrl   = $startLessonId
	? add_query_arg(
		array(
			'mint_course' => (string) $overview->courseId,
			'mint_lesson' => (string) $startLessonId,
		),
		$playerUrl
	)
	: $playerUrl;

$pct     = null !== $overview->progressPct ? (int) round( $overview->progressPct ) : 0;
$outline = is_array( $overview->contentOutline ) ? $overview->contentOutline : array();

$lessonPlayerUrls = array();
foreach ( $outline as $section ) {
	foreach ( $section['lessons'] as $lesson ) {
		$lessonPlayerUrls[ (int) $lesson['id'] ] = add_query_arg(
			array(
				'mint_course' => (string) $overview->courseId,
				'mint_lesson' => (string) $lesson['id'],
			),
			$playerUrl
		);
	}
}

$showCta = $overview->isEnrolled || $overview->canEnroll || ( 'paid' === $overview->enrollmentType && '' !== $purchaseUrl ) || ! $overview->isLoggedIn;
?>

<?php if ( '' !== $enrollMessage ) : ?>
	<div class="mint-s3-wrap mint-mb-4" style="padding-bottom: 0">
		<div class="mint-rounded-lg mint-border mint-border-success/20 mint-bg-success-wash mint-px-4 mint-py-3 mint-text-sm mint-font-medium mint-text-success" role="status"><?php echo esc_html( $enrollMessage ); ?></div>
	</div>
<?php endif; ?>
<?php if ( isset( $_GET['mint_enroll_error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
	<div class="mint-s3-wrap mint-mb-4" style="padding-bottom: 0">
		<div class="mint-rounded-lg mint-border mint-border-danger/20 mint-bg-danger-wash mint-px-4 mint-py-3 mint-text-sm mint-font-medium mint-text-danger" role="alert"><?php esc_html_e( 'Unable to enroll. This course may require purchase or manual enrollment.', 'mint-lms' ); ?></div>
	</div>
<?php endif; ?>

<div
	class="mint-s3-wrap"
	x-data="mintCourseOverview({})"
>
	<?php /* S3 hero card — max-width 620px, padding 32/36/40, centered */ ?>
	<article class="mint-s3-hero">
		<h1 class="mint-m-0 mint-text-[32px] mint-font-semibold mint-leading-[38px] mint-tracking-[-0.03em] mint-text-ink">
			<?php echo esc_html( $overview->title ); ?>
		</h1>

		<?php if ( '' !== $featuredUrl ) : ?>
			<div class="mint-s3-hero__media">
				<img
					src="<?php echo esc_url( $featuredUrl ); ?>"
					alt="<?php echo esc_attr( $overview->title ); ?>"
				/>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $overview->authorName || '' !== $categoryName ) : ?>
			<div class="mint-mt-[18px] mint-text-sm mint-text-[#5C5C77]">
				<?php if ( '' !== $overview->authorName ) : ?>
					<?php esc_html_e( 'Written by', 'mint-lms' ); ?>
					<span class="mint-font-medium mint-text-ink mint-underline"><?php echo esc_html( $overview->authorName ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $categoryName ) : ?>
					<?php if ( '' !== $overview->authorName ) : ?>
						<?php echo esc_html( ' ' . __( 'in', 'mint-lms' ) . ' ' ); ?>
					<?php endif; ?>
					<span class="mint-font-medium mint-text-ink mint-underline"><?php echo esc_html( $categoryName ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $overview->description ) : ?>
			<div class="mint-course-overview__description mint-mt-4 mint-max-w-full mint-text-[15px] mint-leading-6 mint-text-[#33334A] [&_p]:mint-mb-3 [&_p:last-child]:mint-mb-0">
				<?php echo wp_kses_post( wpautop( $overview->description ) ); ?>
			</div>
		<?php endif; ?>

		<div class="mint-mt-5 mint-flex mint-flex-wrap mint-items-center mint-gap-3.5 mint-rounded-[10px] mint-bg-[#F1FFF9] mint-px-4 mint-py-3">
			<div class="mint-h-2 mint-min-w-[120px] mint-flex-1 mint-overflow-hidden mint-rounded-full mint-bg-[#DFF7EB]">
				<div class="mint-h-full mint-rounded-full mint-bg-cta-ink" style="width: <?php echo esc_attr( (string) absint( $pct ) ); ?>%"></div>
			</div>
			<div class="mint-whitespace-nowrap mint-text-[13px] mint-font-bold mint-uppercase mint-tracking-[0.04em] mint-text-ink">
				<?php
				printf(
					/* translators: %d: progress percentage */
					esc_html__( '%d%% COMPLETE', 'mint-lms' ),
					absint( $pct )
				);
				?>
			</div>
			<?php if ( is_string( $overview->lastActivityLabel ) && '' !== $overview->lastActivityLabel ) : ?>
				<div class="mint-whitespace-nowrap mint-text-[13px] mint-text-[#5C5C77]">
					<?php
					printf(
						/* translators: %s: last activity datetime */
						esc_html__( 'Last activity on %s', 'mint-lms' ),
						esc_html( $overview->lastActivityLabel )
					);
					?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $showCta ) : ?>
			<div class="mint-mt-4 mint-flex mint-flex-wrap mint-gap-3">
				<?php if ( $overview->isEnrolled ) : ?>
					<a href="<?php echo esc_url( $continueUrl ); ?>" class="mint-inline-flex mint-h-[42px] mint-items-center mint-justify-center mint-rounded-lg mint-border-0 mint-bg-cta mint-px-[18px] mint-text-sm mint-font-bold mint-text-ink mint-no-underline hover:mint-bg-cta-hover focus-visible:mint-outline-none focus-visible:mint-shadow-[0_0_0_3px_rgba(11,79,63,0.35)]">
					<?php
						echo null !== $overview->progressPct && $overview->progressPct > 0
							? esc_html__( 'Continue lesson', 'mint-lms' )
							: esc_html__( 'Start course', 'mint-lms' );
					?>
					</a>
				<?php elseif ( $overview->canEnroll ) : ?>
					<a href="<?php echo esc_url( $enrollUrl ); ?>" class="mint-inline-flex mint-h-[42px] mint-items-center mint-justify-center mint-rounded-lg mint-border-0 mint-bg-cta mint-px-[18px] mint-text-sm mint-font-bold mint-text-ink mint-no-underline hover:mint-bg-cta-hover focus-visible:mint-outline-none focus-visible:mint-shadow-[0_0_0_3px_rgba(11,79,63,0.35)]"><?php esc_html_e( 'Enroll for free', 'mint-lms' ); ?></a>
				<?php elseif ( 'paid' === $overview->enrollmentType && '' !== $purchaseUrl ) : ?>
					<a href="<?php echo esc_url( $purchaseUrl ); ?>" class="mint-inline-flex mint-h-[42px] mint-items-center mint-justify-center mint-rounded-lg mint-border-0 mint-bg-cta mint-px-[18px] mint-text-sm mint-font-bold mint-text-ink mint-no-underline hover:mint-bg-cta-hover focus-visible:mint-outline-none focus-visible:mint-shadow-[0_0_0_3px_rgba(11,79,63,0.35)]">
					<?php
						echo '' !== $productPrice
							? esc_html( sprintf( __( 'Buy course — %s', 'mint-lms' ), function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( (float) $productPrice ) ) : $productPrice ) )
							: esc_html__( 'Buy course', 'mint-lms' );
					?>
					</a>
				<?php elseif ( ! $overview->isLoggedIn ) : ?>
					<a href="<?php echo esc_url( wp_login_url( get_permalink() ? get_permalink() : home_url( '/' ) ) ); ?>" class="mint-inline-flex mint-h-[42px] mint-items-center mint-justify-center mint-rounded-lg mint-border-0 mint-bg-cta mint-px-[18px] mint-text-sm mint-font-bold mint-text-ink mint-no-underline hover:mint-bg-cta-hover focus-visible:mint-outline-none focus-visible:mint-shadow-[0_0_0_3px_rgba(11,79,63,0.35)]"><?php esc_html_e( 'Log in to enroll', 'mint-lms' ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</article>

	<?php /* S3 course content — max-width 960px, padding 36/48/48 */ ?>
	<section class="mint-s3-content">
		<div>
			<h2 class="mint-m-0 mint-text-xl mint-font-semibold mint-tracking-[-0.02em] mint-text-ink"><?php esc_html_e( 'Course content', 'mint-lms' ); ?></h2>
			<p class="mint-m-0 mint-mt-1 mint-text-sm mint-text-[#33334A]"><?php esc_html_e( "Here's how your course is put together — open a lesson group to see its lessons, open a lesson to see its quiz.", 'mint-lms' ); ?></p>
		</div>

		<?php if ( ! $overview->curriculumVisible ) : ?>
			<div class="mint-mt-[18px] mint-rounded-xl mint-border mint-border-[#E4E1F0] mint-bg-[#FAFCFB] mint-px-5 mint-py-6 mint-text-center mint-text-sm mint-text-[#5C5C77]">
				<?php esc_html_e( 'Enroll to see the lessons in this course.', 'mint-lms' ); ?>
			</div>
		<?php elseif ( array() === $outline ) : ?>
			<div class="mint-mt-[18px] mint-rounded-xl mint-border mint-border-[#E4E1F0] mint-bg-[#FAFCFB] mint-px-5 mint-py-6 mint-text-center mint-text-sm mint-text-[#5C5C77]">
				<?php esc_html_e( 'No lesson groups yet. Add content in the course builder.', 'mint-lms' ); ?>
			</div>
		<?php else : ?>
			<div class="mint-s3-curriculum">
				<?php
				foreach ( $outline as $section ) :
					$sectionId    = (int) ( $section['id'] ?? 0 );
					$sectionKey   = 'sec-' . $sectionId;
					$sectionTitle = (string) ( $section['title'] ?? '' );
					$sectionMeta  = (string) ( $section['meta'] ?? '' );
					$lessons      = is_array( $section['lessons'] ?? null ) ? $section['lessons'] : array();
					$isUngrouped  = 0 === $sectionId;
					?>
					<div class="mint-s3-section">
						<?php if ( ! $isUngrouped ) : ?>
						<div class="mint-s3-row">
							<span class="mint-s3b-stack__icon" aria-hidden="true">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
							</span>
							<div class="mint-s3b-stack__quiz-copy">
								<div class="mint-s3b-stack__quiz-title"><?php echo esc_html( $sectionTitle ); ?></div>
								<div class="mint-s3b-stack__quiz-meta">
									<span class="mint-s3b-badge mint-s3b-badge--sm"><?php esc_html_e( 'Lesson Group', 'mint-lms' ); ?></span>
									<span><?php echo esc_html( $sectionMeta ); ?></span>
								</div>
							</div>
							<button
								type="button"
								class="mint-s3-expand"
								@click.stop="toggle('<?php echo esc_js( $sectionKey ); ?>')"
								:aria-expanded="isOpen('<?php echo esc_js( $sectionKey ); ?>') ? 'true' : 'false'"
							>
								<span x-text="isOpen('<?php echo esc_js( $sectionKey ); ?>') ? '<?php echo esc_js( __( 'Collapse', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Expand', 'mint-lms' ) ); ?>'"><?php esc_html_e( 'Expand', 'mint-lms' ); ?></span>
								<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
									<path :d="isOpen('<?php echo esc_js( $sectionKey ); ?>') ? 'M5.5 12 10 7.5 14.5 12' : 'M5.5 8 10 12.5 14.5 8'" d="M5.5 8 10 12.5 14.5 8" />
								</svg>
							</button>
						</div>
						<?php endif; ?>

						<div class="mint-s3-section__body" <?php echo $isUngrouped ? '' : 'x-show="isOpen(\'' . esc_js( $sectionKey ) . '\')" x-cloak'; ?>>
							<?php if ( array() === $lessons ) : ?>
								<p class="mint-m-0 mint-px-2 mint-py-2 mint-text-sm mint-text-[#5C5C77]"><?php esc_html_e( 'No lessons in this lesson group yet.', 'mint-lms' ); ?></p>
							<?php endif; ?>
							<?php
							foreach ( $lessons as $lesson ) :
								$lessonId    = (int) ( $lesson['id'] ?? 0 );
								$lessonKey   = $sectionKey . '-les-' . $lessonId;
								$lessonTitle = (string) ( $lesson['title'] ?? '' );
								$lessonMeta  = (string) ( $lesson['meta'] ?? '' );
								$lessonUrl   = $lessonPlayerUrls[ $lessonId ] ?? $continueUrl;
								$accessible  = ! empty( $lesson['accessible'] );
								$quizzes     = is_array( $lesson['quizzes'] ?? null ) ? $lesson['quizzes'] : array();
								?>
								<div class="mint-s3-rail--lesson">
									<div class="mint-s3-lesson">
										<div class="mint-s3-row">
											<span class="mint-s3b-stack__icon" aria-hidden="true">
												<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
											</span>
											<div class="mint-s3b-stack__quiz-copy">
												<div class="mint-s3b-stack__quiz-title">
													<?php if ( $accessible ) : ?>
														<a href="<?php echo esc_url( $lessonUrl ); ?>" class="mint-no-underline mint-text-inherit"><?php echo esc_html( $lessonTitle ); ?></a>
													<?php else : ?>
														<?php echo esc_html( $lessonTitle ); ?>
													<?php endif; ?>
												</div>
												<div class="mint-s3b-stack__quiz-meta">
													<span class="mint-s3b-badge mint-s3b-badge--sm"><?php esc_html_e( 'Lesson', 'mint-lms' ); ?></span>
													<span><?php echo esc_html( $lessonMeta ); ?></span>
												</div>
											</div>
											<?php if ( array() !== $quizzes ) : ?>
												<button
													type="button"
													class="mint-s3-expand"
													@click.stop="toggle('<?php echo esc_js( $lessonKey ); ?>')"
													:aria-expanded="isOpen('<?php echo esc_js( $lessonKey ); ?>') ? 'true' : 'false'"
												>
													<span x-text="isOpen('<?php echo esc_js( $lessonKey ); ?>') ? '<?php echo esc_js( __( 'Collapse', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Expand', 'mint-lms' ) ); ?>'"><?php esc_html_e( 'Expand', 'mint-lms' ); ?></span>
													<svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
														<path :d="isOpen('<?php echo esc_js( $lessonKey ); ?>') ? 'M5.5 12 10 7.5 14.5 12' : 'M5.5 8 10 12.5 14.5 8'" d="M5.5 8 10 12.5 14.5 8" />
													</svg>
												</button>
											<?php endif; ?>
										</div>

										<?php if ( array() !== $quizzes ) : ?>
											<div class="mint-s3-lesson__body" x-show="isOpen('<?php echo esc_js( $lessonKey ); ?>')" x-cloak>
												<?php
												foreach ( $quizzes as $quiz ) :
													$quizId    = (int) ( $quiz['id'] ?? 0 );
													$quizKey   = $lessonKey . '-quiz-' . $quizId;
													$quizTitle = (string) ( $quiz['title'] ?? '' );
													$quizUrl   = $quizId > 0 && $lessonId > 0
														? add_query_arg(
															array(
																'mint_course' => (string) $overview->courseId,
																'mint_lesson' => (string) $lessonId,
																'mint_quiz'   => (string) $quizId,
															),
															$playerUrl
														)
														: '';
													$quizMeta  = (string) ( $quiz['meta'] ?? '' );
													$questions = is_array( $quiz['questions'] ?? null ) ? $quiz['questions'] : array();
													?>
													<div class="mint-s3-rail--quiz">
														<div class="mint-s3-quiz">
															<div class="mint-s3-row">
																<span class="mint-s3b-stack__icon mint-s3b-stack__icon--quiz" aria-hidden="true">
																	<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
																</span>
																<div class="mint-s3b-stack__quiz-copy">
																	<div class="mint-s3b-stack__quiz-title">
																		<?php if ( '' !== $quizUrl ) : ?>
																			<a href="<?php echo esc_url( $quizUrl ); ?>" class="mint-s3-quiz-name-link"><?php echo esc_html( $quizTitle ); ?></a>
																		<?php else : ?>
																			<?php echo esc_html( $quizTitle ); ?>
																		<?php endif; ?>
																	</div>
																	<div class="mint-s3b-stack__quiz-meta">
																		<span class="mint-s3b-badge mint-s3b-badge--sm"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></span>
																		<span><?php echo esc_html( $quizMeta ); ?></span>
																	</div>
																</div>
																<?php if ( array() !== $questions ) : ?>
																	<button
																		type="button"
																		class="mint-s3-expand"
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
															<?php if ( array() !== $questions ) : ?>
																<div class="mint-s3-quiz__body" x-show="isOpen('<?php echo esc_js( $quizKey ); ?>')" x-cloak>
																	<?php foreach ( $questions as $question ) : ?>
																		<div class="mint-s3-row mint-s3-row--nested">
																			<span class="mint-s3b-stack__icon mint-s3b-stack__icon--quiz" aria-hidden="true">
																				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
																			</span>
																			<div class="mint-s3b-stack__quiz-copy">
																				<div class="mint-s3b-stack__quiz-title"><?php echo esc_html( (string) ( $question['text'] ?? '' ) ); ?></div>
																				<div class="mint-s3b-stack__quiz-meta">
																					<span class="mint-s3b-badge mint-s3b-badge--sm"><?php esc_html_e( 'Question', 'mint-lms' ); ?></span>
																				</div>
																			</div>
																		</div>
																	<?php endforeach; ?>
																</div>
															<?php endif; ?>
														</div>
													</div>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>
</div>

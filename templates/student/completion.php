<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Student\Dto\StudentPlayerContextDto;

/** @var StudentPlayerContextDto $context */
$context        = $context;
$dashboardUrl   = $dashboardUrl ?? home_url( '/' );
$overviewUrl    = $overviewUrl ?? home_url( '/' );
$certificateUrl = $certificateUrl ?? null;
?>
<div class="mint-flex mint-min-h-[60vh] mint-items-center mint-justify-center mint-bg-accent mint-px-4 mint-py-16">
	<div class="mint-w-full mint-max-w-lg mint-text-center mint-text-neutral-50">
		<div class="mint-mx-auto mint-mb-6 mint-flex mint-h-14 mint-w-14 mint-items-center mint-justify-center mint-rounded-xl mint-bg-neutral-50/15">
			<svg class="mint-h-7 mint-w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2L9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2z"/></svg>
		</div>
		<h1 class="mint-text-h1 mint-font-semibold mint-tracking-tight"><?php esc_html_e( 'Course complete!', 'mint-lms' ); ?></h1>
		<p class="mint-mt-3 mint-text-body mint-text-neutral-50/90">
			<?php
			printf(
				/* translators: %s: course title */
				esc_html__( 'You have completed %s.', 'mint-lms' ),
				esc_html( $context->courseTitle )
			);
			?>
		</p>
		<p class="mint-mt-2 mint-text-sm mint-text-neutral-50/75">
			<?php
			printf(
				/* translators: %d: number of completed lessons */
				esc_html__( '%d lessons completed.', 'mint-lms' ),
				absint( count( $context->completedLessonIds ) )
			);
			?>
		</p>
		<div class="mint-mt-8 mint-flex mint-flex-col mint-items-center mint-justify-center mint-gap-3 sm:mint-flex-row">
			<a href="<?php echo esc_url( $dashboardUrl ); ?>" class="mint-inline-flex mint-items-center mint-justify-center mint-h-control-md mint-px-5 mint-text-sm mint-font-medium mint-rounded-md mint-bg-neutral-50 mint-text-accent hover:mint-bg-neutral-100 mint-transition-colors mint-duration-hover mint-no-underline">
				<?php esc_html_e( 'Back to my courses', 'mint-lms' ); ?>
			</a>
			<?php if ( is_string( $certificateUrl ) && '' !== $certificateUrl ) : ?>
				<a href="<?php echo esc_url( $certificateUrl ); ?>" class="mint-inline-flex mint-items-center mint-justify-center mint-h-control-md mint-px-5 mint-text-sm mint-font-medium mint-rounded-md mint-border mint-border-neutral-50/30 mint-bg-transparent mint-text-neutral-50 hover:mint-bg-neutral-50/10 mint-transition-colors mint-duration-hover mint-no-underline" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Download certificate', 'mint-lms' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>

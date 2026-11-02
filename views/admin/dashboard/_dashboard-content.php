<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Admin\Dto\CreatorDashboardDto;
use MintLMS\Infrastructure\Admin\ViewRenderer;

/** @var CreatorDashboardDto $dashboard */
/** @var ViewRenderer $renderer */
/** @var string $firstName */
/** @var bool $showDetails */
/** @var int $userId */

$greeting    = MintLMS\Infrastructure\Ui\MintUi::greeting();
$builderUrl  = admin_url( 'admin.php?page=mint-lms-builder&course_id=' );
$metrics     = $dashboard->metrics;

$hue_tile_classes = array(
	1 => 'mint-bg-hue-1 mint-text-hue-1i',
	2 => 'mint-bg-hue-2 mint-text-hue-2i',
	3 => 'mint-bg-hue-3 mint-text-hue-3i',
	4 => 'mint-bg-hue-4 mint-text-hue-4i',
	5 => 'mint-bg-hue-5 mint-text-hue-5i',
	6 => 'mint-bg-hue-6 mint-text-hue-6i',
);
?>
<div
	x-data="{
		showDetails: <?php echo $showDetails ? 'true' : 'false'; ?>,
		toggleDetails() {
			this.showDetails = !this.showDetails;
			fetch(mintLmsAdmin.restBase + '/user-pref', {
				method: 'POST',
				headers: {'Content-Type':'application/json','X-WP-Nonce': mintLmsAdmin.nonce},
				body: JSON.stringify({key:'mint_lms_show_details', value: this.showDetails ? '1' : '0'})
			}).catch(()=>{});
		}
	}"
>
	<div class="mint-dash-greeting">
		<div class="mint-text-display mint-font-semibold mint-tracking-tight mint-text-ink"><?php echo esc_html( $greeting . ', ' . $firstName ); ?></div>
		<p class="mint-mt-[14px] mint-text-body mint-text-ink-3"><?php echo esc_html( $dashboard->signalLine ); ?></p>
	</div>

	<div class="mint-dash-metrics">
		<?php
		$metricCells = array(
			array(
				'label' => __( 'Students', 'mint-lms' ),
				'value' => (string) $metrics->studentsTotal,
				'delta' => $metrics->studentsDelta > 0 ? sprintf( '+%d this week', $metrics->studentsDelta ) : '',
				'sub'   => sprintf(
					/* translators: %d: published course count */
					_n( 'enrolled across %d course', 'enrolled across %d courses', $metrics->publishedCount, 'mint-lms' ),
					absint( $metrics->publishedCount )
				),
			),
			array(
				'label' => __( 'Finished', 'mint-lms' ),
				'value' => null !== $metrics->finishedPct ? round( $metrics->finishedPct ) . '%' : '—',
				'delta' => $metrics->finishedDelta > 0 ? sprintf( '+%d pts', $metrics->finishedDelta ) : '',
				'sub'   => $metrics->startedCount > 0
					? sprintf(
						/* translators: 1: finished count, 2: started count */
						__( '%1$d of %2$d who started', 'mint-lms' ),
						$metrics->finishedCount,
						$metrics->startedCount
					)
					: __( 'No students started yet', 'mint-lms' ),
			),
			array(
				'label' => __( 'Live', 'mint-lms' ),
				'value' => (string) $metrics->publishedCount,
				'delta' => $metrics->draftCount > 0 ? sprintf( '%d drafts', $metrics->draftCount ) : '',
				'sub'   => sprintf(
					/* translators: %d: total course count */
					__( 'of %d courses total', 'mint-lms' ),
					absint( $metrics->publishedCount + $metrics->draftCount )
				),
			),
			array(
				'label' => __( 'Lessons done', 'mint-lms' ),
				'value' => (string) $metrics->lessonsDone7d,
				'delta' => __( 'last 7 days', 'mint-lms' ),
				'sub'   => __( 'across all students', 'mint-lms' ),
			),
		);

		foreach ( $metricCells as $cell ) :
			?>
			<div class="mint-dash-metric-card">
				<div class="mint-dash-metric-card__label"><?php echo esc_html( $cell['label'] ); ?></div>
				<div class="mint-dash-metric-card__row">
					<div class="mint-dash-metric-card__value"><?php echo esc_html( $cell['value'] ); ?></div>
					<?php if ( '' !== $cell['delta'] ) : ?>
						<div x-show="showDetails" x-cloak class="mint-dash-metric-card__delta"><?php echo esc_html( $cell['delta'] ); ?></div>
					<?php endif; ?>
				</div>
				<div class="mint-dash-metric-card__sub"><?php echo esc_html( $cell['sub'] ); ?></div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( null !== $dashboard->needsYou ) : ?>
	<div class="mint-mt-6 mint-flex mint-flex-wrap mint-items-center mint-justify-between mint-gap-7 mint-rounded-[14px] mint-border mint-border-[#CFEFDE] mint-bg-[#EAFFF6] mint-px-[22px] mint-py-5">
		<div class="mint-flex mint-min-w-0 mint-flex-[1_1_320px] mint-items-baseline mint-gap-3">
			<div class="mint-shrink-0 mint-text-over mint-font-semibold mint-uppercase mint-text-[#0B4F3F]"><?php esc_html_e( 'Needs you', 'mint-lms' ); ?></div>
			<div class="mint-min-w-0">
				<div class="mint-text-h3 mint-font-semibold mint-text-ink"><?php echo esc_html( $dashboard->needsYou->title ); ?></div>
				<div class="mint-mt-[3px] mint-text-sm mint-text-ink-3"><?php echo esc_html( $dashboard->needsYou->blocker . ' · draft · edited ' . $dashboard->needsYou->editedLabel ); ?></div>
			</div>
		</div>
		<a
			href="<?php echo esc_url( $builderUrl . $dashboard->needsYou->courseId ); ?>"
			class="mint-dash-continue mint-inline-flex mint-h-9 mint-flex-none mint-items-center mint-justify-center mint-rounded-md mint-px-[14px] mint-text-sm mint-font-semibold mint-no-underline focus-visible:mint-outline-none"
		><?php esc_html_e( 'Continue editing', 'mint-lms' ); ?></a>
	</div>
	<?php endif; ?>

	<div class="mint-pt-11">
		<div class="mint-mb-[6px] mint-flex mint-items-baseline mint-justify-between mint-gap-4 mint-border-b mint-border-rule mint-pb-2.5">
			<div class="mint-text-over mint-font-semibold mint-uppercase mint-text-ink-3"><?php esc_html_e( 'Courses', 'mint-lms' ); ?></div>
			<div class="mint-flex mint-items-center mint-gap-[14px]">
				<button
					type="button"
					@click="toggleDetails()"
					class="mint-dash-details-toggle mint-inline-flex mint-h-[30px] mint-items-center mint-gap-[5px] mint-rounded-sm mint-border mint-px-[10px] mint-pl-[7px] mint-text-xs mint-font-medium focus-visible:mint-outline-none"
					:class="showDetails
						? 'mint-border-control mint-bg-[#F4F3F8] mint-text-ink'
						: 'mint-border-[rgba(15,14,26,0.12)] mint-bg-transparent mint-text-ink-3'"
				>
					<svg class="mint-transition-transform mint-duration-150" :class="{ 'mint-rotate-180': showDetails }" style="transition-timing-function: cubic-bezier(.2,.8,.2,1)" width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
					<span x-text="showDetails ? '<?php echo esc_js( __( 'Hide details', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Show details', 'mint-lms' ) ); ?>'"></span>
				</button>
				<a href="<?php echo esc_url( \MintLMS\Infrastructure\PostType\PostTypes::listUrl( \MintLMS\Infrastructure\PostType\PostTypes::COURSE ) ); ?>" class="mint-text-sm mint-font-medium mint-text-ink-3 mint-no-underline hover:mint-text-ink">
					<?php
					printf(
						/* translators: %d: number of courses */
						esc_html__( 'All %d', 'mint-lms' ),
						absint( count( $dashboard->courses ) )
					);
					?>
				</a>
			</div>
		</div>

		<div class="mint-mt-[6px] mint-grid mint-grid-cols-[minmax(0,1fr)_78px_132px_84px] mint-items-center mint-gap-6 mint-rounded-lg mint-bg-[#F6F4FD] mint-px-4 mint-py-[13px]">
			<div class="mint-text-xs mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Course', 'mint-lms' ); ?></div>
			<div class="mint-text-xs mint-text-right mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Students', 'mint-lms' ); ?></div>
			<div class="mint-text-xs mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Finished', 'mint-lms' ); ?></div>
			<div class="mint-text-xs mint-text-right mint-font-semibold mint-text-ink-2"><?php esc_html_e( 'Edited', 'mint-lms' ); ?></div>
		</div>

		<?php
		foreach ( $dashboard->courses as $courseIndex => $course ) :
			$pct      = $course->completionPct;
			$pctLabel = null === $pct ? '—' : round( $pct ) . '%';
			$pctWidth = null === $pct ? 0 : (int) round( $pct );
			$hueClass = $hue_tile_classes[ ( $courseIndex % 6 ) + 1 ];
			?>
		<a
			href="<?php echo esc_url( $builderUrl . $course->id ); ?>"
			tabindex="0"
			class="mint-dash-course-row mint-grid mint-grid-cols-[minmax(0,1fr)_78px_132px_84px] mint-items-center mint-gap-6 mint-rounded-md mint-border-t mint-border-[#EDEBF7] mint-px-4 mint-py-[15px] mint-text-inherit mint-no-underline mint-outline-none"
		>
			<div class="mint-flex mint-min-w-0 mint-items-center mint-gap-[14px]">
				<div class="mint-flex mint-h-11 mint-w-11 mint-shrink-0 mint-items-center mint-justify-center mint-rounded-xl mint-text-lg mint-font-bold mint-tracking-tight <?php echo esc_attr( $hueClass ); ?>"><?php echo esc_html( $course->initial ); ?></div>
				<div class="mint-min-w-0">
					<div class="mint-truncate mint-text-row mint-font-medium mint-text-ink"><?php echo esc_html( $course->title ); ?></div>
					<div x-show="showDetails" x-cloak class="mint-mt-[3px] mint-text-xs mint-text-ink-3"><?php echo esc_html( $course->metaLine ); ?></div>
				</div>
			</div>
			<div class="mint-tabular-nums mint-text-right mint-text-base mint-text-ink-2"><?php echo esc_html( $course->studentsLabel ); ?></div>
			<div class="mint-flex mint-items-center mint-gap-[10px]">
				<div class="mint-h-[9px] mint-min-w-[44px] mint-flex-1 mint-overflow-hidden mint-rounded-full mint-bg-[#E7E4F3]">
					<div
						class="mint-h-full mint-rounded-full <?php echo null === $pct ? 'mint-w-0 mint-bg-[#E7E4F3]' : 'mint-bg-[#98FBCB]'; ?>"
						style="<?php echo null === $pct ? '' : esc_attr( 'width:' . $pctWidth . '%' ); ?>"
					></div>
				</div>
				<div class="mint-w-[42px] mint-tabular-nums mint-text-right mint-text-sm mint-font-medium <?php echo null === $pct ? 'mint-text-ink-3' : 'mint-text-ink'; ?>"><?php echo esc_html( $pctLabel ); ?></div>
			</div>
			<div class="mint-tabular-nums mint-text-right mint-text-sm mint-text-ink-3"><?php echo esc_html( $course->editedLabel ); ?></div>
		</a>
		<?php endforeach; ?>
	</div>

	<?php if ( array() !== $dashboard->activity ) : ?>
	<div x-show="showDetails" x-cloak class="mint-px-0 mint-pb-24 mint-pt-12">
		<div class="mint-mb-0 mint-border-b mint-border-rule mint-pb-2.5 mint-text-over mint-font-semibold mint-uppercase mint-text-ink-3"><?php esc_html_e( 'Recent activity', 'mint-lms' ); ?></div>
		<?php
		foreach ( $dashboard->activity as $activityIndex => $item ) :
			$activityHue = $hue_tile_classes[ ( $activityIndex % 6 ) + 1 ];
			?>
		<div class="mint-grid mint-grid-cols-[32px_minmax(0,1fr)_92px] mint-items-center mint-gap-[14px] mint-border-t mint-border-rule-soft mint-py-[13px]">
			<div class="mint-flex mint-h-8 mint-w-8 mint-shrink-0 mint-items-center mint-justify-center mint-rounded-full mint-text-[13px] mint-font-bold <?php echo esc_attr( $activityHue ); ?>"><?php echo esc_html( $item->initials ); ?></div>
			<div class="mint-min-w-0 mint-truncate mint-text-sm mint-text-ink-2"><?php echo esc_html( $item->text ); ?></div>
			<div class="mint-tabular-nums mint-text-right mint-text-xs mint-text-[#82829C]"><?php echo esc_html( $item->when ); ?></div>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<div x-show="!showDetails" class="mint-pb-24"></div>
</div>

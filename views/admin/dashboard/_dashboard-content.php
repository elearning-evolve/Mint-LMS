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

$greeting   = MintLMS\Infrastructure\Ui\MintUi::greeting();
$builderUrl = admin_url( 'admin.php?page=mint-lms-builder&course_id=' );
$metrics    = $dashboard->metrics;

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
				'label' => __( 'Published', 'mint-lms' ),
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
	<div class="mint-dash-needs">
		<div class="mint-dash-needs__copy">
			<div class="mint-dash-needs__eyebrow"><?php esc_html_e( 'Needs you', 'mint-lms' ); ?></div>
			<div class="mint-dash-needs__body">
				<div class="mint-dash-needs__title"><?php echo esc_html( $dashboard->needsYou->title ); ?></div>
				<div class="mint-dash-needs__meta">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: blocker/status note, 2: draft|published, 3: relative edited label */
							__( '%1$s · %2$s · edited %3$s', 'mint-lms' ),
							$dashboard->needsYou->blocker,
							$dashboard->needsYou->statusLabel,
							$dashboard->needsYou->editedLabel
						)
					);
					?>
				</div>
			</div>
		</div>
		<a
			href="<?php echo esc_url( $builderUrl . $dashboard->needsYou->courseId ); ?>"
			class="mint-dash-continue"
		><?php esc_html_e( 'Continue editing', 'mint-lms' ); ?></a>
	</div>
	<?php endif; ?>

	<section class="mint-dash-courses">
		<div class="mint-dash-courses__head">
			<div class="mint-dash-courses__title"><?php esc_html_e( 'Courses', 'mint-lms' ); ?></div>
			<div class="mint-dash-courses__actions">
				<button
					type="button"
					@click="toggleDetails()"
					class="mint-dash-details-toggle"
					:class="showDetails ? 'is-on' : ''"
					:aria-pressed="showDetails ? 'true' : 'false'"
				>
					<svg class="mint-dash-details-toggle__chevron" :class="{ 'is-open': showDetails }" width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
					<span x-text="showDetails ? '<?php echo esc_js( __( 'Hide details', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Show details', 'mint-lms' ) ); ?>'"></span>
				</button>
				<a href="<?php echo esc_url( \MintLMS\Infrastructure\PostType\PostTypes::listUrl( \MintLMS\Infrastructure\PostType\PostTypes::COURSE ) ); ?>" class="mint-dash-courses__all">
					<?php
					printf(
						/* translators: %d: number of courses */
						esc_html__( 'All %d', 'mint-lms' ),
						absint( $dashboard->coursesTotal > 0 ? $dashboard->coursesTotal : count( $dashboard->courses ) )
					);
					?>
				</a>
			</div>
		</div>

		<div class="mint-dash-courses__cols">
			<div class="mint-dash-courses__col mint-dash-courses__col--course"><?php esc_html_e( 'Course', 'mint-lms' ); ?></div>
			<div class="mint-dash-courses__col mint-dash-courses__col--students"><?php esc_html_e( 'Students', 'mint-lms' ); ?></div>
			<div class="mint-dash-courses__col mint-dash-courses__col--finished"><?php esc_html_e( 'Finished', 'mint-lms' ); ?></div>
			<div class="mint-dash-courses__col mint-dash-courses__col--edited"><?php esc_html_e( 'Edited', 'mint-lms' ); ?></div>
		</div>

		<div class="mint-dash-courses__body">
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
			class="mint-dash-course-row"
		>
			<div class="mint-dash-course-row__course">
				<div class="mint-dash-course-row__tile <?php echo esc_attr( $hueClass ); ?>"><?php echo esc_html( $course->initial ); ?></div>
				<div class="mint-dash-course-row__copy">
					<div class="mint-dash-course-row__title"><?php echo esc_html( $course->title ); ?></div>
					<div x-show="showDetails" x-cloak class="mint-dash-course-row__meta"><?php echo esc_html( $course->metaLine ); ?></div>
				</div>
			</div>
			<div class="mint-dash-course-row__students"><?php echo esc_html( $course->studentsLabel ); ?></div>
			<div class="mint-dash-course-row__finished">
				<div class="mint-dash-course-row__bar">
					<div
						class="mint-dash-course-row__bar-fill<?php echo null === $pct ? ' is-empty' : ''; ?>"
						style="<?php echo null === $pct ? '' : esc_attr( 'width:' . $pctWidth . '%' ); ?>"
					></div>
				</div>
				<div class="mint-dash-course-row__pct<?php echo null === $pct ? ' is-empty' : ''; ?>"><?php echo esc_html( $pctLabel ); ?></div>
			</div>
			<div class="mint-dash-course-row__edited"><?php echo esc_html( $course->editedLabel ); ?></div>
		</a>
		<?php endforeach; ?>
		</div>
	</section>

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

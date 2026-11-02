<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Ui\MintUi;

/** @var array<string, mixed> $summary */

$activity = is_array( $summary['activity'] ?? null ) ? $summary['activity'] : array();

$hue_tile_classes = array(
	1 => 'mint-bg-hue-1 mint-text-hue-1i',
	2 => 'mint-bg-hue-2 mint-text-hue-2i',
	3 => 'mint-bg-hue-3 mint-text-hue-3i',
	4 => 'mint-bg-hue-4 mint-text-hue-4i',
	5 => 'mint-bg-hue-5 mint-text-hue-5i',
	6 => 'mint-bg-hue-6 mint-text-hue-6i',
);
?>
<div class="mint-mx-auto mint-max-w-content mint-px-7">
	<div class="mint-pt-10">
		<div>
			<h1 class="mint-m-0 mint-text-[48px] mint-font-semibold mint-leading-[52px] mint-tracking-[-0.04em] mint-text-ink"><?php esc_html_e( 'Reports', 'mint-lms' ); ?></h1>
			<p class="mint-mt-[10px] mint-text-[19px] mint-leading-7 mint-text-ink-2"><?php esc_html_e( 'Overview of courses, enrollments, and recent activity.', 'mint-lms' ); ?></p>
		</div>
	</div>

	<div class="mint-mt-6 mint-flex mint-flex-wrap mint-items-stretch mint-border-b mint-border-rule">
		<?php
		$metrics = array(
			array(
				'label' => __( 'Courses', 'mint-lms' ),
				'value' => (string) absint( $summary['total_courses'] ?? 0 ),
				'sub'   => __( 'total courses', 'mint-lms' ),
			),
			array(
				'label' => __( 'Students', 'mint-lms' ),
				'value' => (string) absint( $summary['total_students'] ?? 0 ),
				'sub'   => __( 'active enrollments', 'mint-lms' ),
			),
			array(
				'label' => __( 'Enrollments', 'mint-lms' ),
				'value' => (string) absint( $summary['total_enrollments'] ?? 0 ),
				'sub'   => __( 'all time', 'mint-lms' ),
			),
			array(
				'label' => __( 'Completions', 'mint-lms' ),
				'value' => (string) absint( $summary['total_completions'] ?? 0 ),
				'sub'   => __( 'courses finished', 'mint-lms' ),
			),
		);

		foreach ( $metrics as $cell ) :
			?>
			<div class="mint-min-w-0 mint-flex-[1_1_165px] mint-border-l mint-border-rule-faint mint-px-7 mint-py-6 first:mint-border-l-0 first:mint-pl-0">
				<div class="mint-text-sm mint-font-semibold mint-leading-5 mint-text-hue-1i"><?php echo esc_html( $cell['label'] ); ?></div>
				<div class="mint-mt-3 mint-text-stat mint-font-semibold mint-tracking-tight mint-text-ink"><?php echo esc_html( $cell['value'] ); ?></div>
				<div class="mint-text-xs mint-text-ink-3"><?php echo esc_html( $cell['sub'] ); ?></div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="mint-mt-8">
		<div class="mint-text-sm mint-font-semibold mint-leading-5 mint-text-ink-2"><?php esc_html_e( 'Lessons completed', 'mint-lms' ); ?></div>
		<div class="mint-mt-2 mint-text-stat mint-font-semibold mint-tracking-tight mint-text-ink"><?php echo esc_html( (string) absint( $summary['lessons_completed'] ?? 0 ) ); ?></div>
		<p class="mint-mt-1 mint-text-sm mint-text-ink-3"><?php esc_html_e( 'Individual lesson completions recorded across all courses.', 'mint-lms' ); ?></p>
	</div>

	<?php if ( array() !== $activity ) : ?>
	<div class="mint-mt-10 mint-pb-24">
		<div class="mint-mb-[10px] mint-text-sm mint-font-semibold mint-leading-5 mint-text-ink-2"><?php esc_html_e( 'Recent activity', 'mint-lms' ); ?></div>
		<?php
		$index = 0;
		foreach ( $activity as $row ) :
			if ( ! is_array( $row ) ) {
				continue;
			}
			$user = get_userdata( (int) ( $row['user_id'] ?? 0 ) );
			$name = $user instanceof WP_User ? $user->display_name : __( 'A student', 'mint-lms' );
			$hue  = MintUi::hueByIndex( $index );
			$hueClass = $hue_tile_classes[ ( $index % 6 ) + 1 ];
			++$index;
			$course = (string) ( $row['course_title'] ?? '' );
			$type   = (string) ( $row['type'] ?? '' );
			if ( 'completed' === $type ) {
				$text = sprintf(
					/* translators: 1: student name, 2: course title */
					__( '%1$s finished %2$s', 'mint-lms' ),
					$name,
					$course
				);
			} elseif ( 'enrolled' === $type ) {
				$text = sprintf(
					/* translators: 1: student name, 2: course title */
					__( '%1$s enrolled in %2$s', 'mint-lms' ),
					$name,
					$course
				);
			} else {
				$text = sprintf(
					/* translators: 1: student name, 2: course title */
					__( '%1$s made progress in %2$s', 'mint-lms' ),
					$name,
					$course
				);
			}
			$when = isset( $row['occurred_at'] ) ? MintUi::relativeTimeShort( new DateTimeImmutable( (string) $row['occurred_at'] ) ) : '';
			?>
			<div class="mint-grid mint-grid-cols-[32px_minmax(0,1fr)_92px] mint-items-center mint-gap-[14px] mint-border-t mint-border-rule-soft mint-py-[13px]">
				<div class="mint-flex mint-h-8 mint-w-8 mint-shrink-0 mint-items-center mint-justify-center mint-rounded-full mint-text-[13px] mint-font-bold <?php echo esc_attr( $hueClass ); ?>"><?php echo esc_html( MintUi::initials( $name ) ); ?></div>
				<div class="mint-min-w-0 mint-truncate mint-text-sm mint-text-ink-2"><?php echo esc_html( $text ); ?></div>
				<div class="mint-tabular-nums mint-text-right mint-text-xs mint-text-[#82829C]"><?php echo esc_html( $when ); ?></div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php else : ?>
	<p class="mint-mt-10 mint-pb-24 mint-text-body mint-text-ink-3"><?php esc_html_e( 'No recent activity yet.', 'mint-lms' ); ?></p>
	<?php endif; ?>
</div>

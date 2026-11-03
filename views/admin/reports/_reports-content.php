<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/** @var array<string, mixed> $dashboard */

$dashboard = is_array( $dashboard ?? null ) ? $dashboard : array();
$totals    = is_array( $dashboard['totals'] ?? null ) ? $dashboard['totals'] : array();
$ranges    = is_array( $dashboard['ranges'] ?? null ) ? $dashboard['ranges'] : array();
$top       = is_array( $dashboard['top_courses'] ?? null ) ? $dashboard['top_courses'] : array();
$activity  = is_array( $dashboard['activity'] ?? null ) ? $dashboard['activity'] : array();

$boot = array(
	'defaultRange' => '30d',
	'totals'       => array(
		'courses'           => (int) ( $totals['courses'] ?? 0 ),
		'students'          => (int) ( $totals['students'] ?? 0 ),
		'enrollments'       => (int) ( $totals['enrollments'] ?? 0 ),
		'completions'       => (int) ( $totals['completions'] ?? 0 ),
		'lessons_completed' => (int) ( $totals['lessons_completed'] ?? 0 ),
	),
	'ranges'       => $ranges,
	'topCourses'   => $top,
	'activity'     => $activity,
	'i18n'         => array(
		'courses'           => __( 'Courses', 'mint-lms' ),
		'students'          => __( 'Students', 'mint-lms' ),
		'enrollments'       => __( 'Enrollments', 'mint-lms' ),
		'completions'       => __( 'Completions', 'mint-lms' ),
		'lessonsCompleted'  => __( 'Lessons completed', 'mint-lms' ),
		'totalCourses'      => __( 'total courses', 'mint-lms' ),
		'activeEnrollments' => __( 'active enrollments', 'mint-lms' ),
		'allTime'           => __( 'all time', 'mint-lms' ),
		'coursesFinished'   => __( 'courses finished', 'mint-lms' ),
		'acrossCourses'     => __( 'across all courses', 'mint-lms' ),
		'studentsLabel'     => __( 'students', 'mint-lms' ),
		'noActivity'        => __( 'No recent activity yet.', 'mint-lms' ),
		'noCourses'         => __( 'No courses yet.', 'mint-lms' ),
		'exportEmpty'       => __( 'Nothing to export yet.', 'mint-lms' ),
	),
);

$boot_json = wp_json_encode( $boot );
if ( ! is_string( $boot_json ) ) {
	$boot_json = '{}';
}
?>
<div
	class="mint-reports"
	x-data="mintReports(<?php echo esc_attr( $boot_json ); ?>)"
>
	<div class="mint-reports__hero">
		<div>
			<h1 class="mint-reports__title"><?php echo esc_html__( 'Reports', 'mint-lms' ); ?></h1>
			<p class="mint-reports__sub"><?php echo esc_html__( 'How your courses, students and lessons are doing.', 'mint-lms' ); ?></p>
		</div>
		<div class="mint-reports__ranges" role="tablist" aria-label="<?php echo esc_attr__( 'Report range', 'mint-lms' ); ?>">
			<template x-for="key in rangeKeys" :key="key">
				<button
					type="button"
					class="mint-reports__range"
					role="tab"
					:aria-selected="range === key ? 'true' : 'false'"
					:class="range === key ? 'is-active' : ''"
					@click="setRange(key)"
					x-text="ranges[key]?.label || key"
				></button>
			</template>
		</div>
	</div>

	<div class="mint-reports__stats">
		<template x-for="card in statCards" :key="card.key">
			<div class="mint-reports__stat" tabindex="0">
				<div class="mint-reports__stat-top">
					<div class="mint-reports__stat-label" x-text="card.label"></div>
					<div class="mint-reports__stat-icon" aria-hidden="true" x-html="card.icon"></div>
				</div>
				<div class="mint-reports__stat-value" x-text="card.value"></div>
				<div class="mint-reports__stat-note" x-text="card.note"></div>
				<div class="mint-reports__stat-trend" x-text="card.trend"></div>
			</div>
		</template>
	</div>

	<div class="mint-reports__grid">
		<div class="mint-reports__main">
			<section class="mint-reports__panel">
				<div class="mint-reports__panel-head">
					<div class="mint-reports__panel-title"><?php echo esc_html__( 'New enrollments', 'mint-lms' ); ?></div>
					<div class="mint-reports__panel-note" x-text="chartNote"></div>
				</div>
				<div class="mint-reports__chart">
					<template x-for="(bar, index) in chartBars" :key="bar.label + '-' + index">
						<div class="mint-reports__bar" tabindex="0">
							<div class="mint-reports__bar-value" x-text="bar.value"></div>
							<div
								class="mint-reports__bar-fill"
								:class="index === chartBars.length - 1 ? 'is-latest' : ''"
								:style="'height:' + barHeight(bar.value) + 'px'"
							></div>
							<div class="mint-reports__bar-label" x-text="bar.label"></div>
						</div>
					</template>
				</div>
			</section>

			<section class="mint-reports__panel">
				<div class="mint-reports__panel-head">
					<div class="mint-reports__panel-title"><?php echo esc_html__( 'Top courses', 'mint-lms' ); ?></div>
				</div>
				<div class="mint-reports__top">
					<template x-if="topCourses.length === 0">
						<p class="mint-reports__empty" x-text="i18n.noCourses"></p>
					</template>
					<template x-for="course in topCourses" :key="course.id">
						<div class="mint-reports__course" tabindex="0">
							<div
								class="mint-reports__course-tile"
								:style="'background:' + course.bg + ';color:' + course.ink"
								x-text="course.letter"
							></div>
							<div class="mint-min-w-0 mint-flex-1">
								<div class="mint-reports__course-name" x-text="course.title"></div>
								<div class="mint-reports__course-meta" x-text="course.students + ' ' + (i18n.studentsLabel || 'students')"></div>
							</div>
							<div class="mint-reports__course-progress">
								<div class="mint-reports__course-track">
									<div class="mint-reports__course-fill" :style="'width:' + course.pct + '%'"></div>
								</div>
								<div class="mint-reports__course-pct" x-text="course.pct + '%'"></div>
							</div>
						</div>
					</template>
				</div>
			</section>
		</div>

		<section class="mint-reports__panel mint-reports__activity">
			<div class="mint-reports__panel-head">
				<div class="mint-reports__panel-title"><?php echo esc_html__( 'Recent activity', 'mint-lms' ); ?></div>
			</div>
			<div class="mint-reports__feed">
				<template x-if="activity.length === 0">
					<p class="mint-reports__empty" x-text="i18n.noActivity"></p>
				</template>
				<template x-for="(item, index) in activity" :key="(item.user_id || 0) + '-' + (item.occurred_at || index)">
					<div class="mint-reports__event" tabindex="0">
						<div
							class="mint-reports__avatar"
							:style="'background:' + item.bg + ';color:' + item.ink"
							x-text="item.initials"
						></div>
						<div class="mint-min-w-0 mint-flex-1">
							<div class="mint-reports__event-text">
								<span class="mint-reports__event-name" x-text="item.name"></span>
								<span x-text="' ' + item.action"></span>
							</div>
							<div class="mint-reports__event-what" x-text="item.what"></div>
						</div>
						<div class="mint-reports__event-when" x-text="item.when"></div>
					</div>
				</template>
			</div>
		</section>
	</div>
</div>

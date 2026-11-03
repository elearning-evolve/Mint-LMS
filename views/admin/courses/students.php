<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Admin\CreatorDashboardService;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Infrastructure\Admin\MintUiDashboardPresentation;
use MintLMS\Infrastructure\Admin\ViewRenderer;
use MintLMS\Infrastructure\Database\Repository\WpdbAdminDashboardRepository;
use MintLMS\Infrastructure\Database\Repository\WpPostCourseRepository;
use MintLMS\Infrastructure\Ui\MintUi;
use MintLMS\Infrastructure\User\WpUserLookup;
use MintLMS\Plugin;

$renderer = new ViewRenderer();
$courseId = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$overviewStats = array(
	'students_enrolled' => 0,
	'finished'          => 0,
	'active_week'       => 0,
	'courses'           => 0,
);
$recentJoined  = array();
$pickerCourses = array();
$courseTitle   = '';
$courseStats   = array(
	'students'     => 0,
	'finished'     => 0,
	'lesson_count' => 0,
);

$wpdb = mintlms_wpdb();
if ( null !== $wpdb ) {
	$userId           = get_current_user_id();
	$dashboardService = new CreatorDashboardService(
		new WpPostCourseRepository(),
		new WpdbAdminDashboardRepository( $wpdb ),
		new MintUiDashboardPresentation(),
		new WpUserLookup(),
	);
	$dashboard        = $dashboardService->getDashboard( $userId );
	$metrics          = $dashboard->metrics;

	$overviewStats = array(
		'students_enrolled' => (int) $metrics->studentsTotal,
		'finished'          => (int) $metrics->finishedCount,
		'active_week'       => (int) $metrics->studentsDelta,
		'courses'           => (int) $metrics->publishedCount + (int) $metrics->draftCount,
	);

	$presentation = new MintUiDashboardPresentation();
	$userLookup   = new WpUserLookup();
	$dashRepo     = new WpdbAdminDashboardRepository( $wpdb );
	$card_tints   = array(
		array(
			'bg'  => '#F8E7EF',
			'ink' => '#A81E5C',
		),
		array(
			'bg'  => '#E8F5EC',
			'ink' => '#1E7A4C',
		),
		array(
			'bg'  => '#E6F1FF',
			'ink' => '#1A5AA8',
		),
		array(
			'bg'  => '#F1EDFB',
			'ink' => '#5B2BFF',
		),
		array(
			'bg'  => '#FFF3E0',
			'ink' => '#B0620A',
		),
	);

	try {
		$courseList = Plugin::courseService()->list( 1, 100, $userId, null, null );
		$courseIds  = array_map(
			static function ( $course ): int {
				return (int) $course->id;
			},
			$courseList->courses
		);
		$batchStats = array() !== $courseIds ? $dashRepo->getCourseStatsBatch( $courseIds ) : array();

		$students_enrolled = 0;
		$finished_total    = 0;

		foreach ( $courseList->courses as $index => $course ) {
			$id                 = (int) $course->id;
			$rowStats           = $batchStats[ $id ] ?? array();
			$tint               = $card_tints[ $index % count( $card_tints ) ];
			$student_count      = (int) ( $rowStats['students'] ?? $course->studentCount );
			$finished_count     = (int) ( $rowStats['finished'] ?? $course->finishedCount );
			$students_enrolled += $student_count;
			$finished_total    += $finished_count;
			$pickerCourses[]    = array(
				'id'            => $id,
				'title'         => (string) $course->title,
				'letter'        => MintUi::courseInitial( (string) $course->title ),
				'studentCount'  => $student_count,
				'finishedCount' => $finished_count,
				'bg'            => $tint['bg'],
				'ink'           => $tint['ink'],
			);
		}

		$overviewStats['students_enrolled'] = $students_enrolled;
		$overviewStats['finished']          = $finished_total;
		$overviewStats['courses']           = (int) ( $courseList->allTotal > 0 ? $courseList->allTotal : count( $pickerCourses ) );
		$overviewStats['active_week']       = (int) $metrics->studentsDelta;
	} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Picker falls back empty.
		$courseIds = array();
	}

	if ( array() === $pickerCourses && array() !== $dashboard->courses ) {
		$courseIds         = array_map(
			static function ( $row ): int {
				return (int) $row->id;
			},
			$dashboard->courses
		);
		$batchStats        = array() !== $courseIds ? $dashRepo->getCourseStatsBatch( $courseIds ) : array();
		$students_enrolled = 0;
		$finished_total    = 0;
		foreach ( $dashboard->courses as $index => $row ) {
			$id                 = (int) $row->id;
			$rowStats           = $batchStats[ $id ] ?? array();
			$tint               = $card_tints[ $index % count( $card_tints ) ];
			$student_count      = (int) ( $rowStats['students'] ?? 0 );
			$finished_count     = (int) ( $rowStats['finished'] ?? 0 );
			$students_enrolled += $student_count;
			$finished_total    += $finished_count;
			$pickerCourses[]    = array(
				'id'            => $id,
				'title'         => (string) $row->title,
				'letter'        => MintUi::courseInitial( (string) $row->title ),
				'studentCount'  => $student_count,
				'finishedCount' => $finished_count,
				'bg'            => $tint['bg'],
				'ink'           => $tint['ink'],
			);
		}
		$overviewStats['students_enrolled'] = $students_enrolled;
		$overviewStats['finished']          = $finished_total;
		$overviewStats['courses']           = count( $pickerCourses );
	}

	if ( empty( $courseIds ) ) {
		$courseIds = array_map(
			static function ( $row ): int {
				return (int) $row->id;
			},
			$dashboard->courses
		);
	}

	$recentRows  = $dashRepo->getRecentActiveEnrollments( $courseIds, 5 );
	$recentIndex = 0;
	foreach ( $recentRows as $row ) {
		$name           = $userLookup->getDisplayName( (int) $row['user_id'] ) ?? __( 'A student', 'mint-lms' );
		$hue            = $presentation->hueByIndex( $recentIndex );
		$when           = $presentation->relativeTimeShort( new \DateTimeImmutable( (string) $row['enrolled_at'] ) );
		$recentJoined[] = array(
			'enrollmentId' => (int) $row['enrollment_id'],
			'userId'       => (int) $row['user_id'],
			'courseId'     => (int) $row['course_id'],
			'name'         => $name,
			'initials'     => $presentation->initials( $name ),
			'course'       => (string) ( $row['course_title'] ?? '' ),
			'when'         => $when,
			'bg'           => $hue['bg'],
			'ink'          => $hue['ink'],
		);
		++$recentIndex;
	}

	if ( $courseId > 0 ) {
		try {
			$course      = Plugin::courseService()->get( $courseId, $userId );
			$courseTitle = $course->title;
			$batch       = ( new WpdbAdminDashboardRepository( $wpdb ) )->getCourseStatsBatch( array( $courseId ) );
			$courseStats = $batch[ $courseId ] ?? $courseStats;
		} catch ( NotFoundException | ForbiddenException $e ) {
			$courseTitle = '';
		}
	}
}

$content = $renderer->render(
	'admin/courses/_students-content',
	array(
		'renderer'      => $renderer,
		'courseId'      => $courseId,
		'courseTitle'   => $courseTitle,
		'courseStats'   => $courseStats,
		'overviewStats' => $overviewStats,
		'recentJoined'  => $recentJoined,
		'pickerCourses' => $pickerCourses,
	)
);

$headerActions = '<span class="mint-hidden" aria-hidden="true"></span>';
if ( $courseId > 0 ) {
	$headerActions = '<button type="button" class="mint-cta-lime mint-inline-flex mint-h-[38px] mint-items-center mint-justify-center mint-gap-2 mint-rounded-lg mint-border-0 mint-px-[15px] mint-text-[15px] mint-font-semibold" onclick="window.dispatchEvent(new CustomEvent(\'mint-export-students\'))">'
		. '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 3.5v9M6.5 9.5 10 13l3.5-3.5M4 16h12"/></svg>'
		. esc_html__( 'Export CSV', 'mint-lms' )
		. '</button>';
}

$pageLabel     = __( 'Students', 'mint-lms' );
$pageLabelHtml = '';
if ( $courseId > 0 && '' !== $courseTitle ) {
	$studentsHome  = admin_url( 'admin.php?page=mint-lms-course-students' );
	$pageLabelHtml = '<a class="mint-admin-brand__crumb-link" href="' . esc_url( $studentsHome ) . '">' . esc_html__( 'Students', 'mint-lms' ) . '</a>'
		. '<svg class="mint-admin-brand__crumb-chevron" width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="#33334A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>'
		. '<span class="mint-admin-brand__crumb-current">' . esc_html( $courseTitle ) . '</span>';
}

$renderer->echo(
	'admin/layout',
	array(
		'pageTitle'     => __( 'Students', 'mint-lms' ),
		'pageLabel'     => $pageLabel,
		'pageLabelHtml' => $pageLabelHtml,
		'activeNav'     => 'courses',
		'headerActions' => $headerActions,
		'maxWidthClass' => $courseId > 0 ? 'mint-max-w-content mint-students-main' : 'mint-max-w-wide mint-students-main',
		'content'       => $content,
	)
);

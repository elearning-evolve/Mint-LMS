<?php
/**
 * Smoke tests for Mint LMS v1 — run after seeding demo data.
 *
 * Usage: wp eval-file wp-content/plugins/mint-lms-dev/scripts/smoke-v1.php
 *
 * @package MintLMS
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'MINTLMS_PATH' ) ) {
	WP_CLI::error( 'Mint LMS must be active.' );
}

MintLMS\Plugin::boot();

$wpdb = mintlms_wpdb();
if ( null === $wpdb ) {
	WP_CLI::error( 'Database unavailable.' );
}

$mintlms_failures = 0;

function mintlms_smoke_assert( bool $condition, string $label ): void {
	global $mintlms_failures;

	if ( $condition ) {
		WP_CLI::log( 'PASS: ' . $label );
		return;
	}

	++$mintlms_failures;
	WP_CLI::warning( 'FAIL: ' . $label );
}

$course = ( new MintLMS\Infrastructure\Database\Repository\WpPostCourseRepository() )->findBySlug( 'mint-lms-demo-course' );

mintlms_smoke_assert( null !== $course, 'Demo course exists' );

if ( null === $course ) {
	WP_CLI::error( 'Run seed-v1-demo.php first.' );
}

$student = get_user_by( 'login', 'mintstudent' );
mintlms_smoke_assert( $student instanceof WP_User, 'Demo student user exists' );

$studentId = $student instanceof WP_User ? (int) $student->ID : 0;

$courseRepo     = new MintLMS\Infrastructure\Database\Repository\WpPostCourseRepository();
$lessonRepo     = new MintLMS\Infrastructure\Database\Repository\WpPostLessonRepository();
$sectionRepo    = new MintLMS\Infrastructure\Database\Repository\WpdbSectionRepository( $wpdb, $lessonRepo );
$enrollmentRepo = new MintLMS\Infrastructure\Database\Repository\WpdbEnrollmentRepository( $wpdb );
$progressRepo   = new MintLMS\Infrastructure\Database\Repository\WpdbProgressRepository( $wpdb );
$quizRepo       = new MintLMS\Infrastructure\Database\Repository\WpPostQuizRepository( $wpdb );
$clock          = new MintLMS\Infrastructure\Clock\SystemClock();
$auth           = MintLMS\Plugin::authorization();
$lessonAccess   = new MintLMS\Application\Lesson\LessonAccessService( $lessonRepo, $enrollmentRepo, $progressRepo, $clock );
$userLookup     = new MintLMS\Infrastructure\User\WpUserLookup();
$quizService    = new MintLMS\Application\Quiz\QuizService( $quizRepo, $lessonRepo, $courseRepo, $auth, $clock, $progressRepo, $userLookup, $lessonAccess );
$studentService = new MintLMS\Application\Student\StudentExperienceService(
	$courseRepo,
	$sectionRepo,
	$enrollmentRepo,
	$progressRepo,
	$clock,
	$quizService,
	$auth,
);

mintlms_smoke_assert(
	$progressRepo->isUserEnrolled( $studentId, $course->id ),
	'Student enrolled in demo course'
);

$quizLessonId = null;
$dripLessonId = null;

foreach ( $sectionRepo->loadStructureRows( $course->id ) as $row ) {
	if ( null === $row['lesson'] ) {
		continue;
	}

	if ( str_contains( $row['lesson']->title, 'Knowledge Check' ) ) {
		$quizLessonId = $row['lesson']->id;
	}

	if ( str_contains( $row['lesson']->title, 'Graduation' ) ) {
		$dripLessonId = $row['lesson']->id;
	}
}

mintlms_smoke_assert( null !== $quizLessonId, 'Quiz lesson exists' );
mintlms_smoke_assert( null !== $dripLessonId, 'Drip lesson exists' );

if ( null !== $quizLessonId ) {
	$ctx = $studentService->getPlayerContext( $course->id, $quizLessonId, $studentId );
	mintlms_smoke_assert( $ctx->quizRequired, 'Player context marks quiz required' );
	mintlms_smoke_assert( null !== $ctx->quiz, 'Player context includes quiz payload' );
}

if ( null !== $dripLessonId ) {
	$enrollment = $enrollmentRepo->findByUserAndCourse( $studentId, $course->id );

	if ( null !== $enrollment ) {
		$enrollmentRepo->save(
			new MintLMS\Domain\Enrollment\Enrollment(
				$enrollment->id,
				$studentId,
				$course->id,
				$enrollment->status,
				$clock->now(),
				$enrollment->expiresAt,
				$enrollment->completedAt,
			)
		);
	}

	$dripBlocked = false;

	try {
		$lessonAccess->assertCanAccessLesson( $studentId, $dripLessonId );
	} catch ( MintLMS\Application\Exception\ForbiddenException $exception ) {
		$dripBlocked = true;
	}

	mintlms_smoke_assert( $dripBlocked, 'Drip lesson blocked before unlock date' );
}

$catalog = $studentService->getPublishedCatalogCourses();
mintlms_smoke_assert(
	count(
		array_filter(
			$catalog,
			static fn( MintLMS\Application\Student\Dto\StudentCatalogCourseDto $item ): bool => $item->courseId === $course->id
		)
	) > 0,
	'Demo course appears in published catalog'
);

$dashboardId = (int) get_option( 'mintlms_page_dashboard', 0 );
$catalogId   = (int) get_option( 'mintlms_page_catalog', 0 );
$playerId    = (int) get_option( 'mintlms_page_player', 0 );

mintlms_smoke_assert( $dashboardId > 0, 'Dashboard page configured' );
mintlms_smoke_assert( $catalogId > 0, 'Catalog page configured' );
mintlms_smoke_assert( $playerId > 0, 'Player page configured' );

if ( $mintlms_failures > 0 ) {
	WP_CLI::error( (string) $mintlms_failures . ' smoke test(s) failed.' );
}

WP_CLI::success( 'All Mint LMS v1 smoke tests passed.' );

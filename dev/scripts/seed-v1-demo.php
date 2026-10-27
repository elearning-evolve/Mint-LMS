<?php
/**
 * Seeds Mint LMS v1 demo course data for manual and automated verification.
 *
 * Usage: wp eval-file wp-content/plugins/mint-lms-dev/scripts/seed-v1-demo.php
 *
 * @package MintLMS
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'MINTLMS_PATH' ) ) {
	WP_CLI::error( 'Mint LMS must be active before seeding demo data.' );
}

use MintLMS\Application\Course\CourseService;
use MintLMS\Application\Course\Dto\CreateCourseDto;
use MintLMS\Application\Enrollment\EnrollmentService;
use MintLMS\Application\Lesson\Dto\CreateLessonDto;
use MintLMS\Application\Lesson\Dto\UpdateLessonDto;
use MintLMS\Application\Lesson\LessonService;
use MintLMS\Application\Quiz\Dto\CreateQuestionDto;
use MintLMS\Application\Quiz\Dto\CreateQuizDto;
use MintLMS\Application\Quiz\QuizService;
use MintLMS\Application\Section\Dto\CreateSectionDto;
use MintLMS\Application\Section\SectionService;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Infrastructure\Clock\SystemClock;
use MintLMS\Infrastructure\Database\Repository\WpdbAdminDashboardRepository;
use MintLMS\Infrastructure\Database\Repository\WpPostCourseRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbEnrollmentRepository;
use MintLMS\Infrastructure\Database\Repository\WpPostLessonRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbProgressRepository;
use MintLMS\Infrastructure\Database\Repository\WpPostQuizRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbSectionRepository;
use MintLMS\Infrastructure\User\WpUserLookup;
use MintLMS\Infrastructure\Setup\PageInstaller;
use MintLMS\Plugin;

$wpdb = mintlms_wpdb();
if ( null === $wpdb ) {
	WP_CLI::error( 'Database unavailable.' );
}

if ( ! $wpdb instanceof wpdb ) {
	WP_CLI::error( 'Database unavailable.' );
}

Plugin::boot();

$adminId = 1;
$clock   = new SystemClock();
$auth    = Plugin::authorization();

$courseRepo     = new WpPostCourseRepository();
$lessonRepo     = new WpPostLessonRepository();
$sectionRepo    = new WpdbSectionRepository( $wpdb, $lessonRepo );
$enrollmentRepo = new WpdbEnrollmentRepository( $wpdb );
$progressRepo   = new WpdbProgressRepository( $wpdb );
$quizRepo       = new WpPostQuizRepository( $wpdb );
$dashboardRepo  = new WpdbAdminDashboardRepository( $wpdb );

$courseService  = new CourseService( $courseRepo, $auth, $clock, $dashboardRepo );
$quizService    = new QuizService( $quizRepo, $lessonRepo, $courseRepo, $auth, $clock, $progressRepo, new WpUserLookup() );
$progressService = new MintLMS\Application\Progress\ProgressService(
	$progressRepo,
	Plugin::eventPublisher(),
	$clock,
	$quizService
);
$sectionService = new SectionService( $sectionRepo, $lessonRepo, $courseRepo, $auth, $clock );
$lessonService  = new LessonService(
	$lessonRepo,
	$sectionRepo,
	$courseRepo,
	$auth,
	$clock,
	$progressService
);
$enrollmentService = new EnrollmentService(
	$enrollmentRepo,
	$courseRepo,
	$auth,
	$clock,
	Plugin::eventPublisher()
);

$existing = $courseRepo->findBySlug( 'mint-lms-demo-course' );

if ( null !== $existing ) {
	$courseId = $existing->id;
	WP_CLI::log( 'Demo course already exists (ID ' . $courseId . ').' );
} else {
	$course = $courseService->create(
		new CreateCourseDto(
			'Mint LMS Demo Course',
			'mint-lms-demo-course',
			'A complete demo course covering lessons, quizzes, drip content, and certificates.',
			null,
			EnrollmentType::Open,
		),
		$adminId
	);
	$courseService->publish( $course->id, $adminId );
	$courseId = $course->id;

	$intro = $sectionService->create(
		$courseId,
		new CreateSectionDto( 'Getting Started' ),
		$adminId
	);
	$advanced = $sectionService->create(
		$courseId,
		new CreateSectionDto( 'Assessment & Completion' ),
		$adminId
	);

	$welcome = $lessonService->create(
		$intro->id,
		new CreateLessonDto(
			'Welcome to Mint LMS',
			null,
			'<p>This lesson introduces the Mint LMS student experience. Mark it complete to continue.</p>',
			'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
			null,
			true,
		),
		$adminId
	);

	$lessonService->create(
		$intro->id,
		new CreateLessonDto(
			'Core Concepts',
			null,
			'<p>Learn the fundamentals before taking the quiz in the next section.</p>',
			null,
			null,
			false,
		),
		$adminId
	);

	$quizLesson = $lessonService->create(
		$advanced->id,
		new CreateLessonDto(
			'Knowledge Check',
			null,
			'<p>Complete this quiz to unlock the final lesson.</p>',
			null,
			null,
			false,
		),
		$adminId
	);

	$dripLesson = $lessonService->create(
		$advanced->id,
		new CreateLessonDto(
			'Graduation (Drip)',
			null,
			'<p>This lesson unlocks 1 day after enrollment to demonstrate drip scheduling.</p>',
			null,
			null,
			false,
		),
		$adminId
	);

	$lessonService->update(
		$dripLesson->id,
		new UpdateLessonDto( availableAfterDays: 1, hasAvailableAfterDays: true ),
		$adminId
	);

	$quiz = $quizService->create(
		$quizLesson->id,
		new CreateQuizDto( 'Knowledge Check Quiz', 70 ),
		$adminId
	);

	$quizService->addQuestion(
		$quiz->id,
		new CreateQuestionDto(
			'mcq',
			'Mint LMS stores course data in:',
			array( 'WordPress posts', 'Custom database tables', 'Theme files' ),
			'Custom database tables',
		),
		$adminId
	);

	$quizService->addQuestion(
		$quiz->id,
		new CreateQuestionDto(
			'true_false',
			'Students must pass a lesson quiz before marking that lesson complete.',
			array( 'True', 'False' ),
			'True',
		),
		$adminId
	);

	WP_CLI::success( 'Created demo course ID ' . $courseId . ' with sections, lessons, quiz, and drip.' );
}

$studentLogin = 'mintstudent';
$student      = get_user_by( 'login', $studentLogin );

if ( ! $student instanceof WP_User ) {
	$studentId = wp_create_user( $studentLogin, 'MintStudent!2026', 'mintstudent@example.com' );

	if ( is_wp_error( $studentId ) ) {
		WP_CLI::error( $studentId->get_error_message() );
	}

	wp_update_user(
		array(
			'ID'           => $studentId,
			'display_name' => 'Mint Student',
			'role'         => 'subscriber',
		)
	);
	WP_CLI::log( 'Created student user "' . $studentLogin . '" (ID ' . $studentId . ').' );
} else {
	$studentId = (int) $student->ID;
	WP_CLI::log( 'Student user already exists (ID ' . $studentId . ').' );
}

if ( ! $enrollmentService->isEnrolled( $studentId, $courseId ) ) {
	$enrollmentService->enroll( $studentId, $courseId, $studentId );
	WP_CLI::log( 'Enrolled student in demo course.' );
}

( new PageInstaller() )->install();

$dashboardId = (int) get_option( 'mintlms_page_dashboard', 0 );
$catalogId     = (int) get_option( 'mintlms_page_catalog', 0 );
$playerId      = (int) get_option( 'mintlms_page_player', 0 );

WP_CLI::log( '' );
WP_CLI::log( 'Demo data summary:' );
WP_CLI::log( '  Course ID: ' . $courseId );
WP_CLI::log( '  Student: ' . $studentLogin . ' / MintStudent!2026' );
WP_CLI::log( '  Dashboard: ' . ( $dashboardId > 0 ? get_permalink( $dashboardId ) : 'not set' ) );
WP_CLI::log( '  Catalog: ' . ( $catalogId > 0 ? get_permalink( $catalogId ) : 'not set' ) );
WP_CLI::log( '  Player: ' . ( $playerId > 0 ? add_query_arg( array( 'mintlms_course' => (string) $courseId ), get_permalink( $playerId ) ) : 'not set' ) );

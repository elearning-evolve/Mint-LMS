<?php
/**
 * Full Mint LMS verification — admin pages, shortcodes, smoke checks.
 *
 * Usage: wp eval-file wp-content/plugins/mint-lms-dev/scripts/verify-all.php
 *
 * @package MintLMS
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'MINTLMS_PATH' ) ) {
	WP_CLI::error( 'Mint LMS must be active.' );
}

MintLMS\Plugin::boot();

$mintlms_verify_failures = 0;

function mintlms_verify( bool $condition, string $label ): void {
	global $mintlms_verify_failures;

	if ( $condition ) {
		WP_CLI::log( 'PASS: ' . $label );
		return;
	}

	++$mintlms_verify_failures;
	WP_CLI::warning( 'FAIL: ' . $label );
}

function mintlms_render_admin( string $label, callable $callback ): void {
	try {
		ob_start();
		$callback();
		$html = (string) ob_get_clean();

		mintlms_verify(
			! preg_match( '/(Fatal error|Uncaught|Warning:|Notice:|<b>Warning<\/b>)/i', $html ),
			'Admin page renders: ' . $label
		);
	} catch ( Throwable $exception ) {
		if ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		mintlms_verify( false, 'Admin page renders: ' . $label . ' (' . $exception->getMessage() . ')' );
	}
}

function mintlms_render_shortcode( string $tag, array $context = array() ): void {
	$previousGet = $_GET;

	foreach ( $context as $key => $value ) {
		$_GET[ $key ] = $value;
	}

	try {
		$html = do_shortcode( '[' . $tag . ']' );
		mintlms_verify(
			is_string( $html ) && '' !== $html && ! preg_match( '/(Fatal error|Uncaught|Warning:|Notice:)/i', $html ),
			'Shortcode [' . $tag . ']'
		);
	} catch ( Throwable $exception ) {
		mintlms_verify( false, 'Shortcode [' . $tag . '] (' . $exception->getMessage() . ')' );
	}

	$_GET = $previousGet;
}

$wpdb = mintlms_wpdb();
if ( null === $wpdb ) {
	WP_CLI::error( 'Database unavailable.' );
}

$adminId   = 1;
$student   = get_user_by( 'login', 'mintstudent' );
$studentId = $student instanceof WP_User ? (int) $student->ID : 0;
$course    = ( new MintLMS\Infrastructure\Database\Repository\WpPostCourseRepository() )->findBySlug( 'mint-lms-demo-course' );
$courseId  = null !== $course ? $course->id : 0;

wp_set_current_user( $adminId );

$registrar = new MintLMS\Infrastructure\Admin\MenuRegistrar();

mintlms_render_admin( 'dashboard', fn() => $registrar->renderDashboard() );
mintlms_render_admin( 'settings', fn() => ( new MintLMS\Infrastructure\Admin\SettingsPage() )->render() );
mintlms_render_admin( 'courses', fn() => $registrar->renderCourses() );
mintlms_render_admin( 'reports', fn() => $registrar->renderReports() );
mintlms_render_admin( 'guided-course', fn() => $registrar->renderGuidedCourse() );

if ( $courseId > 0 ) {
	$_GET['course_id'] = $courseId;
	mintlms_render_admin( 'builder', fn() => ( new MintLMS\Infrastructure\Admin\CourseBuilderPage() )->renderBuilder() );
	mintlms_render_admin( 'edit', fn() => ( new MintLMS\Infrastructure\Admin\CourseBuilderPage() )->renderEdit() );
	mintlms_render_admin( 'students', fn() => $registrar->renderCourseStudents() );
	unset( $_GET['course_id'] );
}

$shortcodeContext = $courseId > 0 ? array( 'mint_course' => (string) $courseId ) : array();

foreach ( array( 'mint_lms_dashboard', 'mint_lms_my_courses', 'mint_lms_catalog', 'mint_lms_course', 'mint_lms_player', 'mint_lms_certificate' ) as $tag ) {
	mintlms_render_shortcode( $tag, $shortcodeContext );
}

if ( $studentId > 0 ) {
	wp_set_current_user( $studentId );

	foreach ( array( 'mint_lms_dashboard', 'mint_lms_my_courses', 'mint_lms_catalog', 'mint_lms_player' ) as $tag ) {
		mintlms_render_shortcode( $tag, $shortcodeContext );
	}
}

wp_set_current_user( $adminId );

if ( $courseId > 0 ) {
	$experience = new MintLMS\Application\Student\StudentExperienceService(
		new MintLMS\Infrastructure\Database\Repository\WpPostCourseRepository(),
		new MintLMS\Infrastructure\Database\Repository\WpdbSectionRepository( $wpdb, new MintLMS\Infrastructure\Database\Repository\WpPostLessonRepository() ),
		new MintLMS\Infrastructure\Database\Repository\WpdbEnrollmentRepository( $wpdb ),
		new MintLMS\Infrastructure\Database\Repository\WpdbProgressRepository( $wpdb ),
		new MintLMS\Infrastructure\Clock\SystemClock(),
		null,
		MintLMS\Plugin::authorization(),
	);

	try {
		$overview = $experience->getCourseOverview( $courseId, $adminId );
		mintlms_verify( '' !== $overview->title, 'Course overview for admin' );
	} catch ( Throwable $exception ) {
		mintlms_verify( false, 'Course overview for admin (' . $exception->getMessage() . ')' );
	}

	$draftCourse = ( new MintLMS\Infrastructure\Database\Repository\WpPostCourseRepository() )->list( 1, 50, $adminId, MintLMS\Domain\Course\CourseStatus::Draft );
	$draft       = $draftCourse['courses'][0] ?? null;

	if ( null !== $draft ) {
		try {
			$experience->getCourseOverview( $draft->id, $adminId );
			mintlms_verify( true, 'Draft course overview for author' );
		} catch ( Throwable $exception ) {
			mintlms_verify( false, 'Draft course overview for author (' . $exception->getMessage() . ')' );
		}
	}

	require_once ABSPATH . 'wp-includes/rest-api.php';
	do_action( 'rest_api_init' );
	$server = rest_get_server();

	$sectionId = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}mintlms_sections WHERE course_id = %d ORDER BY sort_order ASC, id ASC LIMIT 1",
			$courseId
		)
	);

	if ( $sectionId > 0 ) {
		$createRequest = new WP_REST_Request( 'POST', '/mintlms/v1/sections/' . $sectionId . '/lessons' );
		$createRequest->set_header( 'Content-Type', 'application/json' );
		$createRequest->set_body(
			wp_json_encode(
				array(
					'title'      => 'Verify REST Lesson',
					'content'    => '',
					'is_preview' => false,
				)
			)
		);
		$createResponse = $server->dispatch( $createRequest );
		$createData     = $createResponse->get_data();
		mintlms_verify( 201 === $createResponse->get_status(), 'REST create lesson' );
		$lessonId = is_array( $createData ) && isset( $createData['data']['id'] ) ? (int) $createData['data']['id'] : 0;
		mintlms_verify( $lessonId > 0, 'REST create lesson returns id' );

		if ( $lessonId > 0 ) {
			$patchRequest = new WP_REST_Request( 'PATCH', '/mintlms/v1/lessons/' . $lessonId );
			$patchRequest->set_header( 'Content-Type', 'application/json' );
			$patchRequest->set_body( wp_json_encode( array( 'title' => 'Verify REST Lesson Updated' ) ) );
			$patchResponse = $server->dispatch( $patchRequest );
			mintlms_verify( 200 === $patchResponse->get_status(), 'REST update lesson' );

			$deleteRequest = new WP_REST_Request( 'DELETE', '/mintlms/v1/lessons/' . $lessonId );
			$deleteResponse = $server->dispatch( $deleteRequest );
			mintlms_verify( 204 === $deleteResponse->get_status(), 'REST delete lesson' );
		}
	}

	$newSectionRequest = new WP_REST_Request( 'POST', '/mintlms/v1/courses/' . $courseId . '/sections' );
	$newSectionRequest->set_header( 'Content-Type', 'application/json' );
	$newSectionRequest->set_body( wp_json_encode( array( 'title' => 'Verify REST Section' ) ) );
	$newSectionResponse = $server->dispatch( $newSectionRequest );
	$newSectionData     = $newSectionResponse->get_data();
	mintlms_verify( 201 === $newSectionResponse->get_status(), 'REST create section' );
	$newSectionId = is_array( $newSectionData ) && isset( $newSectionData['data']['id'] ) ? (int) $newSectionData['data']['id'] : 0;

	if ( $newSectionId > 0 ) {
		$deleteSectionRequest = new WP_REST_Request( 'DELETE', '/mintlms/v1/sections/' . $newSectionId );
		$deleteSectionResponse = $server->dispatch( $deleteSectionRequest );
		mintlms_verify( 204 === $deleteSectionResponse->get_status(), 'REST delete section' );
	}
}

WP_CLI::log( '' );
WP_CLI::log( 'Running verify-rest-actions.php...' );
require MINTLMS_PATH . 'dev/scripts/verify-rest-actions.php';
$restExit = ( $mintlms_rest_failures ?? 0 ) > 0 ? 1 : 0;
mintlms_verify( 0 === $restExit, 'REST actions script exit code 0' );

WP_CLI::log( '' );
WP_CLI::log( 'Running smoke-v1.php...' );
require MINTLMS_PATH . 'dev/scripts/smoke-v1.php';
$smokeExit = ( $mintlms_failures ?? 0 ) > 0 ? 1 : 0;
mintlms_verify( 0 === $smokeExit, 'Smoke script exit code 0' );

if ( $mintlms_verify_failures > 0 ) {
	WP_CLI::error( (string) $mintlms_verify_failures . ' verification check(s) failed.' );
}

WP_CLI::success( 'All Mint LMS verification checks passed.' );

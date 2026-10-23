<?php
/**
 * Exercises every Mint LMS REST action used by admin/student UI buttons.
 *
 * Usage: wp eval-file wp-content/plugins/mint-lms-dev/scripts/verify-rest-actions.php
 *
 * @package MintLMS
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'MINTLMS_PATH' ) ) {
	WP_CLI::error( 'Mint LMS must be active.' );
}

MintLMS\Plugin::boot();

require_once ABSPATH . 'wp-includes/rest-api.php';
do_action( 'rest_api_init' );

$wpdb = mintlms_wpdb();
if ( null === $wpdb ) {
	WP_CLI::error( 'Database unavailable.' );
}

$mintlms_rest_failures = 0;
$mintlms_created         = array(
	'course_ids'     => array(),
	'section_ids'    => array(),
	'lesson_ids'     => array(),
	'quiz_ids'       => array(),
	'enrollment_ids' => array(),
);

function mintlms_rest_failures(): int {
	global $mintlms_rest_failures;

	return (int) $mintlms_rest_failures;
}

function mintlms_rest_assert( bool $condition, string $label ): void {
	global $mintlms_rest_failures;

	if ( $condition ) {
		WP_CLI::log( 'PASS: ' . $label );
		return;
	}

	++$mintlms_rest_failures;
	WP_CLI::warning( 'FAIL: ' . $label );
}

/**
 * @return array{status:int,body:array<string,mixed>|mixed}
 */
function mintlms_rest_dispatch( string $method, string $route, ?array $body = null, array $query = array() ): array {
	$path = $route;
	if ( array() !== $query ) {
		$path = strtok( $route, '?' );
	}

	$request = new WP_REST_Request( $method, $path );
	$request->set_header( 'Content-Type', 'application/json' );

	foreach ( $query as $key => $value ) {
		$request->set_param( (string) $key, $value );
	}

	if ( null !== $body ) {
		$request->set_body( wp_json_encode( $body ) );
	}

	$response = rest_get_server()->dispatch( $request );
	$data     = $response->get_data();

	return array(
		'status' => (int) $response->get_status(),
		'body'   => is_array( $data ) ? $data : array(),
	);
}

function mintlms_rest_data( array $result ): mixed {
	return $result['body']['data'] ?? null;
}

function mintlms_rest_cleanup(): void {
	global $mintlms_created;

	$created = is_array( $mintlms_created ) ? $mintlms_created : array();

	foreach ( $mintlms_created['quiz_ids'] ?? array() as $quizId ) {
		mintlms_rest_dispatch( 'DELETE', '/mintlms/v1/quizzes/' . (int) $quizId );
	}

	foreach ( $mintlms_created['lesson_ids'] ?? array() as $lessonId ) {
		mintlms_rest_dispatch( 'DELETE', '/mintlms/v1/lessons/' . (int) $lessonId );
	}

	foreach ( $mintlms_created['section_ids'] ?? array() as $sectionId ) {
		mintlms_rest_dispatch( 'DELETE', '/mintlms/v1/sections/' . (int) $sectionId );
	}

	foreach ( $mintlms_created['enrollment_ids'] ?? array() as $enrollmentId ) {
		mintlms_rest_dispatch( 'DELETE', '/mintlms/v1/enrollments/' . (int) $enrollmentId );
	}

	foreach ( $mintlms_created['course_ids'] ?? array() as $courseId ) {
		mintlms_rest_dispatch( 'DELETE', '/mintlms/v1/courses/' . (int) $courseId );
	}
}

$admin = get_users(
	array(
		'role'   => 'administrator',
		'number' => 1,
	)
)[0] ?? null;

if ( ! $admin instanceof WP_User ) {
	WP_CLI::error( 'No administrator user found.' );
}

$student = get_user_by( 'login', 'mintstudent' );
if ( ! $student instanceof WP_User ) {
	$student = get_users( array( 'role' => 'subscriber', 'number' => 1 ) )[0] ?? null;
}

wp_set_current_user( (int) $admin->ID );

// Courses list / create / read / update / structure / publish.
$list = mintlms_rest_dispatch( 'GET', '/mintlms/v1/courses', null, array( 'page' => 1, 'per_page' => 5 ) );
mintlms_rest_assert( 200 === $list['status'], 'GET courses list' );

$createCourse = mintlms_rest_dispatch(
	'POST',
	'/mintlms/v1/courses',
	array(
		'title'           => 'REST Action Verify Course',
		'enrollment_type' => 'open',
	)
);
mintlms_rest_assert( 201 === $createCourse['status'], 'POST create course' );
$courseId = (int) ( mintlms_rest_data( $createCourse )['id'] ?? 0 );
mintlms_rest_assert( $courseId > 0, 'POST create course returns id' );
if ( $courseId > 0 ) {
	$mintlms_created['course_ids'][] = $courseId;
}

$getCourse = mintlms_rest_dispatch( 'GET', '/mintlms/v1/courses/' . $courseId );
mintlms_rest_assert( 200 === $getCourse['status'], 'GET course' );

$patchCourse = mintlms_rest_dispatch(
	'PATCH',
	'/mintlms/v1/courses/' . $courseId,
	array(
		'title'       => 'REST Action Verify Course Updated',
		'description' => 'Updated via REST verify script.',
	)
);
mintlms_rest_assert( 200 === $patchCourse['status'], 'PATCH update course' );

$structure = mintlms_rest_dispatch( 'GET', '/mintlms/v1/courses/' . $courseId . '/structure' );
mintlms_rest_assert( 200 === $structure['status'], 'GET course structure' );

// Sections: create / update / reorder / delete.
$createSection = mintlms_rest_dispatch(
	'POST',
	'/mintlms/v1/courses/' . $courseId . '/sections',
	array( 'title' => 'Verify Section' )
);
mintlms_rest_assert( 201 === $createSection['status'], 'POST create section' );
$sectionId = (int) ( mintlms_rest_data( $createSection )['id'] ?? 0 );
if ( $sectionId > 0 ) {
	$mintlms_created['section_ids'][] = $sectionId;
}

$patchSection = mintlms_rest_dispatch(
	'PATCH',
	'/mintlms/v1/sections/' . $sectionId,
	array( 'title' => 'Verify Section Updated' )
);
mintlms_rest_assert( 200 === $patchSection['status'], 'PATCH update section' );

$reorderSections = mintlms_rest_dispatch(
	'POST',
	'/mintlms/v1/courses/' . $courseId . '/sections/reorder',
	array( 'ids' => array( $sectionId ) )
);
mintlms_rest_assert( 204 === $reorderSections['status'], 'POST reorder sections' );

// Lessons: create / update / reorder / delete.
$createLesson = mintlms_rest_dispatch(
	'POST',
	'/mintlms/v1/sections/' . $sectionId . '/lessons',
	array(
		'title'      => 'Verify Lesson',
		'content'    => '<p>Body</p>',
		'is_preview' => false,
	)
);
mintlms_rest_assert( 201 === $createLesson['status'], 'POST create lesson' );
$lessonId = (int) ( mintlms_rest_data( $createLesson )['id'] ?? 0 );
if ( $lessonId > 0 ) {
	$mintlms_created['lesson_ids'][] = $lessonId;
}

$getLesson = mintlms_rest_dispatch( 'GET', '/mintlms/v1/lessons/' . $lessonId );
mintlms_rest_assert( 200 === $getLesson['status'], 'GET lesson' );

$patchLesson = mintlms_rest_dispatch(
	'PATCH',
	'/mintlms/v1/lessons/' . $lessonId,
	array(
		'title'   => 'Verify Lesson Updated',
		'content' => '<p>Updated</p>',
	)
);
mintlms_rest_assert( 200 === $patchLesson['status'], 'PATCH update lesson' );

$reorderLessons = mintlms_rest_dispatch(
	'POST',
	'/mintlms/v1/sections/' . $sectionId . '/lessons/reorder',
	array( 'ids' => array( $lessonId ) )
);
mintlms_rest_assert( 204 === $reorderLessons['status'], 'POST reorder lessons' );

// Enrollment / students admin actions (before quiz — quiz blocks completion).
if ( $student instanceof WP_User ) {
	$enroll = mintlms_rest_dispatch(
		'POST',
		'/mintlms/v1/courses/' . $courseId . '/enroll',
		array( 'user_id' => (int) $student->ID )
	);
	mintlms_rest_assert( 201 === $enroll['status'], 'POST enroll student' );
	$enrollmentId = (int) ( mintlms_rest_data( $enroll )['id'] ?? 0 );
	if ( $enrollmentId > 0 ) {
		$mintlms_created['enrollment_ids'][] = $enrollmentId;
	}

	$listStudents = mintlms_rest_dispatch( 'GET', '/mintlms/v1/courses/' . $courseId . '/students', null, array( 'page' => 1, 'per_page' => 20 ) );
	mintlms_rest_assert( 200 === $listStudents['status'], 'GET course students' );

	$archiveCourse = mintlms_rest_dispatch(
		'PATCH',
		'/mintlms/v1/courses/' . $courseId,
		array( 'status' => 'archived' )
	);
	mintlms_rest_assert( 200 === $archiveCourse['status'], 'PATCH archive course' );

	$archivedList = mintlms_rest_dispatch(
		'GET',
		'/mintlms/v1/courses',
		null,
		array(
			'page'     => 1,
			'per_page' => 20,
			'status'   => 'archived',
		)
	);
	mintlms_rest_assert( 200 === $archivedList['status'], 'GET archived courses list' );
	$archivedItems   = mintlms_rest_data( $archivedList )['items'] ?? array();
	$archivedCourse  = null;
	foreach ( $archivedItems as $item ) {
		if ( is_array( $item ) && (int) ( $item['id'] ?? 0 ) === $courseId ) {
			$archivedCourse = $item;
			break;
		}
	}
	mintlms_rest_assert( is_array( $archivedCourse ), 'Archived list contains archived course' );
	mintlms_rest_assert(
		( $archivedCourse['lessonCount'] ?? 0 ) >= 1,
		'Archived course list includes lesson stats'
	);
	mintlms_rest_assert(
		( $archivedCourse['studentCount'] ?? 0 ) >= 1,
		'Archived course list includes student stats'
	);

	$unarchiveCourse = mintlms_rest_dispatch(
		'PATCH',
		'/mintlms/v1/courses/' . $courseId,
		array( 'status' => 'draft' )
	);
	mintlms_rest_assert( 200 === $unarchiveCourse['status'], 'PATCH unarchive course to draft' );

	wp_set_current_user( (int) $student->ID );

	$complete = mintlms_rest_dispatch(
		'POST',
		'/mintlms/v1/lessons/' . $lessonId . '/complete',
		array( 'course_id' => $courseId )
	);
	mintlms_rest_assert( 200 === $complete['status'], 'POST complete lesson' );

	$progress = mintlms_rest_dispatch( 'GET', '/mintlms/v1/courses/' . $courseId . '/progress' );
	mintlms_rest_assert( 200 === $progress['status'], 'GET course progress' );

	$myCourses = mintlms_rest_dispatch( 'GET', '/mintlms/v1/me/courses', null, array( 'page' => 1, 'per_page' => 20 ) );
	mintlms_rest_assert( 200 === $myCourses['status'], 'GET my courses' );

	wp_set_current_user( (int) $admin->ID );
}

// Quiz builder actions.
$getQuizEmpty = mintlms_rest_dispatch( 'GET', '/mintlms/v1/lessons/' . $lessonId . '/quiz' );
mintlms_rest_assert( 200 === $getQuizEmpty['status'], 'GET lesson quiz (empty)' );

$createQuiz = mintlms_rest_dispatch(
	'POST',
	'/mintlms/v1/lessons/' . $lessonId . '/quiz',
	array(
		'title'        => 'Verify Quiz',
		'pass_percent' => 70,
	)
);
mintlms_rest_assert( 201 === $createQuiz['status'], 'POST create quiz' );
$quizId = (int) ( mintlms_rest_data( $createQuiz )['id'] ?? 0 );
if ( $quizId > 0 ) {
	$mintlms_created['quiz_ids'][] = $quizId;
}

$patchQuiz = mintlms_rest_dispatch(
	'PATCH',
	'/mintlms/v1/quizzes/' . $quizId,
	array(
		'title'        => 'Verify Quiz Updated',
		'pass_percent' => 80,
	)
);
mintlms_rest_assert( 200 === $patchQuiz['status'], 'PATCH update quiz' );

$addQuestion = mintlms_rest_dispatch(
	'POST',
	'/mintlms/v1/quizzes/' . $quizId . '/questions',
	array(
		'type'           => 'mcq',
		'prompt'         => 'Pick one',
		'options'        => array( 'A', 'B' ),
		'correct_answer' => 'A',
	)
);
mintlms_rest_assert( 200 === $addQuestion['status'], 'POST add quiz question' );
$questionId = 0;
$quizAfterQuestion = mintlms_rest_data( $addQuestion );
if ( is_array( $quizAfterQuestion ) && isset( $quizAfterQuestion['questions'][0]['id'] ) ) {
	$questionId = (int) $quizAfterQuestion['questions'][0]['id'];
}

if ( $questionId > 0 ) {
	$patchQuestion = mintlms_rest_dispatch(
		'PATCH',
		'/mintlms/v1/quizzes/' . $quizId . '/questions/' . $questionId,
		array(
			'prompt'         => 'Pick one (updated)',
			'options'        => array( 'A', 'B', 'C' ),
			'correct_answer' => 'C',
		)
	);
	mintlms_rest_assert( 200 === $patchQuestion['status'], 'PATCH update quiz question' );
}

// Publish course (builder publish button).
$publish = mintlms_rest_dispatch( 'POST', '/mintlms/v1/courses/' . $courseId . '/publish' );
mintlms_rest_assert( 200 === $publish['status'], 'POST publish course' );

$userSearch = mintlms_rest_dispatch( 'GET', '/mintlms/v1/users/search', null, array( 'q' => $admin->user_email ) );
mintlms_rest_assert( 200 === $userSearch['status'], 'GET users search' );

$onboarding = mintlms_rest_dispatch( 'POST', '/mintlms/v1/onboarding/complete' );
mintlms_rest_assert( 200 === $onboarding['status'], 'POST onboarding complete' );

$userPref = mintlms_rest_dispatch(
	'POST',
	'/mintlms/v1/user-pref',
	array(
		'key'   => 'mint_lms_show_details',
		'value' => '1',
	)
);
mintlms_rest_assert( 200 === $userPref['status'], 'POST dashboard user preference' );

// Student quiz attempt (after quiz exists).
if ( $student instanceof WP_User && $quizId > 0 ) {
	wp_set_current_user( (int) $student->ID );

	$attempt = mintlms_rest_dispatch(
		'POST',
		'/mintlms/v1/quizzes/' . $quizId . '/attempt',
		array( 'answers' => array() )
	);
	mintlms_rest_assert( in_array( $attempt['status'], array( 200, 400 ), true ), 'POST quiz attempt (validation or success)' );

	wp_set_current_user( (int) $admin->ID );
}

// Guided wizard duplicate path: second course with section + lesson.
$guidedCourse = mintlms_rest_dispatch(
	'POST',
	'/mintlms/v1/courses',
	array(
		'title'           => 'Guided Path Verify',
		'description'     => 'Wizard flow',
		'enrollment_type' => 'open',
	)
);
$guidedCourseId = (int) ( mintlms_rest_data( $guidedCourse )['id'] ?? 0 );
if ( $guidedCourseId > 0 ) {
	$mintlms_created['course_ids'][] = $guidedCourseId;
	$guidedSection           = mintlms_rest_dispatch(
		'POST',
		'/mintlms/v1/courses/' . $guidedCourseId . '/sections',
		array( 'title' => 'Section 1' )
	);
	mintlms_rest_assert( 201 === $guidedSection['status'], 'POST guided wizard section' );
	$guidedSectionId = (int) ( mintlms_rest_data( $guidedSection )['id'] ?? 0 );
	if ( $guidedSectionId > 0 ) {
		$mintlms_created['section_ids'][] = $guidedSectionId;
		$guidedLesson             = mintlms_rest_dispatch(
			'POST',
			'/mintlms/v1/sections/' . $guidedSectionId . '/lessons',
			array(
				'title'   => 'Lesson 1',
				'content' => 'Intro',
			)
		);
		mintlms_rest_assert( 201 === $guidedLesson['status'], 'POST guided wizard lesson' );
		$guidedLessonId = (int) ( mintlms_rest_data( $guidedLesson )['id'] ?? 0 );
		if ( $guidedLessonId > 0 ) {
			$mintlms_created['lesson_ids'][] = $guidedLessonId;
		}
	}
}

// Cleanup transient verify entities.
mintlms_rest_cleanup();

if ( mintlms_rest_failures() > 0 ) {
	WP_CLI::error( (string) mintlms_rest_failures() . ' REST action check(s) failed.' );
}

WP_CLI::success( 'All Mint LMS REST action checks passed.' );

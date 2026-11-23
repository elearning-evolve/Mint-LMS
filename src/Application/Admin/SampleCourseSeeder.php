<?php
declare(strict_types=1);

namespace MintLMS\Application\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\ProgressLifecycleInterface;
use MintLMS\Application\Course\CourseService;
use MintLMS\Application\Course\Dto\CreateCourseDto;
use MintLMS\Application\Lesson\Dto\CreateLessonDto;
use MintLMS\Application\Lesson\LessonAccessService;
use MintLMS\Application\Lesson\LessonService;
use MintLMS\Application\Quiz\Dto\CreateQuizDto;
use MintLMS\Application\Quiz\QuizService;
use MintLMS\Application\Section\Dto\CreateSectionDto;
use MintLMS\Application\Section\SectionService;
use MintLMS\Domain\Course\EnrollmentType;
use MintLMS\Infrastructure\Clock\SystemClock;
use MintLMS\Infrastructure\Database\Repository\WpPostCourseRepository;
use MintLMS\Infrastructure\Database\Repository\WpPostLessonRepository;
use MintLMS\Infrastructure\Database\Repository\WpPostQuizRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbEnrollmentRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbProgressRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbSectionRepository;
use MintLMS\Infrastructure\User\WpUserLookup;
use MintLMS\Plugin;

/**
 * Seeds a demo course hierarchy for first-run Admin onboarding.
 *
 * Course → Lesson Group → Lesson → 2 Quizzes → 2 Questions each.
 */
final class SampleCourseSeeder {

	public function __construct(
		private CourseService $courseService,
		private SectionService $sectionService,
		private LessonService $lessonService,
		private QuizService $quizService,
	) {
	}

	public static function make(): self {
		$wpdb = mintlms_wpdb();
		if ( null === $wpdb ) {
			throw new \RuntimeException( 'Database unavailable.' );
		}

		$clock          = new SystemClock();
		$authorization  = Plugin::authorization();
		$userLookup     = new WpUserLookup();
		$courseRepo     = new WpPostCourseRepository();
		$lessonRepo     = new WpPostLessonRepository();
		$sectionRepo    = new WpdbSectionRepository( $wpdb, $lessonRepo );
		$enrollmentRepo = new WpdbEnrollmentRepository( $wpdb );
		$progressRepo   = new WpdbProgressRepository( $wpdb );
		$quizRepo       = new WpPostQuizRepository( $wpdb );

		$courseService     = new CourseService(
			$courseRepo,
			$authorization,
			$clock,
			null,
			$userLookup,
			Plugin::eventPublisher()
		);
		$lessonAccess      = new LessonAccessService(
			$lessonRepo,
			$enrollmentRepo,
			$progressRepo,
			$clock,
			$courseRepo,
			$sectionRepo
		);
		$quizService       = new QuizService(
			$quizRepo,
			$lessonRepo,
			$courseRepo,
			$authorization,
			$clock,
			$progressRepo,
			$userLookup,
			$lessonAccess
		);
		$progressLifecycle = new class() implements ProgressLifecycleInterface {
			public function onLessonDeleted( int $lessonId ): void {
				unset( $lessonId );
			}
		};

		$sectionService = new SectionService(
			$sectionRepo,
			$lessonRepo,
			$courseRepo,
			$authorization,
			$clock,
			$quizService
		);
		$lessonService  = new LessonService(
			$lessonRepo,
			$sectionRepo,
			$courseRepo,
			$authorization,
			$clock,
			$progressLifecycle,
			$quizService
		);

		return new self( $courseService, $sectionService, $lessonService, $quizService );
	}

	/**
	 * @return int Created course ID.
	 */
	public function seedForUser( int $userId ): int {
		if ( $userId <= 0 ) {
			throw new \InvalidArgumentException( 'Invalid user.' );
		}

		$course = $this->courseService->create(
			new CreateCourseDto(
				__( 'Sample Course (you can edit or delete)', 'mint-lms' ),
				null,
				__( 'A starter course so you can explore how Mint LMS is structured. Edit or delete it anytime.', 'mint-lms' ),
				null,
				EnrollmentType::Free,
			),
			$userId
		);

		$section = $this->sectionService->create(
			$course->id,
			new CreateSectionDto( __( 'Getting Started', 'mint-lms' ) ),
			$userId
		);

		$lesson = $this->lessonService->create(
			$section->id,
			new CreateLessonDto(
				__( 'Welcome Lesson', 'mint-lms' ),
				null,
				'<p>' . esc_html__( 'This is a sample lesson. Replace this text with your own teaching content — text, video, or both.', 'mint-lms' ) . '</p>',
				null,
				null,
				true,
			),
			$userId
		);

		$this->quizService->create(
			$lesson->id,
			new CreateQuizDto(
				__( 'Sample Quiz 1', 'mint-lms' ),
				80,
				array(
					array(
						'type'           => 'mcq',
						'prompt'         => __( 'What sits at the top of a Mint course?', 'mint-lms' ),
						'options'        => array(
							__( 'Course', 'mint-lms' ),
							__( 'Quiz', 'mint-lms' ),
							__( 'Question', 'mint-lms' ),
							__( 'Student', 'mint-lms' ),
						),
						'correct_answer' => __( 'Course', 'mint-lms' ),
					),
					array(
						'type'           => 'mcq',
						'prompt'         => __( 'How do you connect a new lesson into a lesson group?', 'mint-lms' ),
						'options'        => array(
							__( 'Attach', 'mint-lms' ),
							__( 'Publish', 'mint-lms' ),
							__( 'Enroll', 'mint-lms' ),
							__( 'Delete', 'mint-lms' ),
						),
						'correct_answer' => __( 'Attach', 'mint-lms' ),
					),
				)
			),
			$userId
		);

		$this->quizService->create(
			$lesson->id,
			new CreateQuizDto(
				__( 'Sample Quiz 2', 'mint-lms' ),
				80,
				array(
					array(
						'type'           => 'mcq',
						'prompt'         => __( 'Where do questions belong?', 'mint-lms' ),
						'options'        => array(
							__( 'Inside a quiz', 'mint-lms' ),
							__( 'Inside a lesson group only', 'mint-lms' ),
							__( 'Only on the dashboard', 'mint-lms' ),
							__( 'In WordPress menus', 'mint-lms' ),
						),
						'correct_answer' => __( 'Inside a quiz', 'mint-lms' ),
					),
					array(
						'type'           => 'mcq',
						'prompt'         => __( 'When can students start learning the course?', 'mint-lms' ),
						'options'        => array(
							__( 'After you publish and they are enrolled', 'mint-lms' ),
							__( 'Only after deleting the sample', 'mint-lms' ),
							__( 'Never — drafts are enough', 'mint-lms' ),
							__( 'Only on mobile', 'mint-lms' ),
						),
						'correct_answer' => __( 'After you publish and they are enrolled', 'mint-lms' ),
					),
				)
			),
			$userId
		);

		return $course->id;
	}
}

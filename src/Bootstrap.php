<?php
declare(strict_types=1);

namespace MintLMS;

use MintLMS\Application\Certificate\CertificateService;
use MintLMS\Application\Course\CourseService;
use MintLMS\Application\Course\CourseStructureService;
use MintLMS\Application\Enrollment\EnrollmentService;
use MintLMS\Application\Lesson\LessonAccessService;
use MintLMS\Application\Lesson\LessonService;
use MintLMS\Application\Progress\ProgressService;
use MintLMS\Application\Quiz\QuizService;
use MintLMS\Application\Section\SectionService;
use MintLMS\Application\Student\StudentExperienceService;
use MintLMS\Frontend\ShortcodeRegistrar;
use MintLMS\Frontend\StudentAssetLoader;
use MintLMS\Frontend\TemplateLoader;
use MintLMS\Http\Rest\Controller\ContentPostListController;
use MintLMS\Http\Rest\Controller\CourseController;
use MintLMS\Http\Rest\Controller\EnrollmentController;
use MintLMS\Http\Rest\Controller\LessonController;
use MintLMS\Http\Rest\Controller\OnboardingController;
use MintLMS\Http\Rest\Controller\ProgressController;
use MintLMS\Http\Rest\Controller\QuizController;
use MintLMS\Http\Rest\Controller\SectionController;
use MintLMS\Http\Rest\Controller\UserController;
use MintLMS\Http\Rest\RouteRegistrar;
use MintLMS\Infrastructure\Admin\AdminAjaxHandler;
use MintLMS\Infrastructure\Admin\AssetLoader;
use MintLMS\Infrastructure\Admin\CourseBuilderPage;
use MintLMS\Infrastructure\Admin\CoursePostEditUi;
use MintLMS\Infrastructure\Admin\FirstRunRedirect;
use MintLMS\Infrastructure\Admin\LessonPostEditUi;
use MintLMS\Infrastructure\Admin\MenuRegistrar;
use MintLMS\Infrastructure\Admin\NativeEditorSaveGuard;
use MintLMS\Infrastructure\Admin\QuestionPostEditUi;
use MintLMS\Infrastructure\Admin\QuizPostEditUi;
use MintLMS\Infrastructure\Admin\SettingsPage;
use MintLMS\Infrastructure\Clock\SystemClock;
use MintLMS\Infrastructure\Certificate\CertificateDownloadHandler;
use MintLMS\Infrastructure\Certificate\WpCertificateTemplateProvider;
use MintLMS\Infrastructure\Certificate\WpCertificateUrlBuilder;
use MintLMS\Infrastructure\Database\Migration\Migration_001_InitialSchema;
use MintLMS\Infrastructure\Database\Migration\Migration_002_Quizzes;
use MintLMS\Infrastructure\Database\Migration\Migration_003_Drip;
use MintLMS\Infrastructure\Database\Migration\Migration_004_CertificateTemplate;
use MintLMS\Infrastructure\Database\Migration\Migration_005_CourseSettings;
use MintLMS\Infrastructure\Database\Migration\Migration_006_CptHybrid;
use MintLMS\Infrastructure\Database\Migration\Migration_007_MultiQuizPerLesson;
use MintLMS\Infrastructure\Database\Migration\Migration_008_CourseAccessSettings;
use MintLMS\Infrastructure\Database\Migration\MigrationRunner;
use MintLMS\Infrastructure\Database\Repository\WpdbAdminDashboardRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbEnrollmentRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbProgressRepository;
use MintLMS\Infrastructure\Database\Repository\WpdbSectionRepository;
use MintLMS\Infrastructure\Database\Repository\WpPostCourseRepository;
use MintLMS\Infrastructure\Database\Repository\WpPostLessonRepository;
use MintLMS\Infrastructure\Database\Repository\WpPostQuizRepository;
use MintLMS\Infrastructure\Notification\EmailNotificationRegistrar;
use MintLMS\Infrastructure\PostType\PostTypeRegistrar;
use MintLMS\Infrastructure\Setup\Activator;
use MintLMS\Infrastructure\Setup\ContentMigrator;
use MintLMS\Infrastructure\Setup\PageSettings;
use MintLMS\Infrastructure\User\WpUserLookup;
use MintLMS\Infrastructure\WooCommerce\WooCommerceIntegration;

final class Bootstrap {

	public static function init(): void {
		register_activation_hook( MINTLMS_FILE, array( self::class, 'activate' ) );
		register_deactivation_hook( MINTLMS_FILE, array( self::class, 'deactivate' ) );

		add_action( 'plugins_loaded', array( self::class, 'onPluginsLoaded' ) );
	}

	public static function activate(): void {
		self::runMigrations();
		( new Activator() )->activate();
		set_transient( FirstRunRedirect::ACTIVATION_REDIRECT_TRANSIENT, true, 30 );
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	public static function onPluginsLoaded(): void {
		global $wpdb;

		self::runMigrations();

		( new PostTypeRegistrar() )->register();

		// Needs registered CPTs + WP_Rewrite (after init). plugins_loaded is too early for wp_insert_post.
		add_action(
			'init',
			static function (): void {
				global $wpdb;

				if ( $wpdb instanceof \wpdb ) {
					( new ContentMigrator( $wpdb ) )->maybeMigrate();
				}
			},
			20
		);

		Plugin::boot();

		( new MenuRegistrar() )->register();
		( new SettingsPage() )->register();
		( new CourseBuilderPage() )->register();
		( new CoursePostEditUi() )->register();
		( new LessonPostEditUi() )->register();
		( new QuizPostEditUi() )->register();
		( new QuestionPostEditUi() )->register();
		( new NativeEditorSaveGuard() )->register();
		( new FirstRunRedirect() )->register();
		( new AssetLoader() )->register();
		( new AdminAjaxHandler() )->register();
		( new StudentAssetLoader() )->register();

		if ( ! $wpdb instanceof \wpdb ) {
			return;
		}

		self::registerFrontend();
		WooCommerceIntegration::register();
		self::registerRestApi();
		self::registerNotifications( $wpdb );
		self::registerCertificateHandler( $wpdb );
	}

	private static function registerNotifications( \wpdb $wpdb ): void {
		$clock   = new SystemClock();
		$service = self::createCertificateService( $wpdb, $clock );

		( new EmailNotificationRegistrar(
			new WpPostCourseRepository(),
			$service,
			new WpdbEnrollmentRepository( $wpdb )
		) )->register();
	}

	private static function registerCertificateHandler( \wpdb $wpdb ): void {
		$clock   = new SystemClock();
		$service = self::createCertificateService( $wpdb, $clock );

		Plugin::registerCertificateService( $service );

		( new CertificateDownloadHandler( $service, new TemplateLoader() ) )->register();
	}

	private static function createCertificateService( \wpdb $wpdb, SystemClock $clock ): CertificateService {
		$userLookup = new WpUserLookup();

		return new CertificateService(
			new WpPostCourseRepository(),
			new WpdbProgressRepository( $wpdb ),
			$clock,
			new WpCertificateTemplateProvider(),
			new WpCertificateUrlBuilder(),
			$userLookup,
		);
	}

	private static function registerFrontend(): void {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			return;
		}

		$courseRepo     = new WpPostCourseRepository();
		$lessonRepo     = new WpPostLessonRepository();
		$sectionRepo    = new WpdbSectionRepository( $wpdb, $lessonRepo );
		$enrollmentRepo = new WpdbEnrollmentRepository( $wpdb );
		$progressRepo   = new WpdbProgressRepository( $wpdb );
		$quizRepo       = new WpPostQuizRepository( $wpdb );
		$clock          = new SystemClock();
		$authorization  = Plugin::authorization();
		$userLookup     = new WpUserLookup();
		$certificateService = self::createCertificateService( $wpdb, $clock );

		$enrollmentService = new EnrollmentService(
			$enrollmentRepo,
			$courseRepo,
			$authorization,
			$clock,
			Plugin::eventPublisher(),
			$progressRepo
		);

		$lessonAccessService = new LessonAccessService(
			$lessonRepo,
			$enrollmentRepo,
			$progressRepo,
			$clock,
			$courseRepo,
			$sectionRepo,
		);
		$quizService    = new QuizService( $quizRepo, $lessonRepo, $courseRepo, $authorization, $clock, $progressRepo, $userLookup, $lessonAccessService );

		$studentExperience = new StudentExperienceService(
			$courseRepo,
			$sectionRepo,
			$enrollmentRepo,
			$progressRepo,
			$clock,
			$quizService,
			$authorization,
			$lessonRepo,
		);

		$shortcodes = new ShortcodeRegistrar(
			$studentExperience,
			$enrollmentService,
			new TemplateLoader(),
			new PageSettings(),
			$certificateService,
		);

		$shortcodes->register();

		add_action( 'template_redirect', array( $shortcodes, 'handleEnrollRequest' ) );
	}

	private static function registerRestApi(): void {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			return;
		}

		$authorization     = Plugin::authorization();
		$clock             = new SystemClock();
		$courseRepo        = new WpPostCourseRepository();
		$lessonRepo        = new WpPostLessonRepository();
		$sectionRepo       = new WpdbSectionRepository( $wpdb, $lessonRepo );
		$enrollmentRepo    = new WpdbEnrollmentRepository( $wpdb );
		$progressRepo      = new WpdbProgressRepository( $wpdb );
		$quizRepo          = new WpPostQuizRepository( $wpdb );
		$dashboardRepo     = new WpdbAdminDashboardRepository( $wpdb );
		$userLookup        = new WpUserLookup();
		$courseService     = new CourseService( $courseRepo, $authorization, $clock, $dashboardRepo, $userLookup, Plugin::eventPublisher() );
		$sectionService    = new SectionService( $sectionRepo, $lessonRepo, $courseRepo, $authorization, $clock );
		$lessonAccessService = new LessonAccessService(
			$lessonRepo,
			$enrollmentRepo,
			$progressRepo,
			$clock,
			$courseRepo,
			$sectionRepo,
		);
		$quizService       = new QuizService( $quizRepo, $lessonRepo, $courseRepo, $authorization, $clock, $progressRepo, $userLookup, $lessonAccessService );
		$progressService   = new ProgressService( $progressRepo, Plugin::eventPublisher(), $clock, $quizService, $lessonAccessService, $courseRepo );
		$enrollmentService = new EnrollmentService(
			$enrollmentRepo,
			$courseRepo,
			$authorization,
			$clock,
			Plugin::eventPublisher(),
			$progressRepo
		);
		$lessonService     = new LessonService(
			$lessonRepo,
			$sectionRepo,
			$courseRepo,
			$authorization,
			$clock,
			$progressService,
			$quizService
		);
		$structureService  = new CourseStructureService( $courseRepo, $sectionRepo, $authorization );
		Plugin::registerCourseServices( $courseService, $structureService );
		Plugin::registerEnrollmentService( $enrollmentService );
		Plugin::registerProgressService( $progressService );
		$courseController     = new CourseController( $courseService, $structureService, $authorization );
		$sectionController    = new SectionController( $sectionService, $authorization );
		$lessonController     = new LessonController( $lessonService, $authorization );
		$enrollmentController = new EnrollmentController( $enrollmentService, $authorization );
		$progressController   = new ProgressController( $progressService, $authorization );
		$quizController       = new QuizController( $quizService, $authorization );
		$onboardingController = new OnboardingController( $authorization );
		$userController       = new UserController(
			new WpUserLookup(),
			$authorization,
		);
		$routeRegistrar       = new RouteRegistrar(
			$courseController,
			$sectionController,
			$lessonController,
			$enrollmentController,
			$progressController,
			$quizController,
			$onboardingController,
			$userController,
			new ContentPostListController(),
		);

		$routeRegistrar->register();
	}

	private static function runMigrations(): void {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			return;
		}

		$runner = new MigrationRunner(
			$wpdb,
			array(
				new Migration_001_InitialSchema(),
				new Migration_002_Quizzes(),
				new Migration_003_Drip(),
				new Migration_004_CertificateTemplate(),
				new Migration_005_CourseSettings(),
				new Migration_006_CptHybrid(),
				new Migration_007_MultiQuizPerLesson(),
				new Migration_008_CourseAccessSettings(),
			)
		);

		$runner->run();
	}
}

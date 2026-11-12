<?php
declare(strict_types=1);

namespace MintLMS\Frontend;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Certificate\CertificateService;
use MintLMS\Application\Enrollment\EnrollmentService;
use MintLMS\Application\Exception\ForbiddenException;
use MintLMS\Application\Exception\NotFoundException;
use MintLMS\Application\Exception\ValidationException;
use MintLMS\Application\Student\StudentExperienceService;
use MintLMS\Infrastructure\PostType\PostTypes;
use MintLMS\Infrastructure\Setup\PageSettings;
use MintLMS\Infrastructure\Ui\UiRoot;
use MintLMS\Infrastructure\WooCommerce\WooCommerceIntegration;

final class ShortcodeRegistrar {

	public function __construct(
		private StudentExperienceService $studentExperience,
		private EnrollmentService $enrollmentService,
		private TemplateLoader $templateLoader,
		private ?PageSettings $pageSettings = null,
		private ?CertificateService $certificateService = null,
	) {
	}

	public function register(): void {
		add_shortcode( 'mint_lms_dashboard', array( $this, 'renderDashboard' ) );
		add_shortcode( 'mint_lms_my_courses', array( $this, 'renderMyCourses' ) );
		add_shortcode( 'mint_lms_catalog', array( $this, 'renderCatalog' ) );
		add_shortcode( 'mint_lms_course', array( $this, 'renderCourseOverview' ) );
		add_shortcode( 'mint_lms_player', array( $this, 'renderPlayer' ) );
		add_shortcode( 'mint_lms_certificate', array( $this, 'renderCertificate' ) );
		add_filter( 'request', array( $this, 'mapLmsPageRequest' ) );
		add_action( 'template_redirect', array( $this, 'interceptBuilderPreview' ), 5 );
		add_action( 'template_redirect', array( $this, 'interceptStudentLessonCanvas' ), 5 );
		add_action( 'template_redirect', array( $this, 'interceptCourseOverviewChrome' ), 6 );
	}

	/**
	 * PATHINFO permalinks can match /index.php/courses/ with empty query vars,
	 * so WordPress serves the blog home instead of the catalog page.
	 *
	 * @param array<string, mixed> $queryVars Parsed request vars.
	 * @return array<string, mixed>
	 */
	public function mapLmsPageRequest( array $queryVars ): array {
		if ( $this->requestAlreadyResolvesPost( $queryVars ) ) {
			return $queryVars;
		}

		$pageId = $this->lmsPageIdForRequestPath();

		if ( $pageId <= 0 ) {
			$pageId = $this->lmsPageIdForStudentQuery();
		}

		if ( $pageId <= 0 ) {
			return $queryVars;
		}

		$queryVars['page_id'] = $pageId;
		unset( $queryVars['error'] );

		return $queryVars;
	}

	/**
	 * Course overview (?mintlms_course=) — keep theme "Courses" title visible,
	 * but tighten Kadence hero spacing so the large empty gap under the header shrinks.
	 */
	public function interceptCourseOverviewChrome(): void {
		$previewMode = isset( $_GET['mint_preview'] ) ? sanitize_key( (string) wp_unslash( $_GET['mint_preview'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( in_array( $previewMode, array( 'lesson', 'quiz', 'question' ), true ) ) {
			return;
		}

		$courseId = $this->queryInt( PageSettings::COURSE_QUERY_ARG );

		if ( $courseId <= 0 ) {
			return;
		}

		add_filter(
			'body_class',
			static function ( array $classes ): array {
				$classes[] = 'mint-lms-course-overview';

				return $classes;
			}
		);

		add_action(
			'wp_head',
			static function (): void {
				echo '<style id="mint-lms-course-overview-chrome">
					/* Keep page title visible; only compress oversized hero padding. */
					body.mint-lms-course-overview .entry-hero,
					body.mint-lms-course-overview .entry-hero-container-inner,
					body.mint-lms-course-overview .hero-container,
					body.mint-lms-course-overview .page-hero-section {
						min-height: 0 !important;
						padding-top: 18px !important;
						padding-bottom: 10px !important;
					}
					body.mint-lms-course-overview .entry-hero .entry-header,
					body.mint-lms-course-overview .entry-hero .page-title,
					body.mint-lms-course-overview .entry-hero .entry-title,
					body.mint-lms-course-overview .page-header .page-title {
						margin-top: 0 !important;
						margin-bottom: 0 !important;
					}
					body.mint-lms-course-overview .content-area,
					body.mint-lms-course-overview .entry-content-wrap,
					body.mint-lms-course-overview .entry.content-bg,
					body.mint-lms-course-overview .content-wrap,
					body.mint-lms-course-overview #primary,
					body.mint-lms-course-overview .site-main,
					body.mint-lms-course-overview .entry-content,
					body.mint-lms-course-overview .wp-block-post-content {
						padding-top: 8px !important;
						margin-top: 0 !important;
					}
					body.mint-lms-course-overview #mint-lms-root.mint-lms-student .mint-s3-wrap {
						padding-top: 12px;
					}
				</style>';
			},
			99
		);
	}

	/**
	 * Ensure builder Preview query args always win, even if the page shortcode path is odd.
	 */
	public function interceptBuilderPreview(): void {
		$mode = isset( $_GET['mint_preview'] ) ? sanitize_key( (string) wp_unslash( $_GET['mint_preview'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $mode, array( 'lesson', 'quiz', 'question' ), true ) ) {
			return;
		}

		$courseId   = $this->queryInt( PageSettings::COURSE_QUERY_ARG );
		$lessonId   = $this->queryInt( 'mint_lesson' );
		$questionId = $this->queryInt( 'mint_question' );
		$quizId     = $this->queryInt( 'mint_quiz' );
		if ( $courseId <= 0 ) {
			$courseId = $this->queryInt( 'mint_course' );
		}
		// Course is preferred, but lesson/quiz/question preview can run without one.
		if ( $courseId <= 0 && $lessonId <= 0 && $questionId <= 0 && $quizId <= 0 ) {
			return;
		}

		$html = $this->renderBuilderPreview( $courseId );
		if ( null === $html || '' === $html ) {
			return;
		}

		$this->sendStudentCanvas( $html, $mode );
	}

	/**
	 * Start course / lesson player links (?mint_course=&mint_lesson=) must use the
	 * same Mint LMS lesson canvas as builder preview — not the theme Course Player page.
	 */
	public function interceptStudentLessonCanvas(): void {
		$previewMode = isset( $_GET['mint_preview'] ) ? sanitize_key( (string) wp_unslash( $_GET['mint_preview'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( in_array( $previewMode, array( 'lesson', 'quiz', 'question' ), true ) ) {
			return;
		}

		$courseId = $this->queryInt( PageSettings::COURSE_QUERY_ARG );
		if ( $courseId <= 0 ) {
			$courseId = $this->queryInt( 'mint_course' );
		}

		$quizId = $this->queryInt( 'mint_quiz' );
		if ( $quizId > 0 ) {
			$html = $this->renderS3QuizHtml( $courseId, $this->queryInt( 'mint_lesson' ), $quizId );
			if ( '' === $html ) {
				return;
			}
			$this->sendStudentCanvas( $html, 'quiz' );

			return;
		}

		if ( $this->queryInt( 'mint_question' ) > 0 ) {
			return;
		}

		$lessonId = $this->queryInt( 'mint_lesson' );
		if ( $lessonId <= 0 || $courseId <= 0 ) {
			return;
		}

		$html = $this->renderS3LessonHtml( $courseId, $lessonId );
		if ( '' === $html ) {
			return;
		}

		$this->sendStudentCanvas( $html, 'lesson' );
	}

	/**
	 * Render preview without the theme header/footer (avoids blog chrome and raw Fusion CTA).
	 */
	private function sendStudentCanvas( string $html, string $mode ): void {
		status_header( 200 );
		nocache_headers();

		add_filter(
			'body_class',
			static function ( array $classes ) use ( $mode ): array {
				$classes[] = 'mint-lms-canvas';
				$classes[] = 'mint-lms-builder-preview';
				$classes[] = 'mint-lms-builder-preview--' . sanitize_html_class( $mode );

				return $classes;
			}
		);

		add_filter(
			'document_title_parts',
			static function ( array $parts ) use ( $mode ): array {
				$parts['title'] = 'lesson' === $mode
					? __( 'Lesson preview', 'mint-lms' )
					: __( 'Quiz preview', 'mint-lms' );

				return $parts;
			}
		);

		add_action( 'wp_footer', array( $this, 'startFooterCapture' ), 0 );
		add_action( 'wp_footer', array( $this, 'flushFooterCapture' ), PHP_INT_MAX );

		load_template(
			MINTLMS_PATH . 'templates/student/canvas.php',
			false,
			array(
				'content' => $html,
			)
		);
		exit;
	}

	public function startFooterCapture(): void {
		ob_start();
	}

	public function flushFooterCapture(): void {
		$buffer = ob_get_clean();
		if ( ! is_string( $buffer ) || '' === $buffer ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Footer scripts plus stripped third-party markup.
		echo $this->stripForeignFooterMarkup( $buffer );
	}

	private function stripForeignFooterMarkup( string $html ): string {
		$stripped = preg_replace( '/\[fusion_builder_container\b[\s\S]*?\[\/fusion_builder_container\]/i', '', $html );
		$stripped = is_string( $stripped ) ? $stripped : $html;
		$stripped = preg_replace( '/\[\/?fusion_[^\]]+\]/i', '', $stripped );

		return is_string( $stripped ) ? $stripped : $html;
	}

	/**
	 * @param array<string, string> $atts
	 */
	public function renderDashboard( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'player_page' => '',
			),
			$atts,
			'mint_lms_dashboard'
		);

		$userId = get_current_user_id();

		if ( $userId <= 0 ) {
			return $this->wrap(
				$this->templateLoader->render(
					'student/login-prompt.php',
					array(
						'message' => __( 'Log in to view your learning dashboard.', 'mint-lms' ),
					)
				)
			);
		}

		$courses = $this->studentExperience->getDashboardCourses( $userId );

		return $this->wrap(
			$this->templateLoader->render(
				'student/dashboard.php',
				array(
					'courses'   => $courses,
					'playerUrl' => $this->settings()->resolvePlayerUrl( $atts['player_page'] ),
				)
			)
		);
	}

	/**
	 * @param array<string, string> $atts
	 */
	public function renderMyCourses( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'player_page' => '',
			),
			$atts,
			'mint_lms_my_courses'
		);

		$userId = get_current_user_id();

		if ( $userId <= 0 ) {
			return $this->wrap(
				$this->templateLoader->render(
					'student/login-prompt.php',
					array(
						'message' => __( 'Log in to view your courses.', 'mint-lms' ),
					)
				)
			);
		}

		$filter = isset( $_GET['filter'] ) ? sanitize_key( (string) wp_unslash( $_GET['filter'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $filter, array( 'all', 'in_progress', 'completed' ), true ) ) {
			$filter = 'all';
		}

		$courses = $this->studentExperience->getMyCourses( $userId, $filter );

		return $this->wrap(
			$this->templateLoader->render(
				'student/my-courses.php',
				array(
					'courses'   => $courses,
					'filter'    => $filter,
					'playerUrl' => $this->settings()->resolvePlayerUrl( $atts['player_page'] ),
					'pageUrl'   => $this->currentPageUrl(),
				)
			)
		);
	}

	/**
	 * @param array<string, string> $atts
	 */
	public function renderCatalog( array $atts = array() ): string {
		$courseId = $this->queryInt( PageSettings::COURSE_QUERY_ARG );

		if ( $courseId > 0 ) {
			$builderPreview = $this->renderBuilderPreview( $courseId );
			if ( null !== $builderPreview ) {
				return $builderPreview;
			}

			return $this->renderCourseOverview(
				array(
					'id' => (string) $courseId,
				)
			);
		}

		$courses = $this->studentExperience->getPublishedCatalogCourses();
		$settings = $this->settings();

		return $this->wrap(
			$this->templateLoader->render(
				'student/catalog.php',
				array(
					'courses'   => $courses,
					'courseUrl' => static fn( int $id ): string => $settings->getCourseUrl( $id ),
				)
			)
		);
	}

	/**
	 * Instructor Preview from course builder (S3B / S3C / S3D).
	 */
	private function renderBuilderPreview( int $courseId ): ?string {
		$mode = isset( $_GET['mint_preview'] ) ? sanitize_key( (string) wp_unslash( $_GET['mint_preview'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $mode, array( 'lesson', 'quiz', 'question' ), true ) ) {
			return null;
		}

		$userId   = get_current_user_id();
		$lessonId = $this->queryInt( 'mint_lesson' );

		try {
			if ( 'lesson' === $mode ) {
				if ( $lessonId <= 0 ) {
					return $this->wrap( $this->errorMessage( __( 'Lesson is required for preview.', 'mint-lms' ) ) );
				}

				return $this->renderS3LessonHtml( $courseId, $lessonId );
			}

			$quizId = $this->queryInt( 'mint_quiz' );
			if ( $lessonId <= 0 && $quizId > 0 ) {
				$lessonId = $this->studentExperience->resolveLessonIdForQuizPreview( $quizId, $userId );
			}

			return $this->renderS3QuizHtml( $courseId, $lessonId, $quizId );
		} catch ( NotFoundException $exception ) {
			return $this->wrap( $this->errorMessage( __( 'Preview content not found.', 'mint-lms' ) ) );
		} catch ( ForbiddenException $exception ) {
			return $this->wrap( $this->errorMessage( __( 'You do not have permission to preview this content.', 'mint-lms' ) ) );
		}
	}

	private function renderS3LessonHtml( int $courseId, int $lessonId ): string {
		$userId = get_current_user_id();

		try {
			$preview = $this->studentExperience->getLessonBuilderPreview( $courseId, $lessonId, $userId );
		} catch ( NotFoundException $exception ) {
			return $this->wrap( $this->errorMessage( __( 'Lesson not found.', 'mint-lms' ) ) );
		} catch ( ForbiddenException $exception ) {
			$message = trim( $exception->getMessage() );

			return $this->wrap(
				$this->errorMessage(
					'' !== $message ? $message : __( 'You do not have permission to view this lesson.', 'mint-lms' )
				)
			);
		}

		$featuredId       = isset( $preview['featuredImageId'] ) ? (int) $preview['featuredImageId'] : 0;
		$settings         = $this->settings();
		$resolvedCourseId = $courseId > 0 ? $courseId : (int) ( $preview['courseId'] ?? 0 );
		$resolvedLessonId = (int) ( $preview['lessonId'] ?? $lessonId );
		$previousId       = (int) ( $preview['previousLessonId'] ?? 0 );
		$nextId           = (int) ( $preview['nextLessonId'] ?? 0 );
		$quizzes          = is_array( $preview['quizzes'] ?? null ) ? $preview['quizzes'] : array();
		$quizUrls         = array();
		$nextQuizUrl      = '';

		foreach ( $quizzes as $quizRow ) {
			$quizRowId = (int) ( $quizRow['id'] ?? 0 );
			if ( $quizRowId <= 0 ) {
				continue;
			}
			$quizUrls[ $quizRowId ] = $this->previewQuizUrl( $resolvedCourseId, $resolvedLessonId, $quizRowId );
			if ( '' === $nextQuizUrl ) {
				$nextQuizUrl = $quizUrls[ $quizRowId ];
			}
		}

		return $this->wrap(
			$this->templateLoader->render(
				'student/preview-lesson.php',
				array(
					'preview'       => $preview,
					'featuredUrl'   => $this->attachmentUrl( $featuredId > 0 ? $featuredId : null ) ?? '',
					'courseUrl'     => $resolvedCourseId > 0 ? $settings->getCourseUrl( $resolvedCourseId ) : '',
					'previousUrl'   => $previousId > 0 ? $this->previewLessonUrl( $resolvedCourseId, $previousId ) : '',
					'nextLessonUrl' => $nextId > 0 ? $this->previewLessonUrl( $resolvedCourseId, $nextId ) : '',
					'nextQuizUrl'   => $nextQuizUrl,
					'quizUrls'      => $quizUrls,
				)
			)
		);
	}

	private function renderS3QuizHtml( int $courseId, int $lessonId, int $quizId ): string {
		$userId = get_current_user_id();

		try {
			if ( $lessonId <= 0 && $quizId > 0 ) {
				$lessonId = $this->studentExperience->resolveLessonIdForQuizPreview( $quizId, $userId );
			}

			$quiz = $this->studentExperience->getQuizForBuilderLivePreview( $quizId, $lessonId, $userId );
		} catch ( NotFoundException $exception ) {
			return $this->wrap( $this->errorMessage( __( 'Quiz not found.', 'mint-lms' ) ) );
		} catch ( ForbiddenException $exception ) {
			$message = trim( $exception->getMessage() );

			return $this->wrap(
				$this->errorMessage(
					'' !== $message ? $message : __( 'You do not have permission to view this quiz.', 'mint-lms' )
				)
			);
		}

		$settings         = $this->settings();
		$resolvedCourseId = $courseId > 0 ? $courseId : (int) $quiz->courseId;
		$resolvedLessonId = $lessonId > 0 ? $lessonId : (int) $quiz->lessonId;
		if ( $resolvedLessonId > 0 && PostTypes::isDisposableHostLesson( $resolvedLessonId ) ) {
			$resolvedLessonId = 0;
		}
		$courseTitle = $this->publicPostTitle( $resolvedCourseId );
		$lessonTitle = $resolvedLessonId > 0 ? $this->publicPostTitle( $resolvedLessonId ) : '';

		return $this->wrap(
			$this->templateLoader->render(
				'student/preview-quiz.php',
				array(
					'quiz'        => $quiz,
					'courseUrl'   => $resolvedCourseId > 0 ? $settings->getCourseUrl( $resolvedCourseId ) : '',
					'lessonUrl'   => $resolvedLessonId > 0 ? $this->previewLessonUrl( $resolvedCourseId, $resolvedLessonId ) : '',
					'courseTitle' => $courseTitle,
					'lessonTitle' => $lessonTitle,
				)
			)
		);
	}

	private function publicPostTitle( int $postId ): string {
		if ( $postId <= 0 ) {
			return '';
		}

		$post = get_post( $postId );
		if ( ! $post instanceof \WP_Post ) {
			return '';
		}

		return trim( (string) $post->post_title );
	}

	/**
	 * @param array<string, string> $atts
	 */
	public function renderCourseOverview( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'id'             => '0',
				'player_page'    => '',
				'dashboard_page' => '',
			),
			$atts,
			'mint_lms_course'
		);

		$courseId = (int) $atts['id'];

		if ( $courseId <= 0 ) {
			$courseId = $this->queryInt( PageSettings::COURSE_QUERY_ARG );
		}

		if ( $courseId > 0 ) {
			$builderPreview = $this->renderBuilderPreview( $courseId );
			if ( null !== $builderPreview ) {
				return $builderPreview;
			}
		}

		$userId = get_current_user_id();

		if ( $courseId <= 0 ) {
			return $this->wrap(
				$this->errorMessage( __( 'Course ID is required.', 'mint-lms' ) )
			);
		}

		try {
			$overview = $this->studentExperience->getCourseOverview( $courseId, $userId );
		} catch ( NotFoundException $exception ) {
			return $this->wrap(
				$this->errorMessage( __( 'Course not found.', 'mint-lms' ) )
			);
		}

		$enrollMessage = '';

		if ( isset( $_GET['mint_enrolled'] ) && '1' === $_GET['mint_enrolled'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$enrollMessage = __( 'You are enrolled! Start learning below.', 'mint-lms' );
		}

		$settings = $this->settings();

		$purchaseUrl   = '';
		$productPrice  = '';
		$wc            = WooCommerceIntegration::instance();
		if ( null !== $wc && 'paid' === $overview->enrollmentType ) {
			$purchaseUrl  = $wc->productSync()->getPurchaseUrl( $courseId );
			$productId    = $wc->productSync()->findProductIdForCourse( $courseId );
			$productPrice = $productId > 0 ? $wc->productSync()->getProductPrice( $productId ) : '';
		}

		return $this->wrap(
			$this->templateLoader->render(
				'student/course-overview.php',
				array(
					'overview'      => $overview,
					'playerUrl'     => $settings->resolvePlayerUrl( $atts['player_page'] ),
					'dashboardUrl'  => $settings->resolveDashboardUrl( $atts['dashboard_page'] ),
					'catalogUrl'    => $settings->getCatalogUrl(),
					'enrollUrl'     => $this->enrollActionUrl( $courseId ),
					'enrollMessage' => $enrollMessage,
					'featuredUrl'   => $this->attachmentUrl( $overview->featuredImageId ),
					'purchaseUrl'   => $purchaseUrl,
					'productPrice'  => $productPrice,
				)
			)
		);
	}

	/**
	 * @param array<string, string> $atts
	 */
	public function renderPlayer( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'course'         => '0',
				'lesson'         => '0',
				'dashboard_page' => '',
				'overview_page'  => '',
			),
			$atts,
			'mint_lms_player'
		);

		$courseId = (int) $atts['course'];

		if ( $courseId <= 0 ) {
			$courseId = $this->queryInt( 'mint_course' );
		}

		// Admin "View" / legacy links sometimes use course_id or mintlms_course.
		if ( $courseId <= 0 ) {
			$courseId = $this->queryInt( 'course_id' );
		}

		if ( $courseId <= 0 ) {
			$courseId = $this->queryInt( PageSettings::COURSE_QUERY_ARG );
		}

		if ( $courseId > 0 ) {
			$builderPreview = $this->renderBuilderPreview( $courseId );
			if ( null !== $builderPreview ) {
				return $builderPreview;
			}
		}

		$lessonId = (int) $atts['lesson'];

		if ( $lessonId <= 0 ) {
			$lessonId = $this->queryInt( 'mint_lesson' );
		}

		$userId = get_current_user_id();

		if ( $courseId <= 0 ) {
			return $this->wrap(
				$this->errorMessage( __( 'Course ID is required.', 'mint-lms' ) )
			);
		}

		if ( $lessonId <= 0 ) {
			try {
				$overview = $this->studentExperience->getCourseOverview( $courseId, $userId );
				$lessonId = $overview->lastLessonId ?? $overview->firstLessonId ?? 0;
			} catch ( NotFoundException $exception ) {
				return $this->wrap(
					$this->errorMessage( __( 'Course not found.', 'mint-lms' ) )
				);
			}

			if ( $lessonId <= 0 ) {
				return $this->wrap(
					$this->errorMessage( __( 'This course has no lessons yet.', 'mint-lms' ) )
				);
			}
		}

		$settings = $this->settings();

		try {
			$context = $this->studentExperience->getPlayerContext( $courseId, $lessonId, $userId );
		} catch ( NotFoundException $exception ) {
			return $this->wrap(
				$this->errorMessage( __( 'Lesson not found.', 'mint-lms' ) )
			);
		} catch ( ForbiddenException $exception ) {
			return $this->wrap(
				$this->templateLoader->render(
					'student/gated-lesson.php',
					array(
						'message'     => $exception->getMessage(),
						'overviewUrl' => $this->overviewUrl( $courseId, $atts['overview_page'] ),
						'isLoggedIn'  => $userId > 0,
						'loginUrl'    => wp_login_url( $this->currentPageUrl() ),
					)
				)
			);
		}

		if ( $context->isCourseComplete ) {
			$certificateUrl = null;

			if ( null !== $this->certificateService && $userId > 0 && $this->certificateService->isEligible( $userId, $courseId ) ) {
				$certificateUrl = $this->certificateService->getCertificateUrl( $userId, $courseId );
			}

			return $this->wrap(
				$this->templateLoader->render(
					'student/completion.php',
					array(
						'context'        => $context,
						'dashboardUrl'   => $settings->resolveDashboardUrl( $atts['dashboard_page'] ),
						'overviewUrl'    => $this->overviewUrl( $courseId, $atts['overview_page'] ),
						'certificateUrl' => $certificateUrl,
					)
				)
			);
		}

		$attachmentUrl  = $this->attachmentUrl( $context->currentLesson->attachmentId );
		$lessonUrl      = fn( int $id ): string => $this->lessonUrl( $courseId, $id );
		$lessonSettings = $this->lessonPlayerSettings( (int) $context->currentLesson->id );

		return $this->wrap(
			$this->templateLoader->render(
				'student/player.php',
				array(
					'context'        => $context,
					'playerBaseUrl'  => $settings->resolvePlayerUrl( '' ),
					'dashboardUrl'   => $settings->resolveDashboardUrl( $atts['dashboard_page'] ),
					'lessonUrl'      => $lessonUrl,
					'attachmentUrl'  => $attachmentUrl,
					'videoEmbed'     => $this->videoEmbed( $this->lessonVideoUrl( $context->currentLesson->videoUrl, $lessonSettings ), $lessonSettings ),
					'lessonSettings' => $lessonSettings,
				)
			)
		);
	}

	/**
	 * @param array<string, string> $atts
	 */
	public function renderCertificate( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'course' => '0',
			),
			$atts,
			'mint_lms_certificate'
		);

		$courseId = (int) $atts['course'];
		$userId   = get_current_user_id();

		if ( $userId <= 0 ) {
			return $this->wrap(
				$this->templateLoader->render(
					'student/login-prompt.php',
					array(
						'message' => __( 'Log in to view your certificate.', 'mint-lms' ),
					)
				)
			);
		}

		if ( $courseId <= 0 ) {
			return $this->wrap(
				$this->errorMessage( __( 'Course ID is required.', 'mint-lms' ) )
			);
		}

		if ( null === $this->certificateService || ! $this->certificateService->isEligible( $userId, $courseId ) ) {
			return $this->wrap(
				$this->errorMessage( __( 'Certificate is not available yet.', 'mint-lms' ) )
			);
		}

		$html = $this->certificateService->generateCertificate( $userId, $courseId );
		$url  = $this->certificateService->getCertificateUrl( $userId, $courseId );

		return $this->wrap(
			$this->templateLoader->render(
				'student/certificate.php',
				array(
					'certificateHtml' => $html,
					'downloadUrl'     => $url,
				)
			)
		);
	}

	public function handleEnrollRequest(): void {
		if ( ! isset( $_GET['mint_lms_enroll'], $_GET['course_id'], $_GET['_wpnonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$courseId = (int) $_GET['course_id']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_GET['_wpnonce'] ) ), 'mint_lms_enroll_' . $courseId ) ) {
			return;
		}

		$userId = get_current_user_id();

		if ( $userId <= 0 ) {
			wp_safe_redirect( wp_login_url( $this->refererOrHome() ) );
			exit;
		}

		$redirect = $this->refererOrHome();

		try {
			$this->enrollmentService->enroll( $userId, $courseId, $userId );
			$redirect = add_query_arg( 'mint_enrolled', '1', $redirect );
		} catch ( ValidationException | ForbiddenException | NotFoundException $exception ) {
			$redirect = add_query_arg( 'mint_enroll_error', '1', $redirect );
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	private function settings(): PageSettings {
		if ( null === $this->pageSettings ) {
			$this->pageSettings = new PageSettings();
		}

		return $this->pageSettings;
	}

	private function wrap( string $content ): string {
		return UiRoot::open( 'student' ) . $content . UiRoot::close();
	}

	private function renderLessonPlayerPreview( int $courseId, int $lessonId, int $userId ): ?string {
		try {
			$context = $this->studentExperience->getPlayerContext( $courseId, $lessonId, $userId );
		} catch ( NotFoundException | ForbiddenException $exception ) {
			return null;
		}

		$settings       = $this->settings();
		$attachmentUrl  = $this->attachmentUrl( $context->currentLesson->attachmentId );
		$isReview       = ! empty( $context->isReviewMode );
		$lessonUrl      = $isReview
			? fn( int $id ): string => $this->previewLessonUrl( $courseId, $id )
			: fn( int $id ): string => $this->lessonUrl( $courseId, $id );
		$quizPreviewUrl = '';
		if ( $isReview && null !== $context->quiz ) {
			$quizPreviewUrl = $this->previewQuizUrl( $courseId, $lessonId, (int) $context->quiz->id );
		}

		$lessonSettings = $this->lessonPlayerSettings( (int) $context->currentLesson->id );

		return $this->wrap(
			$this->templateLoader->render(
				'student/player.php',
				array(
					'context'        => $context,
					'playerBaseUrl'  => $settings->resolvePlayerUrl( '' ),
					'dashboardUrl'   => $settings->resolveDashboardUrl( '' ),
					'lessonUrl'      => $lessonUrl,
					'attachmentUrl'  => $attachmentUrl,
					'videoEmbed'     => $this->videoEmbed( $this->lessonVideoUrl( $context->currentLesson->videoUrl, $lessonSettings ), $lessonSettings ),
					'quizPreviewUrl' => $quizPreviewUrl,
					'lessonSettings' => $lessonSettings,
				)
			)
		);
	}

	private function errorMessage( string $message ): string {
		return $this->templateLoader->render(
			'student/error.php',
			array( 'message' => $message )
		);
	}

	private function currentPageUrl(): string {
		$permalink = get_permalink();

		if ( is_string( $permalink ) && '' !== $permalink ) {
			return $permalink;
		}

		return home_url( '/' );
	}

	private function refererOrHome(): string {
		$referer = wp_get_referer();

		if ( is_string( $referer ) && '' !== $referer ) {
			return $referer;
		}

		return home_url( '/' );
	}

	/**
	 * @param array<string, mixed> $queryVars Parsed request vars.
	 */
	private function requestAlreadyResolvesPost( array $queryVars ): bool {
		foreach ( array( 'page_id', 'pagename', 'p', 'name' ) as $key ) {
			if ( ! empty( $queryVars[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	private function lmsPageIdForRequestPath(): int {
		$path = $this->frontRequestPath();

		if ( '' === $path ) {
			return 0;
		}

		foreach ( $this->lmsFrontPageIdsBySlug() as $slug => $pageId ) {
			if ( $path === $slug || str_ends_with( $path, '/' . $slug ) ) {
				return $pageId;
			}
		}

		return 0;
	}

	private function lmsPageIdForStudentQuery(): int {
		$previewMode  = isset( $_GET['mint_preview'] ) ? sanitize_key( (string) wp_unslash( $_GET['mint_preview'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$playerCourse = $this->queryInt( 'mint_course' );
		$playerLesson = $this->queryInt( 'mint_lesson' );

		if ( in_array( $previewMode, array( 'lesson', 'quiz', 'question' ), true ) || $playerCourse > 0 || $playerLesson > 0 ) {
			return $this->settings()->getPlayerPageId();
		}

		if ( $this->queryInt( PageSettings::COURSE_QUERY_ARG ) > 0 ) {
			return $this->settings()->getCatalogPageId();
		}

		return 0;
	}

	/**
	 * @return array<string, int>
	 */
	private function lmsFrontPageIdsBySlug(): array {
		$map = array();

		foreach ( array(
			$this->settings()->getCatalogPageId(),
			$this->settings()->getPlayerPageId(),
			$this->settings()->getDashboardPageId(),
		) as $pageId ) {
			if ( $pageId <= 0 ) {
				continue;
			}

			$post = get_post( $pageId );
			if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type ) {
				continue;
			}

			$slug = strtolower( trim( (string) $post->post_name, '/' ) );
			$uri  = strtolower( trim( (string) get_page_uri( $pageId ), '/' ) );

			if ( '' !== $slug ) {
				$map[ $slug ] = $pageId;
			}
			if ( '' !== $uri ) {
				$map[ $uri ] = $pageId;
			}
		}

		return $map;
	}

	private function frontRequestPath(): string {
		$path = '';

		if ( isset( $_SERVER['REQUEST_URI'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Path only, sanitized below.
			$raw  = esc_url_raw( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) );
			$part = wp_parse_url( $raw, PHP_URL_PATH );
			$path = is_string( $part ) ? $part : '';
		}

		$path = strtolower( trim( $path, '/' ) );

		if ( str_starts_with( $path, 'index.php/' ) ) {
			$path = substr( $path, strlen( 'index.php/' ) );
		} elseif ( 'index.php' === $path ) {
			$path = '';
		}

		return trim( $path, '/' );
	}

	private function queryInt( string $key ): int {
		if ( ! isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return 0;
		}

		return absint( wp_unslash( (string) $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	private function overviewUrl( int $courseId, string $pageIdOverride ): string {
		if ( '' !== $pageIdOverride ) {
			$base = get_permalink( (int) $pageIdOverride );

			if ( is_string( $base ) && '' !== $base ) {
				return add_query_arg( PageSettings::COURSE_QUERY_ARG, (string) $courseId, $base );
			}
		}

		return $this->settings()->getCourseUrl( $courseId );
	}

	private function lessonUrl( int $courseId, int $lessonId ): string {
		$base = $this->settings()->resolvePlayerUrl( '' );

		return add_query_arg(
			array(
				'mint_course' => (string) $courseId,
				'mint_lesson' => (string) $lessonId,
			),
			$base
		);
	}

	private function previewLessonUrl( int $courseId, int $lessonId ): string {
		$args = array(
			'mint_preview' => 'lesson',
			'mint_lesson'  => (string) $lessonId,
		);
		if ( $courseId > 0 ) {
			$args['mintlms_course'] = (string) $courseId;
			$args['mint_course']    = (string) $courseId;
		}

		return add_query_arg( $args, $this->settings()->resolvePlayerUrl( '' ) );
	}

	private function previewQuizUrl( int $courseId, int $lessonId, int $quizId ): string {
		$args = array(
			'mint_preview' => 'quiz',
			'mint_quiz'    => (string) $quizId,
		);
		if ( $lessonId > 0 ) {
			$args['mint_lesson'] = (string) $lessonId;
		}
		if ( $courseId > 0 ) {
			$args['mintlms_course'] = (string) $courseId;
			$args['mint_course']    = (string) $courseId;
		}

		return add_query_arg( $args, $this->settings()->resolvePlayerUrl( '' ) );
	}

	private function enrollActionUrl( int $courseId ): string {
		return wp_nonce_url(
			add_query_arg(
				array(
					'mint_lms_enroll' => '1',
					'course_id'       => (string) $courseId,
				),
				$this->currentPageUrl()
			),
			'mint_lms_enroll_' . $courseId
		);
	}

	private function attachmentUrl( ?int $attachmentId ): ?string {
		if ( null === $attachmentId || $attachmentId <= 0 ) {
			return null;
		}

		$url = wp_get_attachment_url( $attachmentId );

		return is_string( $url ) ? $url : null;
	}

	/**
	 * @param array<string, mixed> $settings
	 */
	private function lessonVideoUrl( ?string $lessonVideoUrl, array $settings ): ?string {
		if ( is_string( $lessonVideoUrl ) && '' !== $lessonVideoUrl ) {
			return $lessonVideoUrl;
		}

		$fromSettings = isset( $settings['videoUrl'] ) ? (string) $settings['videoUrl'] : '';

		return '' !== $fromSettings ? $fromSettings : null;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function lessonPlayerSettings( int $lessonId ): array {
		$raw = $lessonId > 0 ? get_post_meta( $lessonId, PostTypes::META_LESSON_SETTINGS, true ) : array();
		$raw = is_array( $raw ) ? $raw : array();

		return array(
			'materialsEnabled' => ! empty( $raw['materialsEnabled'] ),
			'materialsContent' => wp_kses_post( (string) ( $raw['materialsContent'] ?? '' ) ),
			'videoProgression' => ! empty( $raw['videoProgression'] ),
			'videoUrl'         => sanitize_text_field( (string) ( $raw['videoUrl'] ?? '' ) ),
			'videoTiming'      => ( $raw['videoTiming'] ?? 'before' ) === 'after' ? 'after' : 'before',
			'videoAutoplay'    => ! empty( $raw['videoAutoplay'] ),
			'videoControls'    => ! empty( $raw['videoControls'] ),
		);
	}

	/**
	 * @param array<string, mixed> $settings
	 */
	private function videoEmbed( ?string $videoUrl, array $settings = array() ): string {
		if ( null === $videoUrl || '' === $videoUrl ) {
			return '';
		}

		$controls = ! empty( $settings['videoControls'] );
		$autoplay = ! empty( $settings['videoAutoplay'] );

		$embed = wp_oembed_get( $videoUrl );

		if ( is_string( $embed ) && '' !== $embed ) {
			return $embed;
		}

		$attrs = $controls ? ' controls' : '';
		if ( $autoplay ) {
			$attrs .= ' autoplay playsinline';
		}

		return sprintf(
			'<video class="mint-w-full mint-rounded-lg" %s src="%s"%s></video>',
			trim( $attrs ),
			esc_url( $videoUrl ),
			! empty( $settings['videoProgression'] ) ? ' x-on:ended="videoWatched = true"' : ''
		);
	}
}

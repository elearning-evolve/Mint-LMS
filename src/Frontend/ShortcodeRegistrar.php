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
		add_action( 'template_redirect', array( $this, 'interceptBuilderPreview' ), 5 );
		add_action( 'template_redirect', array( $this, 'interceptCourseOverviewChrome' ), 6 );
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

		// Lesson/quiz preview: keep theme page title visible ("Lessons" / "Quizzes").
		// Question preview: hide theme title to avoid competing chrome.
		add_filter(
			'body_class',
			static function ( array $classes ) use ( $mode ): array {
				$classes[] = 'mint-lms-builder-preview';
				$classes[] = 'mint-lms-builder-preview--' . $mode;

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

		if ( 'lesson' === $mode || 'quiz' === $mode || 'question' === $mode ) {
			$pageHeading = 'lesson' === $mode ? __( 'Lessons', 'mint-lms' ) : __( 'Quizzes', 'mint-lms' );
			add_filter(
				'the_title',
				static function ( $title, $postId = 0 ) use ( $pageHeading ) {
					if ( is_admin() ) {
						return $title;
					}

					$queriedId = (int) get_queried_object_id();
					if ( $queriedId > 0 && (int) $postId === $queriedId ) {
						return $pageHeading;
					}

					if ( 0 === (int) $postId && in_the_loop() && is_main_query() ) {
						return $pageHeading;
					}

					return $title;
				},
				20,
				2
			);
		}

		// Replace after shortcodes/autop so theme catalog markup cannot leak through.
		remove_filter( 'the_content', 'wpautop' );
		add_filter(
			'the_content',
			static function () use ( $html ): string {
				return $html;
			},
			999
		);

		add_action(
			'wp_head',
			static function () use ( $mode ): void {
				if ( 'lesson' === $mode || 'quiz' === $mode || 'question' === $mode ) {
					$mod = sanitize_html_class( $mode );
					echo '<style id="mint-lms-builder-preview-chrome">
						body.mint-lms-builder-preview--' . esc_attr( $mod ) . ' .entry-hero,
						body.mint-lms-builder-preview--' . esc_attr( $mod ) . ' .entry-hero-container-inner,
						body.mint-lms-builder-preview--' . esc_attr( $mod ) . ' .hero-container {
							min-height: 0 !important;
							padding-top: 18px !important;
							padding-bottom: 10px !important;
						}
						body.mint-lms-builder-preview--' . esc_attr( $mod ) . ' #mint-lms-root.mint-lms-student .mint-s3b-wrap {
							padding-top: 12px;
						}
						body.mint-lms-builder-preview--' . esc_attr( $mod ) . ' #mint-lms-root.mint-lms-student .mint-s3-hero {
							margin-bottom: 0;
						}
					</style>';
					return;
				}

				echo '<style id="mint-lms-builder-preview-chrome">
					body.mint-lms-builder-preview .entry-title,
					body.mint-lms-builder-preview .page-title,
					body.mint-lms-builder-preview .wp-block-post-title,
					body.mint-lms-builder-preview h1.entry-title,
					body.mint-lms-builder-preview .page-header .page-title {
						display: none !important;
					}
				</style>';
			},
			99
		);
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

				if ( $courseId > 0 ) {
					$player = $this->renderLessonPlayerPreview( $courseId, $lessonId, $userId );
					if ( null !== $player ) {
						return $player;
					}
				}

				$preview = $this->studentExperience->getLessonBuilderPreview( $courseId, $lessonId, $userId );
				$featuredId = isset( $preview['featuredImageId'] ) ? (int) $preview['featuredImageId'] : 0;
				$settings   = $this->settings();
				$nextId     = isset( $preview['nextLessonId'] ) ? (int) $preview['nextLessonId'] : 0;
				$resolvedCourseId = $courseId > 0 ? $courseId : (int) ( $preview['courseId'] ?? 0 );
				$playerUrl  = $settings->getPlayerUrl();
				$nextUrl    = $nextId > 0 && $resolvedCourseId > 0
					? add_query_arg(
						array(
							'mint_course' => (string) $resolvedCourseId,
							'mint_lesson' => (string) $nextId,
						),
						$playerUrl
					)
					: '';

				return $this->wrap(
					$this->templateLoader->render(
						'student/preview-lesson.php',
						array(
							'preview'     => $preview,
							'featuredUrl' => $this->attachmentUrl( $featuredId > 0 ? $featuredId : null ) ?? '',
							'courseUrl'   => $resolvedCourseId > 0 ? $settings->getCourseUrl( $resolvedCourseId ) : '',
							'nextUrl'     => $nextUrl,
						)
					)
				);
			}

			$quizId = $this->queryInt( 'mint_quiz' );
			if ( $lessonId <= 0 && $quizId > 0 ) {
				$lessonId = $this->studentExperience->resolveLessonIdForQuizPreview( $quizId, $userId );
			}

			$quiz = $this->studentExperience->getQuizForBuilderLivePreview( $quizId, $lessonId, $userId );
			$settings = $this->settings();
			$resolvedCourseId = $courseId > 0 ? $courseId : (int) $quiz->courseId;

			return $this->wrap(
				$this->templateLoader->render(
					'student/preview-quiz.php',
					array(
						'quiz'      => $quiz,
						'courseUrl' => $resolvedCourseId > 0 ? $settings->getCourseUrl( $resolvedCourseId ) : '',
					)
				)
			);
		} catch ( NotFoundException $exception ) {
			return $this->wrap( $this->errorMessage( __( 'Preview content not found.', 'mint-lms' ) ) );
		} catch ( ForbiddenException $exception ) {
			return $this->wrap( $this->errorMessage( __( 'You do not have permission to preview this content.', 'mint-lms' ) ) );
		}
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

		$attachmentUrl = $this->attachmentUrl( $context->currentLesson->attachmentId );
		$lessonUrl     = fn( int $id ): string => $this->lessonUrl( $courseId, $id );

		return $this->wrap(
			$this->templateLoader->render(
				'student/player.php',
				array(
					'context'       => $context,
					'playerBaseUrl' => $settings->resolvePlayerUrl( '' ),
					'dashboardUrl'  => $settings->resolveDashboardUrl( $atts['dashboard_page'] ),
					'lessonUrl'     => $lessonUrl,
					'attachmentUrl' => $attachmentUrl,
					'videoEmbed'    => $this->videoEmbed( $context->currentLesson->videoUrl ),
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

		$settings      = $this->settings();
		$attachmentUrl = $this->attachmentUrl( $context->currentLesson->attachmentId );
		$lessonUrl     = fn( int $id ): string => $this->lessonUrl( $courseId, $id );

		return $this->wrap(
			$this->templateLoader->render(
				'student/player.php',
				array(
					'context'       => $context,
					'playerBaseUrl' => $settings->resolvePlayerUrl( '' ),
					'dashboardUrl'  => $settings->resolveDashboardUrl( '' ),
					'lessonUrl'     => $lessonUrl,
					'attachmentUrl' => $attachmentUrl,
					'videoEmbed'    => $this->videoEmbed( $context->currentLesson->videoUrl ),
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

	private function videoEmbed( ?string $videoUrl ): string {
		if ( null === $videoUrl || '' === $videoUrl ) {
			return '';
		}

		$embed = wp_oembed_get( $videoUrl );

		if ( is_string( $embed ) && '' !== $embed ) {
			return $embed;
		}

		return sprintf(
			'<video class="mint-w-full mint-rounded-lg" controls src="%s"></video>',
			esc_url( $videoUrl )
		);
	}
}

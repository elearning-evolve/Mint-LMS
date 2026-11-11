<?php
declare(strict_types=1);

namespace MintLMS\Frontend;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Setup\PageSettings;

final class StudentAssetLoader {

	private const ENQUEUE_PRIORITY = 999;

	/** @var list<string> */
	private const SHORTCODES = array(
		'mint_lms_dashboard',
		'mint_lms_my_courses',
		'mint_lms_catalog',
		'mint_lms_course',
		'mint_lms_player',
		'mint_lms_certificate',
	);

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueStudentAssets' ), self::ENQUEUE_PRIORITY );
	}

	public function enqueueStudentAssets(): void {
		if ( ! $this->shouldEnqueueStudentAssets() ) {
			return;
		}

		wp_enqueue_style(
			'mint-lms-student',
			MINTLMS_URL . 'assets/dist/student.css',
			array(),
			MINTLMS_VERSION
		);

		wp_add_inline_style(
			'mint-lms-student',
			'#mint-lms-root.mint-lms-student{isolation:isolate;position:relative;z-index:0}'
		);

		wp_enqueue_script(
			'mint-lms-student',
			MINTLMS_URL . 'assets/dist/student.js',
			array(),
			MINTLMS_VERSION,
			true
		);

		wp_localize_script(
			'mint-lms-student',
			'mintLmsStudent',
			array(
				'restUrl' => esc_url_raw( rest_url( 'mintlms/v1' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
					'markComplete'   => __( 'Mark complete', 'mint-lms' ),
					'completed'      => __( 'Completed', 'mint-lms' ),
					'marking'        => __( 'Saving…', 'mint-lms' ),
					'completeError'  => __( 'Could not mark lesson complete.', 'mint-lms' ),
					'courseComplete' => __( 'Course complete!', 'mint-lms' ),
					'quizRequired'   => __( 'Pass the quiz before marking this lesson complete.', 'mint-lms' ),
					'submitQuiz'     => __( 'Submit quiz', 'mint-lms' ),
					'submittingQuiz' => __( 'Submitting…', 'mint-lms' ),
					'quizPassed'     => __( 'Quiz passed! You can now mark this lesson complete.', 'mint-lms' ),
					'quizPreviewPassed' => __( 'Preview passed — this attempt was not saved.', 'mint-lms' ),
					'quizPreviewNotSaved' => __( 'Preview only. Results are not recorded.', 'mint-lms' ),
					'quizFailed'     => __( 'Quiz not passed.', 'mint-lms' ),
					'quizRetry'      => __( 'Review the lesson and try again.', 'mint-lms' ),
					'quizSubmitError' => __( 'Could not submit quiz.', 'mint-lms' ),
					'quizPending'    => __( 'Submitted — essay answers need grading.', 'mint-lms' ),
					'quizEssayPending' => __( 'Auto-graded score. Essays are pending review.', 'mint-lms' ),
					'answerRequired' => __( 'Answer this question before continuing.', 'mint-lms' ),
					'correct'        => __( 'Correct', 'mint-lms' ),
					'incorrect'      => __( 'Incorrect', 'mint-lms' ),
					'pending'        => __( 'Pending', 'mint-lms' ),
				),
			)
		);
	}

	private function shouldEnqueueStudentAssets(): bool {
		if ( is_admin() ) {
			return false;
		}

		$previewMode = isset( $_GET['mint_preview'] ) ? sanitize_key( (string) wp_unslash( $_GET['mint_preview'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $previewMode, array( 'lesson', 'quiz', 'question' ), true ) ) {
			return true;
		}

		if ( isset( $_GET['mint_lesson'] ) || isset( $_GET['mint_quiz'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		$post = get_post();

		if ( null === $post ) {
			return false;
		}

		$pageSettings = new PageSettings();
		$mintPageIds  = array_filter(
			array(
				$pageSettings->getDashboardPageId(),
				$pageSettings->getCatalogPageId(),
				$pageSettings->getPlayerPageId(),
			)
		);

		if ( in_array( (int) $post->ID, $mintPageIds, true ) ) {
			return true;
		}

		$content = (string) $post->post_content;

		foreach ( self::SHORTCODES as $shortcode ) {
			if ( has_shortcode( $content, $shortcode ) ) {
				return true;
			}
		}

		return str_contains( $content, 'mint_lms_' );
	}
}

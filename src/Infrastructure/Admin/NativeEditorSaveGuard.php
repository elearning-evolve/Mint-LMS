<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\PostType\PostTypes;

/**
 * LearnDash-style native editor: persist only on Save draft / Publish.
 *
 * Gutenberg otherwise autosaves auto-drafts into real drafts (they then appear in lists).
 */
final class NativeEditorSaveGuard {

	public function register(): void {
		add_action( 'init', array( $this, 'disableAutosaveSupport' ), 20 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueueBlockEditorGuard' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'dequeueClassicAutosave' ) );
		add_filter( 'block_editor_settings_all', array( $this, 'slowAutosaveInterval' ), 10, 2 );
		add_action( 'wp_ajax_mintlms_discard_auto_draft', array( $this, 'discardAutoDraft' ) );
	}

	public function disableAutosaveSupport(): void {
		foreach ( PostTypes::all() as $postType ) {
			remove_post_type_support( $postType, 'autosave' );
		}
	}

	/**
	 * @param array<string, mixed> $settings Block editor settings.
	 * @param mixed                $context  Editor context.
	 * @return array<string, mixed>
	 */
	public function slowAutosaveInterval( array $settings, $context ): array {
		$post = is_object( $context ) && isset( $context->post ) ? $context->post : null;

		if ( $post instanceof \WP_Post && PostTypes::isContentType( $post->post_type ) ) {
			$settings['autosaveInterval'] = YEAR_IN_SECONDS;
		}

		return $settings;
	}

	public function enqueueBlockEditorGuard(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( null === $screen || ! PostTypes::isContentType( (string) $screen->post_type ) ) {
			return;
		}

		$post     = isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof \WP_Post ? $GLOBALS['post'] : null;
		$postId   = $post instanceof \WP_Post ? (int) $post->ID : 0;
		$status   = $post instanceof \WP_Post ? (string) $post->post_status : 'auto-draft';
		$postType = (string) $screen->post_type;

		wp_enqueue_script(
			'mintlms-native-editor-save-guard',
			MINTLMS_URL . 'assets/js/native-editor-save-guard.js',
			array( 'wp-data', 'wp-dom-ready' ),
			MINTLMS_VERSION,
			true
		);

		wp_localize_script(
			'mintlms-native-editor-save-guard',
			'mintLmsNativeEditor',
			array(
				'postId'       => $postId,
				'status'       => $status,
				'postType'     => $postType,
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'discardNonce' => wp_create_nonce( 'mintlms_discard_auto_draft' ),
				'builderUrls'  => array(
					PostTypes::COURSE   => admin_url( 'admin.php?page=mint-lms-builder&course_id=__ID__' ),
					PostTypes::LESSON   => admin_url( 'admin.php?page=mint-lms-lesson-edit&lesson_id=__ID__&from=lessons' ),
					PostTypes::QUIZ     => admin_url( 'admin.php?page=mint-lms-edit-quiz&quiz_id=__ID__' ),
					PostTypes::QUESTION => admin_url( 'admin.php?page=mint-lms-edit-quiz&question_id=__ID__' ),
				),
			)
		);
	}

	public function dequeueClassicAutosave( string $hookSuffix ): void {
		if ( ! in_array( $hookSuffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( null === $screen || ! PostTypes::isContentType( (string) $screen->post_type ) ) {
			return;
		}

		wp_dequeue_script( 'autosave' );
		wp_deregister_script( 'autosave' );
	}

	public function discardAutoDraft(): void {
		if ( ! check_ajax_referer( 'mintlms_discard_auto_draft', 'nonce', false ) ) {
			wp_die( '', '', array( 'response' => 403 ) );
		}

		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_die( '', '', array( 'response' => 403 ) );
		}

		$postId = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
		$post   = $postId > 0 ? get_post( $postId ) : null;

		if ( ! $post instanceof \WP_Post || ! PostTypes::isContentType( $post->post_type ) ) {
			wp_die( '', '', array( 'response' => 404 ) );
		}

		if ( 'auto-draft' !== $post->post_status ) {
			wp_die( '', '', array( 'response' => 200 ) );
		}

		if ( ! current_user_can( 'delete_post', $postId ) ) {
			wp_die( '', '', array( 'response' => 403 ) );
		}

		wp_delete_post( $postId, true );
		wp_die( '', '', array( 'response' => 200 ) );
	}
}

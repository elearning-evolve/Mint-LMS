<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Capability;

defined( 'ABSPATH' ) || exit;

/**
 * Students stay on the frontend. Instructors, managers, and administrators keep wp-admin.
 */
final class AdminAccessGuard {

	public function register(): void {
		add_action( 'admin_init', array( $this, 'blockStudentWpAdmin' ) );
		add_filter( 'show_admin_bar', array( $this, 'hideAdminBarForStudents' ) );
	}

	public function blockStudentWpAdmin(): void {
		if ( ! is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( ! $this->isFrontendStudent() ) {
			return;
		}

		global $pagenow;
		$page = is_string( $pagenow ) ? $pagenow : '';
		if ( in_array( $page, array( 'admin-ajax.php', 'admin-post.php', 'async-upload.php' ), true ) ) {
			return;
		}

		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	/**
	 * @param bool $show
	 */
	public function hideAdminBarForStudents( $show ): bool {
		if ( $this->isFrontendStudent() ) {
			return false;
		}

		return (bool) $show;
	}

	private function isFrontendStudent(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( current_user_can( 'manage_options' ) || current_user_can( Roles::CAP_EDIT ) ) {
			return false;
		}

		return current_user_can( Roles::CAP_TAKE );
	}
}

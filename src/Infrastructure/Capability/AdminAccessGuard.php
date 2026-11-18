<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Capability;

defined( 'ABSPATH' ) || exit;

/**
 * Students stay on the frontend. Instructors, managers, and administrators keep wp-admin.
 *
 * WooCommerce blocks anyone without edit_posts / manage_woocommerce from wp-admin and
 * sends them to My Account — same issue LearnDash solves with woocommerce_prevent_admin_access.
 */
final class AdminAccessGuard {

	public function register(): void {
		add_action( 'admin_init', array( $this, 'blockStudentWpAdmin' ) );
		add_filter( 'show_admin_bar', array( $this, 'hideAdminBarForStudents' ) );
		add_filter( 'woocommerce_prevent_admin_access', array( $this, 'allowMintStaffWpAdmin' ), 20 );
		add_filter( 'woocommerce_disable_admin_bar', array( $this, 'allowMintStaffAdminBar' ), 20 );
		// After WP marks post.php / post-new.php nopriv for users without edit_posts.
		add_action( 'admin_menu', array( $this, 'allowNativeMintEditorScreens' ), 9999 );
	}

	/**
	 * Instructors/Managers have edit_mintlms_courses but not edit_posts.
	 * Core then puts post.php / post-new.php / edit.php in $_wp_menu_nopriv and
	 * user_can_access_admin_page() dies with "not allowed to access this page"
	 * before CPT create/edit caps run. Clear those flags for Mint staff so the
	 * native Course (and Lesson/Quiz/Question) Gutenberg screens work.
	 */
	public function allowNativeMintEditorScreens(): void {
		if ( ! $this->isMintStaff() ) {
			return;
		}

		global $_wp_menu_nopriv, $_wp_submenu_nopriv;

		foreach ( array( 'post.php', 'post-new.php', 'edit.php' ) as $screen ) {
			unset( $_wp_menu_nopriv[ $screen ] );
		}

		if ( ! is_array( $_wp_submenu_nopriv ) ) {
			return;
		}

		foreach ( $_wp_submenu_nopriv as $parent => $items ) {
			if ( ! is_array( $items ) ) {
				continue;
			}
			foreach ( array( 'post.php', 'post-new.php', 'edit.php' ) as $screen ) {
				unset( $_wp_submenu_nopriv[ $parent ][ $screen ] );
			}
		}
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
	 * @param bool $show Whether to show the admin bar.
	 */
	public function hideAdminBarForStudents( $show ): bool {
		if ( $this->isFrontendStudent() ) {
			return false;
		}

		return (bool) $show;
	}

	/**
	 * Let Mint Instructor / Manager into wp-admin when WooCommerce would block them.
	 *
	 * @param bool $prevent_access Whether WooCommerce wants to block admin.
	 */
	public function allowMintStaffWpAdmin( $prevent_access ): bool {
		if ( $this->isMintStaff() ) {
			return false;
		}

		return (bool) $prevent_access;
	}

	/**
	 * Keep the WP admin bar for Mint staff (WooCommerce disables it for non-editors).
	 *
	 * @param bool $disable Whether WooCommerce wants the admin bar disabled.
	 */
	public function allowMintStaffAdminBar( $disable ): bool {
		if ( $this->isMintStaff() ) {
			return false;
		}

		return (bool) $disable;
	}

	private function isMintStaff(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		return current_user_can( 'manage_options' )
			|| current_user_can( Roles::CAP_EDIT )
			|| current_user_can( Roles::CAP_MANAGE );
	}

	private function isFrontendStudent(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( $this->isMintStaff() ) {
			return false;
		}

		return current_user_can( Roles::CAP_TAKE );
	}
}

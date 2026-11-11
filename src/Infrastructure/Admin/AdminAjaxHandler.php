<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

use MintLMS\Infrastructure\Ui\MintUi;

final class AdminAjaxHandler {

	public function register(): void {
		add_action( 'wp_ajax_mint_lms_toggle_details', array( $this, 'toggleDetails' ) );
	}

	public function toggleDetails(): void {
		if ( ! current_user_can( 'edit_mintlms_courses' ) ) {
			wp_send_json_error( null, 403 );
		}

		check_ajax_referer( 'mint_lms_toggle_details', 'nonce' );

		$userId = get_current_user_id();
		$show   = isset( $_POST['show'] ) && '1' === sanitize_text_field( wp_unslash( (string) $_POST['show'] ) );

		MintUi::setShowDetailsPreference( $userId, $show );

		wp_send_json_success();
	}
}

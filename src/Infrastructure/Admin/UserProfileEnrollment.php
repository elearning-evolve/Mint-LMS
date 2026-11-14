<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Capability\Roles;
use MintLMS\Infrastructure\Ui\UiRoot;

final class UserProfileEnrollment {

	public function register(): void {
		add_action( 'show_user_profile', array( $this, 'render' ) );
		add_action( 'edit_user_profile', array( $this, 'render' ) );
	}

	public function render( \WP_User $user ): void {
		if ( ! current_user_can( Roles::CAP_ENROLL ) ) {
			return;
		}

		$renderer = new ViewRenderer();
		$boot     = wp_json_encode(
			array(
				'userId'   => (int) $user->ID,
				'userName' => (string) $user->display_name,
			)
		);
		if ( ! is_string( $boot ) ) {
			$boot = '{}';
		}

		echo UiRoot::open(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wrapper id/class only.
		$renderer->echo(
			'admin/users/_profile-enroll',
			array(
				'bootJson' => $boot,
				'user'     => $user,
				'renderer' => $renderer,
			)
		);
		echo $renderer->component( 'toast' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo UiRoot::close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

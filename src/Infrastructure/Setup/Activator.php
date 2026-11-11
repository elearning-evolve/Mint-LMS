<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Setup;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\Capability\CapabilityRegistrar;

final class Activator {

	public function activate(): void {
		( new CapabilityRegistrar() )->register();
		( new PageInstaller() )->install();
		$this->ensureDefaultOptions();
	}

	private function ensureDefaultOptions(): void {
		if ( false === get_option( PageSettings::OPTION_DEFAULT_ENROLLMENT, false ) ) {
			add_option( PageSettings::OPTION_DEFAULT_ENROLLMENT, 'free' );
		}

		if ( false === get_option( PageSettings::OPTION_EMAIL_ENROLL, false ) ) {
			add_option( PageSettings::OPTION_EMAIL_ENROLL, '1' );
		}

		if ( false === get_option( PageSettings::OPTION_EMAIL_COMPLETE, false ) ) {
			add_option( PageSettings::OPTION_EMAIL_COMPLETE, '1' );
		}
	}
}

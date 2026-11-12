<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Admin;

defined( 'ABSPATH' ) || exit;

use MintLMS\Application\Contract\DashboardPresentationInterface;
use MintLMS\Infrastructure\Ui\MintUi;

final class MintUiDashboardPresentation implements DashboardPresentationInterface {

	public function hueByIndex( int $index ): array {
		return MintUi::hueByIndex( $index );
	}

	public function editedLabel( \DateTimeInterface $date ): string {
		return MintUi::editedLabel( $date );
	}

	public function courseInitial( string $title ): string {
		return MintUi::courseInitial( $title );
	}

	public function initials( string $name ): string {
		return MintUi::initials( $name );
	}

	public function relativeTimeShort( \DateTimeInterface $date ): string {
		return MintUi::relativeTimeShort( $date );
	}
}

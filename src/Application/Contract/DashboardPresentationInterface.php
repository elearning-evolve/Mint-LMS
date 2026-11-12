<?php
declare(strict_types=1);

namespace MintLMS\Application\Contract;

interface DashboardPresentationInterface {

	/**
	 * @return array{bg: string, ink: string}
	 */
	public function hueByIndex( int $index ): array;

	public function editedLabel( \DateTimeInterface $date ): string;

	public function courseInitial( string $title ): string;

	public function initials( string $name ): string;

	public function relativeTimeShort( \DateTimeInterface $date ): string;
}

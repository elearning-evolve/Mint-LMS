<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Ui;

defined( 'ABSPATH' ) || exit;

final class MintUi {

	/** @var list<array{bg: string, ink: string}> */
	private const HUES = array(
		array(
			'bg'  => '#EFEBFF',
			'ink' => '#4B23C4',
		),
		array(
			'bg'  => '#E6F1FF',
			'ink' => '#1A5AA8',
		),
		array(
			'bg'  => '#E8F6EE',
			'ink' => '#0A6B4C',
		),
		array(
			'bg'  => '#FDEFE4',
			'ink' => '#9A4E12',
		),
		array(
			'bg'  => '#FBE9F2',
			'ink' => '#A22069',
		),
		array(
			'bg'  => '#FCF3DC',
			'ink' => '#7A5407',
		),
	);

	/**
	 * @return array{bg: string, ink: string}
	 */
	public static function hueByIndex( int $index ): array {
		$pair = self::HUES[ $index % count( self::HUES ) ];

		return array(
			'bg'  => $pair['bg'],
			'ink' => $pair['ink'],
		);
	}

	public static function initials( string $name ): string {
		$parts = preg_split( '/\s+/', trim( $name ) );
		if ( false === $parts ) {
			$parts = array();
		}

		if ( array() === $parts ) {
			return '?';
		}

		if ( 1 === count( $parts ) ) {
			return strtoupper( mb_substr( $parts[0], 0, 2 ) );
		}

		return strtoupper( mb_substr( $parts[0], 0, 1 ) . mb_substr( $parts[ count( $parts ) - 1 ], 0, 1 ) );
	}

	public static function courseInitial( string $title ): string {
		$trimmed = trim( $title );
		if ( '' === $trimmed ) {
			return '?';
		}

		if ( 1 === preg_match( '/\p{L}|\p{N}/u', $trimmed, $matches ) ) {
			return mb_strtoupper( $matches[0] );
		}

		return '?';
	}

	public static function greeting(): string {
		$hour = (int) wp_date( 'G' );

		if ( $hour < 12 ) {
			return __( 'Good morning', 'mint-lms' );
		}

		if ( $hour < 17 ) {
			return __( 'Good afternoon', 'mint-lms' );
		}

		return __( 'Good evening', 'mint-lms' );
	}

	public static function relativeTime( \DateTimeInterface $date ): string {
		return human_time_diff( $date->getTimestamp(), time() ) . ' ' . __( 'ago', 'mint-lms' );
	}

	public static function relativeTimeShort( \DateTimeInterface $date ): string {
		$diff = time() - $date->getTimestamp();

		if ( $diff < HOUR_IN_SECONDS ) {
			$mins = max( 1, (int) floor( $diff / MINUTE_IN_SECONDS ) );

			/* translators: %d: minutes */
			return sprintf( _n( '%dm ago', '%dm ago', $mins, 'mint-lms' ), $mins );
		}

		if ( $diff < DAY_IN_SECONDS ) {
			$hours = max( 1, (int) floor( $diff / HOUR_IN_SECONDS ) );

			/* translators: %d: hours */
			return sprintf( _n( '%dh ago', '%dh ago', $hours, 'mint-lms' ), $hours );
		}

		if ( $diff < WEEK_IN_SECONDS ) {
			$days = max( 1, (int) floor( $diff / DAY_IN_SECONDS ) );

			/* translators: %d: days */
			return sprintf( _n( '%dd ago', '%dd ago', $days, 'mint-lms' ), $days );
		}

		return wp_date( 'M j', $date->getTimestamp() );
	}

	public static function editedLabel( \DateTimeInterface $date ): string {
		$diff = time() - $date->getTimestamp();

		if ( $diff < DAY_IN_SECONDS ) {
			return self::relativeTimeShort( $date );
		}

		if ( $diff < WEEK_IN_SECONDS ) {
			$days = max( 1, (int) floor( $diff / DAY_IN_SECONDS ) );

			/* translators: %d: days */
			return sprintf( _n( '%dd ago', '%dd ago', $days, 'mint-lms' ), $days );
		}

		if ( $diff < MONTH_IN_SECONDS ) {
			$weeks = max( 1, (int) floor( $diff / WEEK_IN_SECONDS ) );

			/* translators: %d: weeks */
			return sprintf( _n( '%dw ago', '%dw ago', $weeks, 'mint-lms' ), $weeks );
		}

		return wp_date( 'M j', $date->getTimestamp() );
	}

	public static function showDetailsPreference( int $userId ): bool {
		return (bool) get_user_meta( $userId, 'mint_lms_show_details', true );
	}

	public static function setShowDetailsPreference( int $userId, bool $show ): void {
		update_user_meta( $userId, 'mint_lms_show_details', $show ? '1' : '0' );
	}
}

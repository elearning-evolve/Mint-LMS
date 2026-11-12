<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Ui;

defined( 'ABSPATH' ) || exit;

final class UiRoot {

	public const ID = 'mint-lms-root';

	public static function open( string $context = 'admin' ): string {
		$classes = array( 'mint-lms-ui' );

		if ( 'student' === $context ) {
			$classes[] = 'mint-lms-student';
		}

		return sprintf(
			'<div id="%s" class="%s" data-mint-lms-ui="1">',
			esc_attr( self::ID ),
			esc_attr( implode( ' ', $classes ) )
		);
	}

	public static function close(): string {
		return '</div>';
	}
}

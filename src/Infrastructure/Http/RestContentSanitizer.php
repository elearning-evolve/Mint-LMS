<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Http;

defined( 'ABSPATH' ) || exit;

final class RestContentSanitizer {

	public static function richText( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		return wp_kses_post( $value );
	}
}

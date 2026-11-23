<?php
declare(strict_types=1);

namespace MintLMS\Application\Quiz;

defined( 'ABSPATH' ) || exit;

/**
 * Essay answers may be plain text or JSON {"text":"...","attachmentId":123}.
 */
final class EssayAnswerCodec {

	/**
	 * @return array{text: string, attachmentId: int}
	 */
	public static function decode( string $raw ): array {
		$trimmed = trim( $raw );
		if ( '' === $trimmed ) {
			return array(
				'text'         => '',
				'attachmentId' => 0,
			);
		}

		if ( str_starts_with( $trimmed, '{' ) ) {
			$decoded = json_decode( $trimmed, true );
			if ( is_array( $decoded ) ) {
				return array(
					'text'         => (string) ( $decoded['text'] ?? '' ),
					'attachmentId' => max( 0, (int) ( $decoded['attachmentId'] ?? 0 ) ),
				);
			}
		}

		return array(
			'text'         => $raw,
			'attachmentId' => 0,
		);
	}

	public static function encode( string $text, int $attachmentId = 0 ): string {
		if ( $attachmentId <= 0 ) {
			return $text;
		}

		$json = json_encode(
			array(
				'text'         => $text,
				'attachmentId' => $attachmentId,
			),
			JSON_UNESCAPED_UNICODE
		);

		return is_string( $json ) ? $json : $text;
	}

	public static function hasContent( string $raw ): bool {
		$decoded = self::decode( $raw );

		return '' !== trim( $decoded['text'] ) || $decoded['attachmentId'] > 0;
	}
}

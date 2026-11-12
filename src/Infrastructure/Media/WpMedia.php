<?php
declare(strict_types=1);

namespace MintLMS\Infrastructure\Media;

use MintLMS\Application\Contract\MediaInterface;

final class WpMedia implements MediaInterface {

	/** @var list<string> */
	private const ALLOWED_MIME_TYPES = array(
		'image/jpeg',
		'image/png',
		'image/gif',
		'image/webp',
		'application/pdf',
		'video/mp4',
	);

	public function getUrl( int $attachmentId ): string {
		if ( $attachmentId <= 0 ) {
			return '';
		}

		$url = wp_get_attachment_url( $attachmentId );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * @param array<string, mixed> $file Upload payload (typically a single $_FILES entry).
	 */
	public function validateUpload( array $file ): bool {
		if ( ! isset( $file['error'] ) || UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return false;
		}

		if ( empty( $file['tmp_name'] ) || ! is_string( $file['tmp_name'] ) ) {
			return false;
		}

		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			return false;
		}

		$filename = isset( $file['name'] ) && is_string( $file['name'] ) ? $file['name'] : '';
		$checked  = wp_check_filetype( $filename );

		if ( empty( $checked['type'] ) || ! in_array( $checked['type'], self::ALLOWED_MIME_TYPES, true ) ) {
			return false;
		}

		return true;
	}
}

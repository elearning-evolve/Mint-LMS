<?php
declare(strict_types=1);

namespace MintLMS\Api\V1\Registry;

final class CertificateTemplateRegistry {

	/** @var array<string, CertificateTemplateInterface> */
	private static array $templates = array();

	public static function register( CertificateTemplateInterface $template ): void {
		self::$templates[ $template->slug() ] = $template;
	}

	public static function get( string $slug ): ?CertificateTemplateInterface {
		return self::$templates[ $slug ] ?? null;
	}

	/**
	 * @return array<string, CertificateTemplateInterface>
	 */
	public static function all(): array {
		return self::$templates;
	}

	public static function has( string $slug ): bool {
		return isset( self::$templates[ $slug ] );
	}
}

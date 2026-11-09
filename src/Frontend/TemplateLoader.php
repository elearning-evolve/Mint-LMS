<?php
declare(strict_types=1);

namespace MintLMS\Frontend;

defined( 'ABSPATH' ) || exit;

use MintLMS\Infrastructure\View\ScopedPhpTemplate;

final class TemplateLoader {

	/**
	 * @param array<string, mixed> $vars
	 */
	public function render( string $template, array $vars = array() ): string {
		return ScopedPhpTemplate::render( $this->locate( $template ), $vars );
	}

	public function locate( string $template ): string {
		$template = ltrim( $template, '/' );

		$themePaths = array(
			get_stylesheet_directory() . '/mint-lms/' . $template,
			get_template_directory() . '/mint-lms/' . $template,
		);

		foreach ( $themePaths as $themePath ) {
			if ( is_readable( $themePath ) ) {
				return $themePath;
			}
		}

		return MINTLMS_PATH . 'templates/' . $template;
	}
}
